<?php

namespace Tests\Unit;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Endpoint pengelola role, menu, dan hari libur (/api/roles*, /api/menus*,
 * /api/management/holidays*) wajib dilindungi middleware `menu:`. Dulu hanya dibungkus "sudah login", sehingga karyawan mana
 * pun bisa mengubah akses role lewat API langsung. Tidak menyentuh database.
 */
class RoleMenuRouteGuardTest extends TestCase
{
    /** @return array<string, string[]> uri+metode => slug yang diterima middleware */
    private function guardedRoutes(): array
    {
        $found = [];

        foreach (Route::getRoutes() as $route) {
            /** @var LaravelRoute $route */
            $uri = $route->uri();
            $isRoleOrMenu = preg_match('#^api/(roles|menus|management/holidays)(/|$)#', $uri) === 1;

            if (! $isRoleOrMenu) {
                continue;
            }

            $slugs = [];
            foreach ($route->gatherMiddleware() as $mw) {
                if (is_string($mw) && str_starts_with($mw, 'menu:')) {
                    $slugs = array_merge($slugs, explode(',', substr($mw, 5)));
                }
                // menu.can:<slug>,<kotak> (Create/Edit/Delete per menu): slug-nya yang dihitung.
                if (is_string($mw) && str_starts_with($mw, 'menu.can:')) {
                    $slugs[] = explode(',', substr($mw, 9))[0];
                }
            }

            $found[implode('|', $route->methods()) . ' ' . $uri] = $slugs;
        }

        return $found;
    }

    public function test_every_role_and_menu_api_route_requires_a_menu_permission(): void
    {
        $routes = $this->guardedRoutes();

        $this->assertNotEmpty($routes);

        foreach ($routes as $name => $slugs) {
            $this->assertNotEmpty($slugs, "{$name} tidak punya middleware menu:");
        }
    }

    public function test_write_routes_are_limited_to_the_management_menus(): void
    {
        foreach ($this->guardedRoutes() as $name => $slugs) {
            if (! preg_match('/^(POST|PUT|DELETE|PATCH)/', $name)) {
                continue;
            }

            $allowed = str_contains($name, 'api/management/holidays')
                ? ['management.holidays']
                : ['management.roles', 'management.permissions'];

            foreach ($slugs as $slug) {
                $this->assertContains($slug, $allowed, "{$name} boleh diakses lewat slug {$slug}");
            }
        }
    }
}
