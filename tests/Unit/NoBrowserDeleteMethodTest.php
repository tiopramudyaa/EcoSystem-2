<?php

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * WAF di production memblokir method HTTP DELETE, jadi tombol hapus di UI memakai
 * POST ".../delete" (pola yang sama di seluruh proyek). Test ini menjaga agar tidak ada
 * tampilan yang kembali memanggil DELETE, dan agar tiap varian POST-nya tetap terdaftar.
 */
class NoBrowserDeleteMethodTest extends TestCase
{
    public function test_no_view_calls_the_http_delete_method(): void
    {
        $offenders = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if (!str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            if (preg_match('/method:\s*[\'"]DELETE[\'"]|axios\.delete\(|type:\s*[\'"]DELETE[\'"]/', $src)) {
                $offenders[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders, 'Pakai POST ".../delete", bukan method DELETE: ' . implode(', ', $offenders));
    }

    /** @return array<string, array{0: string}> */
    public static function postDeleteRoutes(): array
    {
        return [
            'mandays draft'        => ["Route::post('/{ticketId}/mandays/pic-draft/delete'"],
            'report template'      => ["Route::post('/{reportTemplate}/delete'"],
            'weekly consolidation' => ["Route::post('/weekly-consolidation/{id}/delete'"],
            'weekly force delete'  => ["Route::post('/weekly-consolidation/{id}/force/delete'"],
        ];
    }

    /**
     * @dataProvider postDeleteRoutes
     */
    public function test_post_variant_is_registered(string $needle): void
    {
        $this->assertStringContainsString($needle, file_get_contents(base_path('routes/api.php')));
    }
}
