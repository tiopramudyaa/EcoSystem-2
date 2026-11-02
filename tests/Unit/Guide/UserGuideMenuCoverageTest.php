<?php

namespace Tests\Unit\Guide;

use App\Services\Guide\UserGuideService;
use Tests\TestCase;

/**
 * Setiap halaman menu harus punya topik panduan sendiri, supaya pencarian di User Guide
 * mengikuti menu dan submenu. Slug diambil dari middleware `menu:` di routes/web.php, jadi
 * halaman baru tanpa panduan membuat test ini gagal. Tambahkan prefix grup baru ke
 * COVERED_GROUPS setelah topiknya selesai ditulis.
 */
class UserGuideMenuCoverageTest extends TestCase
{
    /** Izin tombol (edit, tambah, hapus) di dalam satu bagian halaman tidak perlu topik sendiri: topik halamannya yang menjelaskan. */
    private const ACTION_SUFFIXES = ['.edit', '.manage', '.delete'];

    private const COVERED_GROUPS = ['control-center.', 'management.', 'delivery.', 'delivery-project.', 'delivery-support.'];

    public function test_every_menu_page_of_a_covered_group_has_a_guide_topic(): void
    {
        $routes = '';
        foreach (glob(base_path('routes/*.php')) as $file) {
            $routes .= file_get_contents($file);
        }
        preg_match_all("/middleware\('menu:([a-z0-9.\-]+)'\)/", $routes, $m);

        $pages = array_unique(array_filter($m[1], function (string $slug) {
            foreach (self::ACTION_SUFFIXES as $suffix) {
                if (str_ends_with($slug, $suffix) && !str_starts_with($slug, 'management.') && !str_starts_with($slug, 'control-center.')) {
                    return false;
                }
            }
            foreach (self::COVERED_GROUPS as $prefix) {
                if (str_starts_with($slug, $prefix)) {
                    return true;
                }
            }

            return false;
        }));
        $this->assertNotEmpty($pages, 'tidak ada slug menu yang ditemukan di routes/web.php');

        $required = [];
        foreach ((new UserGuideService())->load('en', false) as $topic) {
            foreach ($topic['requires'] as $slug) {
                $required[$slug] = true;
            }
        }

        $missing = array_values(array_filter($pages, fn (string $slug) => !isset($required[$slug])));
        $this->assertSame([], $missing, 'menu tanpa topik panduan: ' . implode(', ', $missing));
    }
}
