# Sandbox Hoplite (Preview & test)

- `setup.sh`: pasang PHP 8.3 + Composer, `composer install`, MariaDB lokal, skema
  `tests/_support/Database/skema_uji.sql`, data uji, dan `.env` sandbox (HTTP polos).
- `run.sh`: start MariaDB lalu `php spark serve` di :3000 (Preview).
- `db.sh start|seed`: kontrol MariaDB lokal dan data uji.

Akun uji sandbox: `admin.uji` (ADMIN) dan `staf.b` (unit B). Password bawaan ada di
`db.sh` dan hanya berlaku untuk database lokal sekali pakai; ganti lewat
`EULT_SANDBOX_ADMIN_PASS` / `EULT_SANDBOX_STAF_PASS` bila perlu. Tidak ada
hubungan dengan kredensial produksi (`db_newtiket` produksi memakai `.env` EULT_DB_*).
