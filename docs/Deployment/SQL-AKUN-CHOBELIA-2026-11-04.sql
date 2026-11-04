-- ============================================================================
-- Akun Ermania Chobelia Fitriana (ECI C25037) — langsung bisa masuk, tanpa verifikasi email
-- Dibuat 4 Nov 2026. Jalankan di phpMyAdmin pada database EcoSystem (mis. ec / database produksi).
--
-- Efek: password menjadi "password123", akun dianggap sudah verifikasi + sudah ganti password
--       (is_already_cp = 1), sehingga TIDAK diminta email/OTP/ganti password saat login pertama.
-- Login dengan: ECI  C25037   atau email  legal.chobelia@eclectic.co.id   + password  password123
-- Hanya menyentuh SATU baris auth_users (dikunci lewat username = ECI dan employee-nya). Tidak ada baris yang dihapus.
-- ============================================================================

-- 1) Lihat kondisi SEBELUM (harus tepat 1 baris)
SELECT a.id, a.employee_id, a.username, a.email, a.is_active, a.is_already_cp, a.email_verified_at
FROM auth_users a
WHERE a.username = 'C25037'
  AND a.employee_id IN (SELECT e.employee_id FROM employee e WHERE e.eci = 'C25037');

-- 2) Ubah
UPDATE auth_users
SET password          = '$2y$12$aGoKSGZxmMBijvxqHUZ4qulcsMA3yG/Lywr.nfxehRywADhb1I.9m',
    is_active         = 1,
    is_already_cp     = 1,
    email_verified_at = COALESCE(email_verified_at, NOW()),
    cp_token          = NULL,
    cp_token_expires_at = NULL,
    updated_at        = NOW()
WHERE username = 'C25037'
  AND employee_id IN (SELECT e.employee_id FROM employee e WHERE e.eci = 'C25037');
-- phpMyAdmin harus menampilkan "1 row affected". Bila 0, ECI/username berbeda di database ini — jangan lanjut.

-- 3) Periksa SESUDAH
SELECT id, employee_id, username, email, is_active, is_already_cp, email_verified_at, LEFT(password, 7) AS hash_awal
FROM auth_users
WHERE username = 'C25037';
-- hash_awal harus diawali $2y$12
