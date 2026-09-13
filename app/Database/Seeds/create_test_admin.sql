-- ============================================================
-- SCRIPT: Buat akun admin test sementara untuk QA/Impeccable
-- DB Target: db_newtiket (production)
-- Dibuat: 2026-09-13
-- ============================================================
-- PERHATIAN: Hapus akun ini setelah sesi QA selesai!
-- Jalankan bagian DROP di bawah untuk menghapusnya.
-- ============================================================

-- [1] BUAT AKUN TEST
-- Password plaintext: Impeccable@2026!
-- Hash bcrypt dibuat via: php -r "echo password_hash('Impeccable@2026!', PASSWORD_DEFAULT);"
INSERT INTO `s_user`
  (`susrNama`, `susrPassword`, `susrSgroupNama`, `susrProfil`, `susrPertanyaan`, `susrJawaban`, `susrAvatar`, `susrRefIndex`, `susrLastLogin`, `susrCategoryId`)
VALUES
  ('test.impeccable', '$2y$12$hqQX3l5Fs46pYXt9PRFqa.xgktnaG1etZ7iILtraXg/bQ5ngxjo1e', 'ADMIN', 'TEST', 'qa', 'qa', '', '', NOW(), NULL)
ON DUPLICATE KEY UPDATE
  `susrPassword`    = VALUES(`susrPassword`),
  `susrSgroupNama`  = 'ADMIN';

-- Verifikasi
SELECT `susrNama`, `susrSgroupNama`, `susrLastLogin`
FROM `s_user`
WHERE `susrNama` = 'test.impeccable';

-- ============================================================
-- [2] HAPUS AKUN TEST (jalankan setelah QA selesai)
-- ============================================================
-- DELETE FROM `s_user` WHERE `susrNama` = 'test.impeccable';
