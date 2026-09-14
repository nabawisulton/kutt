-- =====================================================================
-- KUTT SUKA MAKMUR - Seed: akun demo ANGGOTA + anggota terhubung (portal)
-- Idempoten: INSERT IGNORE / guard WHERE NOT EXISTS. Aman dijalankan ulang.
-- File terpisah agar INSTALASI LAMA (yang sudah mencatat seed lama di tabel
-- migrations) tetap mendapat data ini saat menjalankan `php database/migrate.php`.
-- =====================================================================

-- Akun ANGGOTA demo (password: admin123 — GANTI setelah login pertama!)
INSERT IGNORE INTO users (user_id, username, email, password_hash, role, full_name, is_active)
VALUES ('USR-005', 'anggota', 'anggota@kuttsukamakmur.coop',
        '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC',
        'ANGGOTA', 'Budi Santoso (Anggota Demo)', 1);

-- Anggota demo yang tertaut ke akun di atas (users.user_id => members.user_id).
INSERT IGNORE INTO members (member_no, full_name, address, phone, email, occupation, status, joined_at)
SELECT 'AGT-2026-0001', 'Budi Santoso (Anggota Demo)', 'Dusun Grati, Pasuruan, Jawa Timur',
       '0812-0000-0001', 'anggota@kuttsukamakmur.coop', 'Peternak Sapi Perah', 'AKTIF', CURRENT_DATE
WHERE NOT EXISTS (SELECT 1 FROM members WHERE member_no = 'AGT-2026-0001');

UPDATE members SET user_id = (SELECT id FROM users WHERE username = 'anggota')
WHERE member_no = 'AGT-2026-0001' AND user_id IS NULL;
