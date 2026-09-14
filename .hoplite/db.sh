#!/usr/bin/env bash
# Kontrol MariaDB lokal sandbox: start | seed
set -euo pipefail
cd "$(dirname "$0")/.."

case "${1:-start}" in
  start)
    if ! mysqladmin ping --silent 2>/dev/null; then
      mkdir -p /run/mysqld
      (mysqld_safe --user=root --datadir=/var/lib/mysql >/tmp/mariadb.log 2>&1 &)
      for _ in $(seq 1 30); do
        mysqladmin ping --silent 2>/dev/null && break
        sleep 1
      done
    fi
    mysqladmin ping --silent
    ;;
  seed)
    mysql -e "CREATE USER IF NOT EXISTS 'eult'@'localhost' IDENTIFIED BY 'eult-uji';
      GRANT ALL ON db_newtiket.* TO 'eult'@'localhost';
      GRANT ALL ON db_ult.* TO 'eult'@'localhost'; FLUSH PRIVILEGES;"
    # Akun uji sandbox (bukan produksi). Password dibaca dari env agar dapat
    # diganti; nilai bawaan hanya untuk MariaDB lokal yang sekali pakai.
    SANDBOX_ADMIN_PASS="${EULT_SANDBOX_ADMIN_PASS:-AdminUji#2026Sandbox}"
    SANDBOX_STAF_PASS="${EULT_SANDBOX_STAF_PASS:-StafB#2026Sandbox}"
    HASH_ADMIN=$(SANDBOX_PASS="$SANDBOX_ADMIN_PASS" php -r 'echo password_hash(getenv("SANDBOX_PASS"), PASSWORD_DEFAULT);')
    HASH_STAF=$(SANDBOX_PASS="$SANDBOX_STAF_PASS" php -r 'echo password_hash(getenv("SANDBOX_PASS"), PASSWORD_DEFAULT);')
    mysql db_newtiket <<SQL
INSERT IGNORE INTO s_user (susrNama, susrPassword, susrSgroupNama, susrProfil) VALUES
  ('admin.uji', '$HASH_ADMIN', 'ADMIN', 'Admin Uji'),
  ('staf.b', '$HASH_STAF', 'STAF_UJI_UNIT_B', 'Staf Unit B');
INSERT IGNORE INTO d_ticketing (ticketTrackingId, ticketName, ticketEmail, ticketNoHp, ticketCategories, ticketPriority, ticketSubject, ticketMessage, ticketCreated, ticketStatus, ticketAssign, ticketIdentitas) VALUES
  ('UJI1-SAND-001', 'Pemohon Uji', 'pemohon@example.invalid', '0812', '1', '1', 'Surat Keterangan', 'Mohon surat', NOW(), 1, '01', '1234567890'),
  ('QEHO-HTTV-001', 'Uji Klaster3', 'uji@example.invalid', '0812', '1', '1', 'Uji', 'Uji', NOW(), 1, NULL, NULL);
INSERT IGNORE INTO t_surat (tsuratLayananId, tsuratNomor, tsuratPerihal, tsuratIsi, tsuratFooter, tsuratLampiran, tsuratTujuan, tsuratForm) VALUES
  ('1', '001/UN17/2026', 'Keterangan Aktif', '<p>Isi template</p>', '', '-', '', 'cetak_1');
SQL
    mysql db_ult <<'SQL'
INSERT IGNORE INTO ref_unit VALUES (1, 'Biro Akademik', 1);
INSERT IGNORE INTO ref_jenis_layanan VALUES (1, 'Layanan Akademik');
INSERT IGNORE INTO ref_layanan VALUES (1, 'Surat Keterangan Aktif', 1, 1, 'TOPBOTTOM', 1);
SQL
    ;;
  *)
    echo "pemakaian: $0 {start|seed}" >&2
    exit 1
    ;;
esac
