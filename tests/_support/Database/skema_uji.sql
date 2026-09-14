-- Skema minimal db_newtiket + db_ult untuk suite `Database` di lingkungan
-- tanpa dump produksi (mis. sandbox/CI). Kolom mengikuti yang dipakai model
-- dan view; tipe disederhanakan. Jalankan:
--   mysql < tests/_support/Database/skema_uji.sql
CREATE DATABASE IF NOT EXISTS db_newtiket CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS db_ult CHARACTER SET utf8mb4;

USE db_ult;
CREATE TABLE IF NOT EXISTS ref_unit (
  unitId INT PRIMARY KEY, unitNama VARCHAR(150), unitUrut INT DEFAULT 0
);
CREATE TABLE IF NOT EXISTS ref_jenis_layanan (
  jenislayananId INT PRIMARY KEY, jenislayananNama VARCHAR(150)
);
CREATE TABLE IF NOT EXISTS ref_layanan (
  layananId INT PRIMARY KEY, layananNama VARCHAR(200), layananunitId INT, layananjenisId INT,
  layananisbottomup VARCHAR(20), layananisdisplay TINYINT DEFAULT 1
);

USE db_newtiket;
CREATE TABLE IF NOT EXISTS d_ticketing (
  ticketTrackingId VARCHAR(30) PRIMARY KEY,
  ticketName VARCHAR(150), ticketEmail VARCHAR(150), ticketNoHp VARCHAR(30), ticketCategories VARCHAR(20),
  ticketPriority VARCHAR(5), ticketSubject VARCHAR(255), ticketMessage TEXT, ticketCreated DATETIME,
  ticketAssigned DATETIME, ticketUpdated DATETIME, ticketClosed DATETIME, ticketAssignedBy VARCHAR(150),
  ticketUpdatedBy VARCHAR(150), ticketClosedBy VARCHAR(150), ticketAcceptedBy VARCHAR(150), ticketStatus INT DEFAULT 1,
  ticketArchiveId VARCHAR(30), ticketCustomer VARCHAR(150), ticketIsValidasi TINYINT DEFAULT 0, ticketValidated DATETIME,
  ticketAccepted DATETIME, ticketValidatedBy VARCHAR(150), ticketIsChange TINYINT DEFAULT 0, ticketAssign VARCHAR(20),
  ticketAccept VARCHAR(20), ticketIdentitas VARCHAR(30), ticketSuratCreated DATETIME, ticketCreatedBy VARCHAR(150),
  ticketIsVerified TINYINT DEFAULT 0, ticketVerifiedBy VARCHAR(150), ticketVerified DATETIME, ticketSuratId INT,
  ticketRejected DATETIME, ticketKetTolak TEXT, ticketKetSuratTolak TEXT, ticketSuratTolakBy VARCHAR(150),
  ticketRejectedBy VARCHAR(150), ticketmValidasi TEXT
);
CREATE TABLE IF NOT EXISTS d_archive (
  archiveId VARCHAR(30) PRIMARY KEY, archiveTrackingId VARCHAR(30), archiveJenis VARCHAR(20), archiveFile VARCHAR(255), archiveUrl VARCHAR(255)
);
CREATE TABLE IF NOT EXISTS d_replies (
  repliesId INT AUTO_INCREMENT PRIMARY KEY, repliesTicketId VARCHAR(30), repliesMessage TEXT, repliesStatus VARCHAR(50),
  repliesDate DATETIME, repliesBy VARCHAR(150), repliesFile VARCHAR(255), repliesRead VARCHAR(2) DEFAULT '0'
);
CREATE TABLE IF NOT EXISTS d_disposisi (
  disposisiId INT AUTO_INCREMENT PRIMARY KEY, disposisiTicketId VARCHAR(30), disposisiMessage TEXT, disposisiTanggal DATETIME,
  disposisiUser VARCHAR(150), disposisiUnit VARCHAR(20), disposisiUserProfil VARCHAR(150), disposisiStatus INT,
  disposisiPriority VARCHAR(5), disposisiArchiveId VARCHAR(30), disposisiIsTrue TINYINT DEFAULT 0, disposisiTanggalAkhir DATETIME,
  disposisiPreviousUnit VARCHAR(20), disposisiIsRejected TINYINT DEFAULT 0
);
CREATE TABLE IF NOT EXISTS d_rating (
  ratingTicketId VARCHAR(30) PRIMARY KEY, ratingNilai VARCHAR(5)
);
CREATE TABLE IF NOT EXISTS d_history (
  historyId INT AUTO_INCREMENT PRIMARY KEY, detailHistory TEXT, tglHistory DATETIME, ticketTrackingIdHistory VARCHAR(30)
);
CREATE TABLE IF NOT EXISTS d_worker (
  workerId INT AUTO_INCREMENT PRIMARY KEY, workerTrackingId VARCHAR(30), workerUser VARCHAR(150)
);
CREATE TABLE IF NOT EXISTS r_surat (
  suratId INT AUTO_INCREMENT PRIMARY KEY, suratTrackingId VARCHAR(30), suratNomor VARCHAR(150), suratNomorTanggal DATE,
  suratJenis VARCHAR(255), suratPerihal VARCHAR(255), suratBody TEXT, suratFooter TEXT, suratLampiran VARCHAR(255),
  suratTujuan TEXT, suratTanggal VARCHAR(50), suratPejabatNama VARCHAR(150), suratPejabatNIP VARCHAR(50),
  suratPejabatJabatan VARCHAR(150), suratPejabatJabatanAnDraft VARCHAR(150), suratPejabatNIPDraft VARCHAR(50),
  suratPejabatJabatanDraft VARCHAR(150), suratPejabatNamaDraft VARCHAR(150), suratBank VARCHAR(100),
  SuratNomorPemohon VARCHAR(150), suratTanggalPemohon VARCHAR(50)
);
CREATE TABLE IF NOT EXISTS t_surat (
  tsuratId INT AUTO_INCREMENT PRIMARY KEY, tsuratLayananId VARCHAR(20), tsuratNomor VARCHAR(150), tsuratPerihal VARCHAR(255),
  tsuratIsi TEXT, tsuratFooter TEXT, tsuratLampiran VARCHAR(255), tsuratTujuan TEXT, tsuratForm VARCHAR(30)
);
CREATE TABLE IF NOT EXISTS r_priority (priorityId VARCHAR(5) PRIMARY KEY, priorityName VARCHAR(50));
CREATE TABLE IF NOT EXISTS r_status (statusId INT PRIMARY KEY, statusNama VARCHAR(100), statusColor VARCHAR(30), statusCode VARCHAR(30));
CREATE TABLE IF NOT EXISTS s_unit (
  unitId VARCHAR(20) PRIMARY KEY, unitKode VARCHAR(20), unitNama VARCHAR(150), unitPejabatNama VARCHAR(150),
  unitPejabatNIP VARCHAR(50), unitPejabatJabatan VARCHAR(150), unitPejabatGol VARCHAR(30), unitUrut INT DEFAULT 0
);
CREATE TABLE IF NOT EXISTS s_user (
  susrNama VARCHAR(100) PRIMARY KEY, susrPassword VARCHAR(255), susrSgroupNama VARCHAR(100), susrProfil VARCHAR(150),
  susrPertanyaan VARCHAR(255), susrJawaban VARCHAR(255), susrAvatar VARCHAR(255), susrRefIndex VARCHAR(50),
  susrLastLogin DATETIME, susrCategoryId VARCHAR(20)
);
CREATE TABLE IF NOT EXISTS s_user_group (
  sgroupNama VARCHAR(100) PRIMARY KEY, sgroupKeterangan VARCHAR(255), sgroupCategoryId VARCHAR(20), sgroupSubCategoryId VARCHAR(20), sgroupUrut INT DEFAULT 0
);
CREATE TABLE IF NOT EXISTS s_user_group_user (sgroupSusrNama VARCHAR(100), sgroupSgroupNama VARCHAR(100));
CREATE TABLE IF NOT EXISTS s_user_group_unit (
  sgroupunitSgroupNama VARCHAR(100), sgroupunitUnitId VARCHAR(20), sgroupunitIsHome TINYINT DEFAULT 0, sgroupunitUnitRead TINYINT DEFAULT 1
);
CREATE TABLE IF NOT EXISTS s_user_group_modul (
  sgroupmodulSgroupNama VARCHAR(100), sgroupmodulSusrmodulNama VARCHAR(100), sgroupmodulSusrmodulRead VARCHAR(2) DEFAULT '1'
);
CREATE TABLE IF NOT EXISTS s_user_modul_ref (
  susrmodulNama VARCHAR(100) PRIMARY KEY, susrmodulNamaDisplay VARCHAR(150), susrmodulSusrmdgroupNama VARCHAR(100),
  susrmodulUrut INT DEFAULT 0, susrmodulIsLogin VARCHAR(2) DEFAULT '1'
);
CREATE TABLE IF NOT EXISTS s_user_modul_group_ref (
  susrmdgroupNama VARCHAR(100) PRIMARY KEY, susrmdgroupDisplay VARCHAR(150), susrmdgroupIcon VARCHAR(100)
);
CREATE TABLE IF NOT EXISTS r_category (categoryId INT AUTO_INCREMENT PRIMARY KEY, categoryNama VARCHAR(150), categorysGroupNama VARCHAR(100));
CREATE TABLE IF NOT EXISTS r_category_sub (sCatId INT AUTO_INCREMENT PRIMARY KEY, sCatCategoryId INT, sCatNama VARCHAR(150), sCatDisposisi VARCHAR(30));
CREATE TABLE IF NOT EXISTS r_berkas_layanan (berkasId INT AUTO_INCREMENT PRIMARY KEY, berkasidLayanan VARCHAR(20), berkasNama VARCHAR(255), berkasKeterangan TEXT);

-- Data referensi minimum yang dibutuhkan AuthFilter/BaseController dan test.
INSERT IGNORE INTO r_priority VALUES ('1', 'Normal'), ('2', 'Tinggi');
INSERT IGNORE INTO r_status VALUES (1, 'Baru', 'primary', 'NEW'), (3, 'Diproses', 'warning', 'PROSES'), (5, 'Selesai', 'success', 'DONE'), (6, 'Tervalidasi', 'info', 'VALID'), (8, 'Ditolak', 'danger', 'REJECT');
INSERT IGNORE INTO s_user_modul_group_ref VALUES ('TIKET', 'Tiket', 'flaticon-tiket');
INSERT IGNORE INTO s_user_modul_ref VALUES ('ticketing', 'Ticketing', 'TIKET', 1, '1'), ('home', 'Dashboard', 'TIKET', 0, '1');
INSERT IGNORE INTO s_user_group VALUES ('ADMIN', 'Administrator', NULL, NULL, 1), ('STAF_UJI_UNIT_B', 'Staf unit lain (uji)', NULL, NULL, 9);
INSERT IGNORE INTO s_user_group_modul VALUES ('ADMIN', 'ticketing', '1'), ('ADMIN', 'home', '1'), ('STAF_UJI_UNIT_B', 'ticketing', '1'), ('STAF_UJI_UNIT_B', 'home', '1');
INSERT IGNORE INTO s_unit VALUES ('01', '01', 'Unit A', 'Pejabat A', '1970', 'Kepala A', 'IV', 1), ('02', '02', 'Unit B', 'Pejabat B', '1971', 'Kepala B', 'IV', 2);
INSERT IGNORE INTO s_user_group_unit VALUES ('STAF_UJI_UNIT_B', '02', 1, 1);
