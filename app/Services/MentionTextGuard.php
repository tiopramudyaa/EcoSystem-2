<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Pengaman sisi server untuk @mention di internal note.
 *
 * Klien (web composer & Lite) mengirim mentioned_employee_ids / mentioned_role_ids
 * terpisah dari teks. Kalau klien mengirim id basi (mis. chip "@Rizqi Akbar
 * Romadhoni" sudah dihapus dari editor tetapi id-nya masih tertinggal di daftar
 * pending), orang tersebut ikut menerima notifikasi "mentioned you" padahal tidak
 * disebut di teks (kasus tiket #26110345).
 *
 * Aturannya: sebuah id hanya dipertahankan bila "@Nama Lengkap" (employee) atau
 * "@Nama Role" (role) benar-benar muncul di teks pesan sebagai token utuh.
 * Pencocokan dilakukan dari nama terpanjang dulu dan setiap posisi teks hanya
 * boleh diklaim satu nama, sehingga "@Rizqi Akbar Romadhoni" di teks tidak
 * sekaligus membenarkan mention basi "@Rizqi" (orang lain yang namanya prefiks).
 */
class MentionTextGuard
{
    /**
     * Saring id mention terhadap teks pesan (HTML atau plain).
     *
     * @param  int[]|string[]  $employeeIds
     * @param  int[]|string[]  $roleIds
     * @return array{0:int[],1:int[]}  [employeeIds, roleIds] yang benar-benar disebut di teks
     */
    public static function filter(string $messageHtml, array $employeeIds, array $roleIds): array
    {
        $employeeIds = array_values(array_unique(array_map('intval', $employeeIds)));
        $roleIds     = array_values(array_unique(array_map('intval', $roleIds)));

        if (empty($employeeIds) && empty($roleIds)) {
            return [[], []];
        }

        $employeeNames = empty($employeeIds) ? [] : DB::table('employee_basic_data')
            ->whereIn('employee_id', $employeeIds)
            ->selectRaw("employee_id as id, TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as name")
            ->pluck('name', 'id')
            ->all();

        $roleNames = empty($roleIds) ? [] : DB::table('employee_role')
            ->whereIn('id', $roleIds)
            ->pluck('name', 'id')
            ->all();

        // Id yang namanya tidak ditemukan di database tidak bisa diverifikasi -> dibuang.
        $employeeNames = array_intersect_key($employeeNames, array_flip($employeeIds));
        $roleNames     = array_intersect_key($roleNames, array_flip($roleIds));

        return self::filterByNames($messageHtml, $employeeNames, $roleNames);
    }

    /**
     * Inti pencocokan (tanpa database) - dipisah supaya bisa diuji unit.
     *
     * @param  array<int,string>  $employeeNames  id => nama lengkap
     * @param  array<int,string>  $roleNames      id => nama role
     * @return array{0:int[],1:int[]}
     */
    public static function filterByNames(string $messageHtml, array $employeeNames, array $roleNames): array
    {
        $text = self::normalize(html_entity_decode(strip_tags($messageHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        // Kelompokkan kandidat per "needle" (@nama ternormalisasi). Dua entitas
        // bernama identik berbagi needle yang sama dan diperlakukan sama.
        $groups = [];
        foreach ($employeeNames as $id => $name) {
            $needle = self::needle((string) $name);
            if ($needle !== null) {
                $groups[$needle]['employee'][] = (int) $id;
            }
        }
        foreach ($roleNames as $id => $name) {
            $needle = self::needle((string) $name);
            if ($needle !== null) {
                $groups[$needle]['role'][] = (int) $id;
            }
        }

        // Terpanjang dulu: nama yang lebih spesifik mengklaim posisinya lebih dulu.
        uksort($groups, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $claimed     = []; // [[start, end), ...]
        $keptEmp     = [];
        $keptRole    = [];
        $textLength  = mb_strlen($text);

        foreach ($groups as $needle => $ids) {
            $len    = mb_strlen($needle);
            $offset = 0;
            $found  = [];

            while (($pos = mb_strpos($text, $needle, $offset)) !== false) {
                $offset = $pos + 1;
                $end    = $pos + $len;

                // Batas kanan: "@Ahmad" tidak boleh cocok di dalam "@Ahmadi".
                if ($end < $textLength && preg_match('/[\p{L}\p{N}_]/u', mb_substr($text, $end, 1))) {
                    continue;
                }
                // Posisi yang sudah diklaim nama lebih panjang tidak boleh dipakai ulang.
                foreach ($claimed as [$cs, $ce]) {
                    if ($pos >= $cs && $pos < $ce) {
                        continue 2;
                    }
                }
                $found[] = [$pos, $end];
            }

            if (!empty($found)) {
                array_push($claimed, ...$found);
                array_push($keptEmp, ...($ids['employee'] ?? []));
                array_push($keptRole, ...($ids['role'] ?? []));
            }
        }

        // Pertahankan urutan id sesuai input.
        $keptEmp  = array_values(array_filter(array_keys($employeeNames), fn ($id) => in_array((int) $id, $keptEmp, true)));
        $keptRole = array_values(array_filter(array_keys($roleNames), fn ($id) => in_array((int) $id, $keptRole, true)));

        return [array_map('intval', $keptEmp), array_map('intval', $keptRole)];
    }

    private static function needle(string $name): ?string
    {
        $name = ltrim(self::normalize($name), '@');
        return $name === '' ? null : '@' . $name;
    }

    private static function normalize(string $s): string
    {
        // NBSP (Quill sering menyisipkannya di sekitar chip) dan spasi ganda -> satu spasi.
        $s = str_replace("\u{00A0}", ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        return mb_strtolower(trim($s));
    }
}
