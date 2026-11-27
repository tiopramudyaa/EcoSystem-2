<?php
/**
 * Mengekstrak HANYA grup 'seasonal' dari lang/<locale>/ui.php (aldy_merge) menjadi berkas lang minimal untuk eco_new,
 * karena eco_new belum punya lang/en/ui.php dan lang/id/ui.php. Jangan menyalin ui.php utuh: isinya fitur lain.
 *
 * Pakai (dari akar proyek eco_new, setelah menyalin ui.php aldy_merge ke lokasi sementara):
 *   git show aldy_merge:lang/en/ui.php > /tmp/ui_en.php
 *   git show aldy_merge:lang/id/ui.php > /tmp/ui_id.php
 *   php docs/seasonal-theme-port/extract-seasonal-lang.php /tmp/ui_en.php lang/en/ui.php
 *   php docs/seasonal-theme-port/extract-seasonal-lang.php /tmp/ui_id.php lang/id/ui.php
 * Bila lang/<locale>/ui.php sudah ada di eco_new: JANGAN timpa, tambahkan grup 'seasonal' ke dalamnya.
 */
if ($argc < 3) { fwrite(STDERR, "usage: php extract-seasonal-lang.php <source ui.php> <target ui.php>\n"); exit(1); }
$all = require $argv[1];
if (!isset($all['seasonal']) || !is_array($all['seasonal'])) { fwrite(STDERR, "grup 'seasonal' tidak ditemukan\n"); exit(2); }
if (is_file($argv[2])) { fwrite(STDERR, "{$argv[2]} sudah ada: gabungkan manual, tidak ditimpa\n"); exit(3); }
@mkdir(dirname($argv[2]), 0777, true);
$count = function ($a) use (&$count) { $n = 0; foreach ($a as $v) { $n += is_array($v) ? $count($v) : 1; } return $n; };
file_put_contents($argv[2], "<?php\n\n// Hanya grup tema musiman (diekstrak dari aldy_merge).\nreturn [\n    'seasonal' => " . preg_replace('/^/m', '    ', trim(var_export($all['seasonal'], true))) . ",\n];\n");
echo "OK: " . $count($all['seasonal']) . " kunci ke {$argv[2]}\n";
