# Implementation Plan

## Overview

Dokumen ini mengorganisir 12 bug condition (K1-K4, T1-T4, M1-M3, R1) menjadi 4 klaster implementasi berurutan sesuai urutan yang disarankan di `design.md` bagian Overview, ditambah R1 sebagai audit-ulang independen yang dapat dikerjakan paralel dengan klaster manapun.

- **Klaster 1**: Query Aman (K1)
- **Klaster 2**: Kepemilikan Berkas & Rating (K2, T4, M2)
- **Klaster 3**: Pertahanan Perimeter (T1, T2, T3, M1, M3)
- **Klaster 4**: Konfigurasi & Observability (K3, K4)
- **R1**: Audit Ulang esc() (independen, paralel)

Setiap klaster mengikuti metodologi bug condition: Explore (test sebelum fix, HARUS gagal) → Preserve (test sebelum fix, HARUS lulus) → Implement (fix) → Validate (kedua test di atas dijalankan ulang pasca-fix) → Checkpoint.

---

## Tasks

## Klaster 1: Query Aman (K1)

- [x] 1. Tulis test eksplorasi bug condition K1
  - **Property 1: Bug Condition** - SQL Injection via Kondisi String Mentah
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki — kegagalan mengonfirmasi bug ada
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: Test ini mengenkode expected behavior — akan memvalidasi fix ketika lulus setelah implementasi
  - **GOAL**: Surface counterexample yang mendemonstrasikan SQLi pada `Cektiket::rating()`, `Login::savetiket()` (`eult_auto_increment()`/`ModelMaster::getByLastId()`), dan readback `byId()`
  - **Scoped PBT Approach**: Bug ini cocok untuk PBT domain besar (Testing Strategy design.md: "K1: string SQLi arbitrer") — generate string arbitrer termasuk metacharacter SQL (`'`, `OR`, `--`, `;`), unicode, string kosong sebagai `nomorTiket`/`idTiket`
  - Kirim `nomorTiket = "X' OR '1'='1"` ke `Cektiket::rating()` pada kode asli (`byId("ticketTrackingId = '" . $nomorTiket . "'")` di `app/Controllers/Cektiket.php:112`) → assert query mengembalikan >1 baris (seluruh tabel `d_ticketing`, ~38441 baris) alih-alih 0 baris untuk nomor tiket tidak valid
  - Uji juga payload sama terhadap `ambilSatu('d_archive', "archiveTrackingId = '" . $nomorTiket . "' AND ...")` (`Cektiket.php:119`)
  - Uji jalur `Login::savetiket()` → `eult_auto_increment('d_archive', 'archiveId', $arsipId, "archiveTrackingId='" . $idTiket . "'")` (`Login.php:128`) dan readback `byId("ticketTrackingId = '" . $idTiket . "'")` (`Login.php:168`)
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (ini benar — membuktikan bug ada: query mengembalikan baris tambahan/seluruh tabel akibat injeksi)
  - Dokumentasikan counterexample yang ditemukan (contoh: "byId dengan payload OR mengembalikan 38441 baris, bukan 0")
  - Tandai task selesai setelah test ditulis, dijalankan, dan kegagalan terdokumentasi
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2. Tulis test properti preservasi K1 (SEBELUM implementasi fix)
  - **Property 2: Preservation** - Query Valid Tetap Berfungsi Normal
  - **IMPORTANT**: Ikuti metodologi observation-first
  - Observasi pada kode BELUM diperbaiki: `nomorTiket`/`idTiket` valid yang benar-benar ada di `d_ticketing`/`d_archive` (tanpa payload SQLi) → catat hasil `rating()` (insert/update `d_rating`, email `PengirimEmail::selesai()`) dan `savetiket()` (generate `archiveId` berurutan, insert `d_ticketing`/`d_archive`, email `PengirimEmail::buat()`)
  - Tulis property-based test: _for any_ string yang TIDAK mengandung metacharacter SQL dan benar-benar cocok record existing, hasil query SHALL identik dengan hasil sebelum fix
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test LULUS pada kode belum diperbaiki (mengonfirmasi baseline behavior yang harus dipertahankan)
  - Tandai task selesai setelah test ditulis, dijalankan, dan lulus pada kode belum diperbaiki
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 3. Fix K1: Ganti kondisi WHERE string mentah dengan array binding
  - [x] 3.1 Implementasikan fix pada `Cektiket::rating()`
    - Ganti `$this->tiket->byId("ticketTrackingId = '" . $nomorTiket . "'")` menjadi `$this->tiket->byId(['ticketTrackingId' => $nomorTiket])` (`app/Controllers/Cektiket.php`)
    - Ganti `ambilSatu('d_archive', "archiveTrackingId = '" . $nomorTiket . "' AND (archiveJenis = 'OUTPUT' or archiveJenis = 'TTD')")` menjadi kondisi array (`['archiveTrackingId' => $nomorTiket]`) dikombinasikan dengan `whereIn('archiveJenis', ['OUTPUT', 'TTD'])` pada level query builder
    - _Bug_Condition: input.source IN ['Cektiket::rating'] AND input.kondisiType == 'string' AND containsUserControlledValue(input.kondisi) AND NOT isParameterBound(input.kondisi)_
    - _Expected_Behavior: query HANYA mengembalikan baris exact-match, 0 baris untuk nilai tidak ada, tidak pernah SQL error akibat metacharacter_
    - _Preservation: nomorTiket valid tetap mengembalikan data rating/tiket yang sesuai, insert/update d_rating dan email selesai() tidak berubah_
    - _Requirements: 2.1, 2.2_

  - [x] 3.2 Implementasikan fix pada `Login::savetiket()` dan helper terkait
    - Ganti `byId("ticketTrackingId = '" . $idTiket . "'")` (readback pasca-insert) menjadi `byId(['ticketTrackingId' => $idTiket])` (`app/Controllers/Login.php`)
    - Ubah signature `eult_auto_increment()` agar menerima kondisi sebagai `array` (`function eult_auto_increment(string $tabel, string $kolom, string $nip, array $kondisi): string`) dan update caller: `eult_auto_increment('d_archive', 'archiveId', $arsipId, ['archiveTrackingId' => $idTiket])` (`app/Helpers/eult_kode_helper.php`)
    - Verifikasi `ModelMaster::getByLastId()` menerima `array` dari seluruh caller baru; pertahankan dukungan `string` HANYA untuk caller internal yang tidak menerima input publik (agar Preservation 3.3 tidak dilanggar), tandai deprecated-for-public-input pada docblock (`app/Models/ModelMaster.php`)
    - _Bug_Condition: input.source IN ['Login::savetiket', 'Login::index-readback'] AND input.kondisiType == 'string' AND containsUserControlledValue(input.kondisi) AND NOT isParameterBound(input.kondisi)_
    - _Expected_Behavior: query HANYA mengembalikan baris exact-match, tidak pernah SQL error akibat metacharacter_
    - _Preservation: archiveId berurutan tetap benar, method model lain yang memakai escapeString() manual (getDisposisiById(), dll) tidak disentuh_
    - _Requirements: 2.3, 2.4, 2.5_

  - [x] 3.3 Verifikasi test eksplorasi bug condition sekarang lulus
    - **Property 1: Expected Behavior** - SQL Injection via Kondisi String Mentah
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 1 — JANGAN tulis test baru
    - Test dari task 1 mengenkode expected behavior; ketika lulus, ini mengonfirmasi expected behavior terpenuhi
    - Jalankan test eksplorasi bug condition dari langkah 1
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi bug telah diperbaiki — payload SQLi diperlakukan sebagai literal string)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 3.4 Verifikasi test preservasi masih lulus
    - **Property 2: Preservation** - Query Valid Tetap Berfungsi Normal
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 2 — JANGAN tulis test baru
    - Jalankan test properti preservasi dari langkah 2
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi tidak ada regresi pada query valid)
    - Konfirmasi seluruh test masih lulus setelah fix (tidak ada regresi)
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 4. Checkpoint Klaster 1 - Pastikan seluruh test K1 lulus
  - Pastikan seluruh test lulus (eksplorasi + preservasi), tanyakan ke user jika ada pertanyaan
  - Klaster 1 adalah prasyarat pola aman yang dipakai ulang di Klaster 2 (K2/T4 menerapkan array binding yang sama pada method yang sama) — pastikan pola ini solid sebelum lanjut

---

## Klaster 2: Kepemilikan Berkas & Rating (K2, T4, M2)

- [x] 5. Tulis test eksplorasi bug condition K2/M2 (endpoint download tanpa validasi kepemilikan)
  - **Property 1: Bug Condition** - IDOR pada Endpoint Download Berkas
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample yang mendemonstrasikan IDOR pada `Cektiket::loadpdf()`, `Cektiket::loadattach()`, `Validitas::loadpdf()` (publik) dan `Ticketing::loadpdf()`/`Ticketing::loadattach()` (admin non-owner)
  - **Scoped PBT Approach**: Untuk kunci terenkripsi arbitrer (Testing Strategy design.md: "K2/T4: kunci terenkripsi arbitrer vs tidak match") — generate kunci hasil `encode()` dari nomor tiket acak (valid ada di DB / tidak ada) serta kunci acak tidak valid; untuk nama file mentah gunakan kasus konkret dari format predictable (`TIKET_{arsipId}_{timestamp}.pdf`, `CHAT_{idTiket}_{timestamp}.{ext}`)
  - `GET cektiket/loadpdf/{namaFileTebakan}` tanpa memegang kunci terenkripsi apa pun pada kode asli → assert 200 dengan konten file tersaji (`app/Controllers/Cektiket.php:145-152`)
  - `GET cektiket/loadattach/{namaFileTebakan}` milik pemohon lain pada kode asli → assert 200 tersaji (`Cektiket.php:155-163`)
  - `GET validitas/loadpdf/{namaFileTebakan}` pada kode asli → assert 200 tersaji (`app/Controllers/Validitas.php:39-46`)
  - Sebagai staf non-admin unit A, `GET ticketing/loadpdf/{namaFileUnitB}` pada kode asli → assert 200 tersaji tanpa ownership check (`app/Controllers/Ticketing.php:1083-1104`)
  - Verifikasi path traversal (`../../.env`) tetap diblokir `basename()` pada kode asli (bukan bagian bug, tapi baseline yang perlu dicatat)
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan IDOR: file tersaji tanpa validasi kepemilikan/kunci)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.5, 1.6, 1.7, 1.8, 1.27, 1.28_

- [x] 6. Tulis test eksplorasi bug condition T4 (rating tanpa validasi kepemilikan)
  - **Property 1: Bug Condition** - Rating Tanpa Validasi Kepemilikan Tiket
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample bahwa `Cektiket::rating()` beroperasi murni dari `nomorTiket` POST body tanpa decode kunci apa pun
  - POST ke `rating()` dengan `nomorTiket` tebakan/diketahui (tanpa kunci terenkripsi apa pun) pada kode asli → assert rating tersimpan di `d_rating` DAN email `PengirimEmail::selesai()` terpicu ke `ticketEmail` tiket tersebut (`app/Controllers/Cektiket.php:106-125`)
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan siapa pun yang menebak nomorTiket dapat memicu rating+email tanpa memegang kunci)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.23, 1.24_

- [x] 7. Tulis test properti preservasi K2/T4/M2 (SEBELUM implementasi fix)
  - **Property 2: Preservation** - Pemilik Sah Tetap Dapat Mengunduh dan Rating
  - **IMPORTANT**: Ikuti metodologi observation-first
  - Observasi pada kode BELUM diperbaiki: pemegang kunci sah mengklik link `$output_url`/`$load_attach` dari `Cektiket::index()`/`detail_user.php` → catat file tersaji, mime-type, header `Content-Disposition`/`Content-Type`
  - Observasi: `Validitas::index($kunci)` dengan kunci valid hasil scan QR → catat halaman validasi + link download PDF berfungsi
  - Observasi: staf admin/superadmin/staf dengan disposisi sah mengakses `Ticketing::loadpdf()`/`loadattach()` untuk tiket tanggung jawabnya → catat file tersaji tanpa gangguan
  - Observasi: pemegang kunci sah rating tiketnya sendiri → catat insert/update `d_rating` (logika `empty($cek) ? tambah() : ubah()`), email `selesai()` + lampiran, pesan sukses `eult_message_kirim()`
  - Tulis property-based test: _for any_ kunci terenkripsi milik pemegang sah (atau staf dengan ownership sah) di mana file/tiket benar-benar berasosiasi, hasil SHALL identik dengan sebelum fix
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test LULUS pada kode belum diperbaiki (mengonfirmasi baseline behavior)
  - _Requirements: 3.4, 3.5, 3.6, 3.7, 3.21, 3.22, 3.25, 3.26_

- [x] 8. Fix K2: Kepemilikan berkas via kunci terenkripsi pada endpoint publik
  - [x] 8.1 Implementasikan fix pada `Cektiket::loadpdf()`
    - Ubah signature dari `loadpdf(string $namaFile)` menjadi `loadpdf(string $kunci)`
    - Decode `$kunci` via `Enkripsi::decode()` untuk memperoleh `archiveTrackingId`
    - Query `d_archive` dengan kondisi array (`['archiveTrackingId' => $id, 'archiveJenis' => 'OUTPUT']`) untuk memperoleh `archiveFile` yang benar-benar terasosiasi (terapkan pola array binding K1 sekaligus)
    - HANYA serve file jika hasil query ditemukan DAN `basename($hasil['archiveFile'])` sama dengan file fisik yang disajikan
    - Kunci tidak valid/decode gagal/tidak ada file cocok → response 403/404, JANGAN panggil `file_get_contents()` sama sekali
    - Update caller `Cektiket::index()` (`$output_url`) agar mengirim `$kunci` (bukan `archiveFile` mentah) sebagai path segment
    - Pertahankan `basename()` sebagai lapisan tambahan (bukan satu-satunya kontrol)
    - _Bug_Condition: input.endpoint == 'Cektiket::loadpdf' AND input.pathSegment == rawFileName AND NOT ownershipValidated(input.pathSegment)_
    - _Expected_Behavior: HANYA menyajikan konten ketika kunci dapat didecode/divalidasi DAN hasilnya benar-benar berasosiasi dengan berkas; kunci tidak valid → 403/404_
    - _Preservation: pemegang kunci sah tetap dapat download PDF; header Content-Type/Content-Disposition sama seperti sebelumnya_
    - _Requirements: 2.6, 2.7, 2.10_

  - [x] 8.2 Implementasikan fix pada `Cektiket::loadattach()`
    - Ubah signature dari `loadattach(string $namaFile)` menjadi menerima `$kunci` DAN `$namaFile` sebagai dua segment (route `cektiket/loadattach/(:kunci)/(:namaFile)`)
    - Decode `$kunci` via `Enkripsi::decode()` untuk memperoleh identitas tiket
    - Query `d_replies` dengan kondisi (`['repliesTicketId' => $id]`) dan validasi `$namaFile` benar-benar muncul pada `repliesFile` milik `$id` hasil decode
    - HANYA serve jika kecocokan valid; kunci/kombinasi tidak valid → 403/404
    - Update caller di view (`$load_attach` pada `detail_user.php`) agar menyertakan `$kunci` halaman sebagai segment tambahan sebelum nama file
    - Pertahankan `basename()` sebagai lapisan tambahan
    - _Bug_Condition: input.endpoint == 'Cektiket::loadattach' AND input.pathSegment == rawFileName AND NOT ownershipValidated(input.pathSegment)_
    - _Expected_Behavior: HANYA menyajikan konten ketika kunci+namaFile valid dan berasosiasi dengan repliesFile yang benar_
    - _Preservation: pemegang kunci sah tetap dapat mengklik link lampiran chat dari riwayat balasan_
    - _Requirements: 2.8, 2.10_

  - [x] 8.3 Implementasikan fix pada `Validitas::loadpdf()`
    - Ubah signature dari `loadpdf(string $namaFile)` menjadi `loadpdf(string $kunci)`
    - Decode `$kunci`, query `d_archive` dengan `archiveTrackingId` hasil decode dan `archiveJenis` yang relevan
    - HANYA serve jika kecocokan valid; kunci tidak valid → 403/404
    - Route `validitas/loadpdf/(:any)` tetap satu segment (kunci); update caller pemanggil link di view agar mengirim kunci yang sama dengan halaman validitas
    - Pertahankan `basename()` sebagai lapisan tambahan
    - _Bug_Condition: input.endpoint == 'Validitas::loadpdf' AND input.pathSegment == rawFileName AND NOT ownershipValidated(input.pathSegment)_
    - _Expected_Behavior: HANYA menyajikan konten ketika kunci dapat didecode dan berasosiasi dengan archiveFile yang benar_
    - _Preservation: pemegang kunci valid hasil scan QR tetap dapat download PDF terkait_
    - _Requirements: 2.9, 2.10_

  - [x] 8.4 Verifikasi test eksplorasi bug condition K2 sekarang lulus
    - **Property 1: Expected Behavior** - IDOR pada Endpoint Download Berkas
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 5 (bagian K2 publik) — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi endpoint publik memvalidasi kepemilikan via kunci)
    - _Requirements: 2.6, 2.7, 2.8, 2.9, 2.10_

- [x] 9. Fix T4: Rating memakai kunci terenkripsi (bukan nomorTiket mentah)
  - [x] 9.1 Implementasikan fix pada `Cektiket::rating()`
    - Ubah signature menjadi `rating(string $kunci = '')` — parameter `nomorTiket` dari POST body TIDAK lagi dipakai sebagai sumber identifikasi
    - Decode `$kunci` via `Enkripsi::decode()` di awal method → `$nomorTiket`
    - Jika decode gagal (`empty($nomorTiket)`), hentikan pemrosesan dan return response error TANPA insert/update/email apa pun
    - Ganti SELURUH pemakaian variabel `nomorTiket` (sebelumnya dari `getPost('nomorTiket')`) dengan hasil decode ini — untuk `byId()`, `ambilSatu('d_rating', ...)`, `tambah()`/`ubah()`, `ambilSatu('d_archive', ...)`
    - Terapkan fix K1 (array binding) bersamaan pada method ini
    - Update `app/Views/pages/ticketing/detail_user.php`: form/AJAX submit rating menyertakan `$kunci` halaman (sama seperti dipakai `cetakterima`) sebagai pengganti `nomorTiket` mentah
    - _Bug_Condition: input.endpoint == 'Cektiket::rating' AND input.identitySource == 'POST.nomorTiket'_
    - _Expected_Behavior: seluruh operasi (byId, ambilSatu d_rating, tambah/ubah, ambilSatu d_archive, email) SHALL menggunakan HANYA nomorTiket hasil decode; kunci tidak valid → hentikan tanpa side-effect_
    - _Preservation: pemegang kunci sah tetap bisa rating tiketnya sendiri (insert/update d_rating, email selesai() + lampiran, pesan sukses) persis seperti sebelumnya_
    - _Requirements: 2.34, 2.35, 2.36, 2.37_

  - [x] 9.2 Verifikasi test eksplorasi bug condition T4 sekarang lulus
    - **Property 1: Expected Behavior** - Rating Tanpa Validasi Kepemilikan Tiket
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 6 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi rating hanya beroperasi atas hasil decode kunci, kunci tidak valid tidak memicu side-effect apa pun)
    - _Requirements: 2.34, 2.35, 2.36, 2.37_

- [x] 10. Fix M2: Ownership check pada `Ticketing::loadpdf()`/`loadattach()` (admin)
  - [x] 10.1 Implementasikan fix pada `Ticketing::loadpdf()`/`Ticketing::loadattach()`
    - Tambahkan pengecekan ownership SEBELUM serve: ambil `logged_in` session (`susrSgroupNama`)
    - Jika grup `ADMIN`/`OPERATOR*` → lanjutkan tanpa batasan tambahan (konsisten pola existing `Ticketing.php:89-91`)
    - Jika grup lain → decode/cari tiket pemilik file terkait (lookup `archiveTrackingId`/`repliesTicketId` dari nama file), lalu cek disposisi/unit staf terhadap tiket tersebut menggunakan `s_user_group_unit`/`d_disposisi` yang SUDAH ADA (bukan mekanisme baru)
    - Jika tidak match → 403, JANGAN sajikan konten berkas
    - Filter `auth` itu sendiri TIDAK diubah — hanya menambah validasi di dalam body controller setelah filter lolos
    - _Bug_Condition: input.endpoint IN ['Ticketing::loadpdf', 'Ticketing::loadattach'] AND input.staffAuthenticated == true AND NOT staffHasOwnershipOfAssociatedTicket(input.staffGroup, input.fileOwnerTicket)_
    - _Expected_Behavior: staf non-owner (bukan ADMIN/OPERATOR, tanpa disposisi) → 403; staf admin/operator/dengan disposisi sah → tetap 200_
    - _Preservation: staf admin/operator/staf dengan disposisi sah tetap akses berkas tanpa gangguan; mekanisme filter auth tidak berubah_
    - _Requirements: 2.11, 2.40, 2.41_

  - [x] 10.2 Verifikasi test eksplorasi bug condition M2 sekarang lulus
    - **Property 1: Expected Behavior** - IDOR Admin loadpdf/loadattach
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 5 (bagian M2 admin) — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (staf non-owner unit A ditolak 403 mengakses file unit B; staf admin tetap 200)
    - _Requirements: 2.40, 2.41_

  - [x] 10.3 Verifikasi seluruh test preservasi Klaster 2 masih lulus
    - **Property 2: Preservation** - Pemilik Sah Tetap Dapat Mengunduh dan Rating
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 7 — JANGAN tulis test baru
    - Jalankan test properti preservasi dari langkah 7 (download publik, download admin, rating)
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi tidak ada regresi pada pemilik/staf sah)
    - Konfirmasi seluruh test masih lulus setelah fix (tidak ada regresi)
    - _Requirements: 3.4, 3.5, 3.6, 3.7, 3.21, 3.22, 3.25, 3.26_

- [x] 11. Checkpoint Klaster 2 - Pastikan seluruh test K2/T4/M2 lulus
  - Pastikan seluruh test lulus (eksplorasi K2 + eksplorasi T4 + eksplorasi M2 + preservasi), tanyakan ke user jika ada pertanyaan
  - Verifikasi route baru (`cektiket/loadpdf/(:kunci)`, `cektiket/loadattach/(:kunci)/(:namaFile)`, `validitas/loadpdf/(:kunci)`, `cektiket/rating/(:any)` atau field POST `kunci`) terdaftar dengan benar di `app/Config/Routes.php`

---

## Klaster 3: Pertahanan Perimeter (T1, T2, T3, M1, M3)

- [x] 12. Tulis test eksplorasi bug condition T1 (CSRF/filter global dinonaktifkan)
  - **Property 1: Bug Condition** - Filter Keamanan Global Dinonaktifkan
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample bahwa POST ke endpoint sensitif diproses tanpa CSRF
  - POST ke `login/savetiket` tanpa header/field CSRF apa pun pada kode asli → assert request diproses PENUH (insert `d_ticketing`, email terkirim) meski tidak ada token
  - Verifikasi form `app/Views/layouts/login.php` tidak mengandung `csrf_field()`/`csrf_token()` apa pun
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan tidak ada validasi CSRF)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.15, 1.16, 1.17_

- [x] 13. Tulis test eksplorasi bug condition T2 (captcha kosmetik + tidak ada rate limit)
  - **Property 1: Bug Condition** - Captcha Kosmetik Tanpa Rate Limiting
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample captcha terbaca dari DOM dan tidak ada rate limit
  - Assert HTML response halaman login mengandung nilai captcha polos dalam `<span class="captcha-display">` (dapat dibaca tanpa OCR)
  - Kirim 1000 request POST beruntun ke `otentifikasi` dalam 60 detik dari IP sama pada kode asli → assert SELURUHNYA diproses tanpa 429 (tidak ada `Config/Throttle.php` di codebase — konfirmasi via pencarian)
  - Kirim 1000 request POST sukses beruntun ke `login/savetiket` → assert 1000 email `PengirimEmail::buat()` terkirim tanpa hambatan
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan captcha kosmetik dan tidak ada rate limit)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.18, 1.19, 1.20_

- [x] 14. Tulis test eksplorasi bug condition T3 (cookie captcha bypass)
  - **Property 1: Bug Condition** - Cookie captcha_code Membypass Validasi
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample bahwa cookie klien mengalahkan session server
  - **Scoped PBT Approach**: Generate string arbitrer sebagai nilai cookie `captcha_code` independen dari session (Testing Strategy design.md: "T3: string cookie arbitrer")
  - Set cookie `captcha_code=ABCD` (berbeda dari nilai session captcha yang sebenarnya, misal `XYZ9`), kirim `captcha=ABCD` pada kode asli (`eult_captcha_check()` di `app/Helpers/eult_captcha_helper.php:36-40`) → assert fungsi mengembalikan `true` (lolos) meski TIDAK cocok session
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan cookie klien dapat membypass validasi session)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.21, 1.22_

- [x] 15. Tulis test eksplorasi bug condition M1 (header keamanan tidak lengkap)
  - **Property 1: Bug Condition** - Header Keamanan Browser Tidak Lengkap
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample bahwa header CSP/X-Frame-Options absen
  - Assert response HTTP halaman apa pun (publik/admin) pada kode asli TIDAK mengandung header `Content-Security-Policy`/`X-Frame-Options` (`$CSPEnabled = false` di `app/Config/App.php:192`)
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan header keamanan absen)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.25, 1.26_

- [x] 16. Tulis test eksplorasi bug condition M3 (enumerasi tiket tanpa rate limit)
  - **Property 1: Bug Condition** - Enumerasi Nomor Tiket Tanpa Rate Limit
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample enumerasi masif nomor tiket tanpa hambatan
  - Kirim 100 (atau 10.000 sesuai skenario bugfix.md) request enumerasi nomor tiket beruntun ke `Login::cektiket()` (`app/Controllers/Login.php:56-81`) pada kode asli → assert SELURUHNYA diproses dan pesan "ditemukan"/"tidak ditemukan" tetap dapat dibedakan tanpa hambatan
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan tidak ada rate limit pada enumerasi)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.29, 1.30_

- [x] 17. Tulis test properti preservasi Klaster 3 (SEBELUM implementasi fix)
  - **Property 2: Preservation** - Request Sah, Captcha Benar, Aset CSP, Pengguna Wajar Tidak Terblokir
  - **IMPORTANT**: Ikuti metodologi observation-first
  - Observasi pada kode BELUM diperbaiki: request AJAX dengan struktur existing (tanpa token, karena belum ada mekanisme CSRF) ke `login/savetiket`/`login/cektiket`/`cektiket/save_replies`/`cektiket/rating` → catat struktur respons JSON (`status`, `message`, `redirect_url`, `new_captcha`)
  - Observasi: captcha benar (dibaca dari DOM saat ini) → catat diterima; refresh captcha (`refresh-captcha`) → catat captcha baru dihasilkan
  - Observasi: pengguna sah membuat tiket/login dalam frekuensi normal → catat tidak ada hambatan
  - Observasi: captcha benar sesuai session (tanpa cookie apa pun diset) → catat lolos; captcha salah → catat gagal
  - Observasi: aset statis (CSS/JS/gambar/font) yang dimuat halaman existing → catat daftar sumber daya (untuk dipakai sebagai whitelist CSP nanti)
  - Observasi: pengguna sah cek tiket sendiri dalam frekuensi wajar → catat pesan "ditemukan" + redirect; salah ketik sesekali → catat "tidak ditemukan" tanpa terblokir
  - Tulis property-based test yang menangkap pola-pola di atas sebagai baseline yang harus dipertahankan pasca-fix
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test LULUS pada kode belum diperbaiki (mengonfirmasi baseline)
  - _Requirements: 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20, 3.23, 3.24, 3.27, 3.28_

- [x] 18. Fix T1: Aktifkan CSRF, secureheaders, honeypot, invalidchars
  - [x] 18.1 Aktifkan filter global di `app/Config/Filters.php`
    - Uncomment `'csrf'`, `'invalidchars'` di `$globals['before']`
    - Uncomment `'secureheaders'` di `$globals['after']`
    - JANGAN tambahkan `except`/exclude URI apa pun kecuali benar-benar diperlukan DAN dikonfirmasi user terlebih dahulu (default: tidak ada exclude)
    - _Bug_Condition: input.method == 'POST' AND input.route IN [...] AND NOT globalFilterActive('csrf') AND NOT formContainsCsrfToken(input.origin)_
    - _Requirements: 2.21, 2.25_

  - [x] 18.2 Tambahkan CSRF token pada form dan JavaScript AJAX
    - Tambahkan `<?= csrf_field() ?>` di dalam setiap elemen `<form>` yang mengirim POST pada `app/Views/layouts/login.php`
    - Sebelum setiap `$.ajax()`/`fetch()` POST ke `login/savetiket`, `login/cektiket`, `cektiket/save_replies`, `cektiket/rating`, tambahkan header `X-CSRF-TOKEN` dengan nilai token terkini
    - Setelah setiap submit sukses, update nilai token dari respons server (mengikuti `$regenerate = true`)
    - Verifikasi kompatibilitas field honeypot otomatis CI4 dengan struktur form existing setelah `'honeypot'` diaktifkan
    - _Expected_Behavior: request dengan token CSRF valid diproses normal; tanpa token ditolak graceful_
    - _Requirements: 2.22, 2.23, 2.26_

  - [x] 18.3 Tambahkan graceful handler untuk kegagalan CSRF pada request AJAX
    - Tambahkan handler untuk `CSRFException`/kegagalan validasi CSRF pada request AJAX (deteksi via header `X-Requested-With`/`Accept: application/json`) di `app/Controllers/BaseController.php` atau exception handler khusus
    - Kembalikan JSON graceful (`{'status': 'danger', 'message': '...'}`) alih-alih HTML error page mentah
    - **Implementasi**: `app/Exceptions/AjaxSecurityExceptionHandler.php` (baru, implements `CodeIgniter\Debug\ExceptionHandlerInterface`), didaftarkan via `Config\Exceptions::handler()` (`app/Config/Exceptions.php`) — HANYA aktif ketika exception adalah `CodeIgniter\Security\Exceptions\SecurityException` DAN request AJAX (`$request->isAJAX()`) ATAU header `Accept` mengandung `application/json`; selain itu delegasi penuh ke `CodeIgniter\Debug\ExceptionHandler` bawaan (non-AJAX tidak berubah)
    - **Temuan empiris penting** (diverifikasi via curl ke server live SEBELUM implementasi): behavior default CI4 untuk request AJAX yang ditolak CSRF BUKAN halaman HTML penuh seperti diasumsikan semula — `ExceptionHandler` bawaan sudah mendeteksi `Accept` tanpa `text/html` dan mengembalikan `Content-Type: application/json`, TAPI body-nya literal `""` (2 byte, kosong) karena `display_errors` off — tidak berguna bagi client JS. Fix ini mengganti body kosong tersebut dengan `{"status": "danger", "message": "..."}` sesuai kontrak `Login::savetiket()`/`Login::cektiket()` existing.
    - **Test verifikasi baru**: `tests/Bugfix/T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` (cURL-ke-server-live, karena `SecurityException` yang lolos filter global hanya dapat memicu `set_exception_handler`/`Config\Exceptions::handler()` pada proses server sungguhan, TIDAK PERNAH pada dispatch in-process `FeatureTestTrait` — PHPUnit menangkapnya sendiri sebagai test Error) — 3 test, LULUS: (a) AJAX tanpa CSRF → 403 JSON `{status:"danger", message:...}` pada `cektiket/rating` dan `login/savetiket`; (b) non-AJAX tanpa CSRF → 403 HTML `Whoops!` penuh tidak berubah (regression guard)
    - **Catatan untuk task 18.4**: `T1CsrfExplorationTest.php` (task 12) memiliki 2 kegagalan PRA-EXISTING (dikonfirmasi TIDAK disebabkan fix 18.3 — diverifikasi dengan/tanpa perubahan `Exceptions.php`, hasil identik) yang tidak dapat diperbaiki dari scope 18.3: (1) `testPostSavetiketTanpaTokenCsrfDitolakDenganResponsErrorDanTidakAdaInsert` — ERROR karena `SecurityException` lolos tidak tertangkap saat dispatch in-process `FeatureTestTrait` (keterbatasan struktural, bukan bug — lihat docblock `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` untuk investigasi lengkap); (2) `testFormLoginTidakMengandungPemanggilanCsrfFieldApaPun` — FAIL karena task 18.2 sudah menambahkan `csrf_field()` ke `login.php` (test ini sengaja membuktikan KETIDAKHADIRAN token pra-fix, kini berlawanan by design, presedan sama dengan `T4RatingOwnershipExplorationTest.php` yang digantikan `T4RatingHttpIntegrationTest.php`)
    - _Bug_Condition: request AJAX tanpa token CSRF valid_
    - _Expected_Behavior: response JSON graceful, dapat ditangani JS existing tanpa exception 500 mentah_
    - _Preservation: struktur respons JSON existing (status, message, redirect_url, new_captcha) tidak berubah kontraknya_
    - _Requirements: 2.24_

  - [x] 18.4 Verifikasi test eksplorasi bug condition T1 sekarang lulus
    - **Property 1: Expected Behavior** - Filter Keamanan Global Dinonaktifkan
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 12 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (POST tanpa token CSRF ditolak graceful, tidak diproses penuh)
    - **Redefinisi cakupan checkpoint (Opsi (b), presedan IDENTIK K1's task 3.3 dan T4's task 9.2)**: `T1CsrfExplorationTest.php` (task 12) TETAP GAGAL (1 Error, 1 Failure dari 3 test) setelah fix 18.1-18.3, diverifikasi ulang langsung (bukan dipercaya dari klaim 18.3 mentah-mentah) — `vendor/bin/phpunit tests/Bugfix/T1CsrfExplorationTest.php --testdox` menghasilkan persis: (1) `testPostSavetiketTanpaTokenCsrfDitolakDenganResponsErrorDanTidakAdaInsert` — **Error** (bukan Failure): `CodeIgniter\Security\Exceptions\SecurityException` lolos filter csrf tertangkap LANGSUNG oleh PHPUnit sebagai test Error saat dispatch in-process `FeatureTestTrait::call()` (`T1CsrfExplorationTest.php:228`), SEBELUM `set_exception_handler()`/`Config\Exceptions::handler()` (mekanisme fix 18.3) sempat berjalan — KETERBATASAN STRUKTURAL in-process dispatch, bukan kegagalan fix 18.3 (identik alasan K1/T4 harus diverifikasi via cURL-ke-server-live, bukan `FeatureTestTrait`); (2) `testFormLoginTidakMengandungPemanggilanCsrfFieldApaPun` — **Failure**: assertion gagal karena HTML hasil render `login.php` SEKARANG mengandung `csrf_field(` (dikonfirmasi dari output PHPUnit: `does not contain "csrf_field("` — assertion negatif ini SEHARUSNYA gagal, karena task 18.2 SUDAH menambahkan `csrf_field()` by design; test ini sengaja membuktikan KETIDAKHADIRAN token pra-fix, kini berlawanan by design, presedan sama dengan `T4RatingOwnershipExplorationTest.php`).
    - `T1CsrfExplorationTest.php` (task 12) DIPERTAHANKAN apa adanya sebagai dokumentasi/regression-guard historis (mendokumentasikan bug T1 SEBELUM fix — kegagalannya pasca-fix pun tetap bermakna sebagai bukti bahwa expected/fixed behavior tercapai lewat jalur lain) — BUKAN gate checkpoint 18.4 lagi. Gate checkpoint 18.4 yang BENAR adalah `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` (task 18.3) — diverifikasi ulang LULUS 3/3 (19 assertions): (a) AJAX tanpa CSRF → 403 JSON graceful pada `cektiket/rating` dan `login/savetiket`; (b) non-AJAX tanpa CSRF → 403 HTML `Whoops!` penuh tidak berubah (regression guard).
    - Regression check lintas Klaster 3/2 dijalankan ulang: `Klaster3PreservationTest.php` + `T4RatingHttpIntegrationTest.php` — LULUS 11/11 (61 assertions), tidak ada regresi akibat dokumentasi ulang ini (tidak ada assertion test manapun yang diubah pada task ini).
    - _Requirements: 2.21, 2.22, 2.23, 2.24, 2.25, 2.26_

- [x] 19. Fix T2/M3: Captcha gambar + rate limiting
  - [x] 19.1 Implementasikan captcha sebagai gambar
    - Tambahkan fungsi baru `eult_captcha_image(string $teks): string` (binary PNG) menggunakan GD (`imagecreate`, `imagestring`/`imagettftext` dengan distorsi garis/noise); deteksi `extension_loaded('gd')` dengan fallback Imagick (`app/Helpers/eult_captcha_helper.php`)
    - `eult_captcha_generate()` TETAP menyimpan nilai ke `session()->set('captcha', ...)` — TIDAK berubah
    - Tambahkan method `captchaImage()` di `Login.php` yang generate/baca teks captcha dari session, render via `eult_captcha_image()`, return response `Content-Type: image/png`
    - Ubah `refreshCaptcha()` agar respons berisi URL gambar (`captcha_image_url`) alih-alih string captcha polos
    - Ubah respons `new_captcha` pada `Otentifikasi::index()`, `Login::savetiket()` dari string captcha menjadi URL endpoint gambar
    - Ganti `<span class="captcha-display">` menjadi `<img src="{captcha_image_url}">` di `app/Views/layouts/login.php`
    - _Bug_Condition: input.type == 'render' AND capchaRenderedAsPlainDomText(input)_
    - _Expected_Behavior: nilai captcha session TIDAK PERNAH terkirim plaintext ke klien (Property 13); hanya URL gambar_
    - _Preservation: captcha benar (dari gambar) tetap diterima; refresh captcha tetap berfungsi (beda format saja)_
    - **Implementasi**: `eult_captcha_image()`/`eult_captcha_image_gd()`/`eult_captcha_image_imagick()`/`eult_captcha_cari_font_imagick()` baru (`app/Helpers/eult_captcha_helper.php`) — `eult_captcha_generate()`/`eult_captcha_check()` TIDAK disentuh sama sekali (scope task 20/T3 terpisah). Route baru `login/captcha_image` (GET, `Login::captchaImage()`) di `app/Config/Routes.php`. `Login::index()` tidak lagi mengirim `$captcha` plaintext ke view — mengirim `captcha_image_url` (URL dengan nonce `?t=timestamp` cache-busting). `Login::captchaImage()` baru: membaca `session()->get('captcha')` CURRENT (generate hanya bila belum ada — captcha yang divalidasi harus sama dengan yang ditampilkan), render via `eult_captcha_image()`, return `setHeader('Content-Type', 'image/png')->setBody(...)` (API `setContentType()` TIDAK ADA di framework ini — dikonfirmasi via pembacaan `vendor/codeigniter4/framework/system/HTTP/MessageTrait.php`; pola `setHeader('Content-Type', ...)` sudah dipakai `Ticketing::loadimage()` existing). `Login::refreshCaptcha()` dan 3× `new_captcha` pada `Login::savetiket()` serta 3× `new_captcha` pada `Otentifikasi::index()` diubah isinya dari `eult_captcha_generate(4)` (string plaintext) menjadi URL gambar — NAMA FIELD `new_captcha` DIPERTAHANKAN (hanya isinya berubah), sesuai instruksi task text; endpoint `refresh_captcha` sendiri memakai field baru `captcha_image_url` (bukan `captcha`) karena tidak ada kontrak nama field lama yang harus dipertahankan di titik itu. 2 lokasi `<span class="captcha-display">` di `app/Views/layouts/login.php` (baris ~2045, ~2186) diganti `<img src="<?= esc($captcha_image_url) ?>" class="captcha-display" alt="Captcha">` — CSS `.captcha-display` ditambah `display: block; height: 50px` (dan `height: 44px` pada breakpoint mobile) agar dimensi gambar konsisten, tidak menghapus properti CSS teks lama (font-family/color/dll — tidak berlaku pada `<img>` tapi tidak invalid, browser ignore). `public/assets/js/pages/custom/pages/user/login.js`: fungsi `refreshCaptcha(captchaCode)` diubah jadi terima URL dan `.attr('src', ...)` bukan `.text(...)`; callback `.refresh-captcha` click handler dibaca field `response.captcha_image_url` (bukan `response.captcha`) — selector `$('.captcha-display')` otomatis mengenai KEDUA instance form (buat tiket + login) tanpa penyesuaian tambahan.
    - **Temuan empiris penting**: wrapper shell `<!DOCTYPE html>...<body>{...}</body></html>` yang sudah didokumentasikan test lain untuk body JSON pada dispatch `FeatureTestTrait` JUGA berlaku untuk body BINARY (PNG) — dikonfirmasi via investigasi langsung (`login/captcha_image` under `FeatureTestTrait` mengembalikan byte pertama `<` bukan `\x89PNG`, meski Content-Type header tetap benar `image/png`). Konsekuensi: assertion magic-bytes PNG WAJIB memakai cURL-ke-server-live (pola diadaptasi dari `K1SqlInjectionHttpIntegrationTest::postKeServerLive()` untuk GET), sedangkan assertion lain (absensi plaintext di HTML/JSON) AMAN memakai `FeatureTestTrait`.
    - **Bug ditemukan & diperbaiki selama implementasi (bukan regresi dari fix lain)**: fallback Imagick awal (`eult_captcha_image_imagick()`) memakai `ImagickDraw::annotation()` tanpa `setFont()` eksplisit — crash `ImagickException: unable to read font` di environment ini karena tidak ada default font ter-resolve otomatis oleh Freetype. Diperbaiki dengan `eult_captcha_cari_font_imagick()` (mencoba beberapa path font TTF umum Linux/macOS, memakai yang pertama ditemukan; `null` bila tidak ada, pemanggil tetap mencoba `annotation()` tanpa `setFont()` untuk server yang punya default font). Diverifikasi ulang via `php -r` langsung: kedua jalur (GD utama DAN Imagick fallback) menghasilkan PNG valid (magic bytes `\x89PNG` terkonfirmasi pada keduanya).
    - **Regresi ditemukan & diperbaiki pada test PRA-EXISTING** (bukan test eksplorasi Klaster 3, di luar scope redefinisi task 18.4/19.3): `tests/Bugfix/Klaster3PreservationTest.php::testRefreshCaptchaMenghasilkanCaptchaBaru` (FAILURE, menuntut field `"captcha"` yang sudah diganti `"captcha_image_url"`) dan `::testHalamanLoginTetapMerenderDanMengandungAsetStatisYangDikenalUntukWhitelistCsp` (ERROR `Undefined variable $captcha_image_url`, memanggil `view('layouts/login', ['captcha' => ...])` dengan key lama) — KEDUANYA diperbaiki menyesuaikan kontrak baru (bukan melemahkan assertion; docblock ORIGINAL kedua test ini SUDAH eksplisit mengantisipasi perubahan format captcha pasca-task-19, persis presedan `T1CsrfExplorationTest.php` pasca-18.2) — diverifikasi ulang LULUS 6/6 (34 assertions) setelah perbaikan. `tests/unit/AlurFondasiTest.php::testRefreshCaptchaMenghasilkanKode` (field `captcha` lama) juga disesuaikan serupa (bukan bagian spec Klaster manapun, tapi test fondasi lintas-fitur yang overlap langsung) — kini memverifikasi field `captcha_image_url` berpola URL yang benar (LULUS).
    - **Regresi PRA-EXISTING dikonfirmasi TIDAK disebabkan fix ini** (diverifikasi via `git stash` isolasi + reproduksi ulang dengan `<span>` asli dipertahankan): `tests/unit/AlurFondasiTest.php::testLoginCaptchaSalahDitolak` (POST `/otentifikasi` tanpa token CSRF → `SecurityException`) — sudah gagal identik SEBELUM task 19.1 disentuh, disebabkan aktivasi CSRF task 18 pada test unit lama yang belum diperbarui menyertakan token; di luar scope task 19.1 untuk diperbaiki.
    - **Test verifikasi baru**: `tests/Bugfix/T2CaptchaImageHttpIntegrationTest.php` — 3 test, LULUS (17 assertions): (a) `login/captcha_image` (cURL live) mengembalikan `Content-Type: image/png` + body diawali magic bytes PNG standar (`\x89PNG\r\n\x1a\n`), ukuran > 100 byte; (b) HTML `Login::index()` (dispatch HTTP sungguhan, bukan render langsung) TIDAK mengandung nilai captcha session sesungguhnya di mana pun, `<span class="captcha-display">` sudah tidak ada, `<img class="captcha-display">` ada; (c) JSON `login/refresh_captcha` tidak lagi mengandung field `"captcha":` (regex membedakan dari `"captcha_image_url"`), field `captcha_image_url` ada berisi URL non-kosong, nilai captcha session tidak muncul verbatim di body.
    - Regression check lintas Klaster 2/3 dijalankan ulang: `Klaster3PreservationTest.php` (6/6, 34 assertions) + `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` (3/3, 19 assertions) + `T4RatingHttpIntegrationTest.php` (5/5) + `AlurFondasiTest.php` (4/5 — 1 error pra-existing tidak terkait, lihat di atas) — TIDAK ADA regresi baru akibat task 19.1 di luar 2 test yang sudah diperbaiki (didokumentasikan eksplisit di atas).
    - **Catatan untuk task 19.3** (verifikasi ulang test eksplorasi task 13 `T2CaptchaRateLimitExplorationTest.php`): SATU dari 3 test eksplorasi (`testCaptchaTerbacaLangsungDariDomTanpaOcr`, yang me-render `view('layouts/login', ['captcha' => ...])` LANGSUNG tanpa dispatch HTTP) akan mengalami crash TypeError (`Cannot assign CodeIgniter\HTTP\CLIRequest to Security::$request`) pada task 19.3 — dikonfirmasi via investigasi isolasi bahwa crash ini disebabkan `csrf_field()` (baris ~1820, task 18, PRA-EXISTING sebelum task 19.1) yang butuh `IncomingRequest` HTTP context saat dirender di luar dispatch HTTP penuh, SAMA SEKALI TIDAK disebabkan perubahan captcha image task 19.1 (dikonfirmasi ulang dengan `<span>` asli dipertahankan — crash identik tetap terjadi). Test tersebut TIDAK diubah pada task 19.1 ini (di luar scope) — task 19.3 perlu menangani ini (kemungkinan opsi: redefinisi cakupan checkpoint seperti presedan 18.4, atau perbaikan lain sesuai keputusan saat itu).
    - _Requirements: 2.27, 2.28_

  - [x] 19.2 Implementasikan rate limiting per-IP
    - **KOREKSI PREMIS**: task text asli menyebut "Config/Throttle.php bawaan CI4" dengan "Services::throttler()" — TIDAK ADA di codeigniter4/framework v4.7.4 terinstall (dikonfirmasi via pencarian penuh `Throttle`/`Throttler` di seluruh `vendor/codeigniter4/framework/**/*.php`, NOL match; sudah didokumentasikan sebelumnya di docblock `T2CaptchaRateLimitExplorationTest.php`/`M3TicketEnumerationExplorationTest.php` task 13/16). Diimplementasikan sebagai gantinya menggunakan `Services::cache()` (sudah tersedia, `app/Config/Cache.php` — handler `file`/backup `dummy`) — FUNGSIONAL SETARA.
    - Dibuat `app/Config/Throttle.php` (class config array BIASA, BUKAN wrapper CI4 builtin) — `$routes` (ambang batas per-alias) + `$default`. Ambang batas dipilih: `otentifikasi` 10/60detik, `login/savetiket` 5/60detik (lebih ketat karena memicu SMTP nyata), `login/cektiket` 20/60detik (paling longgar, read-only tanpa side-effect) — alasan lengkap didokumentasikan di komentar kode.
    - Dibuat `App\Filters\ThrottleFilter` (`app/Filters/ThrottleFilter.php`) — key cache per-alias-rute+IP (`throttle_{alias}_{ip}`), counter+TTL window via `get()`/`save()`, kembalikan `Services::response()->setStatusCode(429)->setJSON([...])` jika counter ≥ limit dalam window aktif. Degradasi graceful (fail-open) terdokumentasi jika cache backup `dummy` aktif.
    - Diterapkan pada route `otentifikasi`, `login/savetiket`, `login/cektiket` via `$filters` (BUKAN `$globals`) di `app/Config/Filters.php` — 3 alias filter terpisah dengan argumen (`'throttle:otentifikasi'`, `'throttle:login/savetiket'`, `'throttle:login/cektiket'`) agar ambang batas per-route dapat berbeda; `login/cektiket` SUDAH dimasukkan dari awal (dipakai ulang M3, bukan ditunda ke 19.3) sesuai instruksi task ini sendiri.
    - **Urutan eksekusi filter diverifikasi dari source CI4** (`vendor/codeigniter4/framework/system/Filters/Filters.php`, `app/Config/Feature.php::$oldFilterOrder = false`): filter global (`csrf`) dieksekusi SEBELUM filter route-specific (`throttle`) pada posisi `before` — `processGlobals()` dipanggil PALING TERAKHIR dalam `initialize()` dan MEN-PREPEND hasilnya ke depan array, sehingga menempati posisi paling depan pada urutan eksekusi akhir. Konsekuensi: request TANPA token CSRF valid akan ditolak CSRF (403) SEBELUM mencapai throttle — token CSRF WAJIB ada pada SETIAP request test rate-limit, termasuk yang ke-(limit+1) yang diharapkan 429 dari throttle (bukan dari CSRF).
    - **Test verifikasi baru**: `tests/Bugfix/T2M3RateLimitHttpIntegrationTest.php` (2 test, 8 assertions, LULUS) — target `login/cektiket` (read-only, tanpa email/API eksternal, paling murah untuk ditest berulang) via `FeatureTestTrait` in-process + token CSRF pada setiap dispatch (pola `Klaster3PreservationTest::denganTokenCsrf()`): (a) request ke-1 s.d. ke-N (N = limit dari `Config\Throttle`) diproses normal (200), request ke-(N+1) DALAM WINDOW YANG SAMA → 429 dengan body JSON graceful `{"status":"danger",...}`; (b) pesan "tidak ditemukan" tetap spesifik (bukan generik) untuk request yang lolos filter (Preservation 2.43).
    - Regression check: `Klaster3PreservationTest.php` + `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` + `T2CaptchaImageHttpIntegrationTest.php` (12 test, 70 assertions, LULUS bersama T2M3RateLimitHttpIntegrationTest.php — total 14 test, 78 assertions) — TIDAK ADA regresi baru dari filter throttle. `AlurFondasiTest::testLoginCaptchaSalahDitolak` GAGAL (`SecurityException`) — dikonfirmasi via isolasi `git stash` bahwa ini PRA-EXISTING akibat aktivasi CSRF task 18 (belum ter-commit ke git, hanya di working tree), SAMA SEKALI TIDAK terkait perubahan task 19.2 (filter csrf berjalan sebelum throttle, jadi test tersebut gagal di CSRF sebelum sempat mencapai throttle).
    - _Bug_Condition: input.type == 'request' AND input.route IN AUTH_AND_TICKET_ROUTES AND NOT rateLimitEnforced(input.ip)_
    - _Expected_Behavior: request ke-(N+1) dalam window yang sama dari IP sama → 429; request ke-N tetap diproses normal_
    - _Preservation: rate limit tidak memblokir pengguna wajar; ambang batas ditetapkan longgar untuk normal namun ketat untuk brute force; pesan "ditemukan"/"tidak ditemukan" pada login/cektiket TIDAK diubah menjadi generik — rate limiting (bukan penyamaran pesan) adalah kontrol primer untuk M3_
    - _Requirements: 2.29, 2.30, 2.42, 2.43_

  - [x] 19.3 Verifikasi test eksplorasi bug condition T2 dan M3 sekarang lulus
    - **Property 1: Expected Behavior** - Captcha Gambar + Rate Limiting, Enumerasi Dibatasi
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 13 dan task 16 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (captcha tidak lagi terbaca dari DOM sebagai plaintext; request ke-(N+1) mengembalikan 429; enumerasi tiket dibatasi rate limit yang sama)
    - **Redefinisi cakupan checkpoint (Opsi (b), presedan IDENTIK K1's task 3.3-style/T4's task 9.2/T1's task 18.4)**: `T2CaptchaRateLimitExplorationTest.php` (task 13, 3 method) dan `M3TicketEnumerationExplorationTest.php` (task 16, 1 method) diverifikasi ULANG langsung (bukan dipercaya dari klaim 19.2 mentah-mentah) — `vendor/bin/phpunit tests/Bugfix/T2CaptchaRateLimitExplorationTest.php tests/Bugfix/M3TicketEnumerationExplorationTest.php --testdox` menghasilkan **4 Error dari 4 method** (BUKAN pass/fail bermakna terhadap rate-limit), diverifikasi SATU PER SATU (bukan digeneralisasi):
      1. `T2CaptchaRateLimitExplorationTest::testCaptchaTerbacaLangsungDariDomTanpaOcr` — **Error** (`TypeError: Cannot assign CodeIgniter\HTTP\CLIRequest to property ...Security::$request`): method ini me-render `view('layouts/login', ...)` LANGSUNG tanpa dispatch HTTP — crash disebabkan `csrf_field()` (task 18) butuh `IncomingRequest` context, **PRA-EXISTING sebelum task 19.1/19.2 disentuh sama sekali** (sudah didokumentasikan eksplisit di catatan task 19.1 sendiri untuk task 19.3) — SAMA SEKALI TIDAK terkait rate-limiting (task 19.2) yang sedang diverifikasi checkpoint ini.
      2. `T2CaptchaRateLimitExplorationTest::testSeluruhRequestOtentifikasiBeruntunDiprosesTanpaRateLimit` — **Error** (`CodeIgniter\Security\Exceptions\SecurityException`) pada request PERTAMA: method ini POST ke `otentifikasi` TANPA token CSRF sama sekali (ditulis SEBELUM CSRF task 18.1 aktif) — filter global `csrf` (prepend di depan array filter final, dikonfirmasi dari source `Filters::initialize()`) menolak 403 SEBELUM request mencapai filter `throttle:otentifikasi` sama sekali — KETERBATASAN STRUKTURAL interaksi csrf+throttle pada test lama, bukan bug throttle.
      3. `T2CaptchaRateLimitExplorationTest::testSeluruhRequestSavetiketSuksesBeruntunDiprosesTanpaRateLimit` — **Error** (`SecurityException`) identik, POST ke `login/savetiket` tanpa CSRF — sebab sama persis dengan #2.
      4. `M3TicketEnumerationExplorationTest::testEnumerasiNomorTiketBeruntunDiprosesTanpaRateLimitDanSinyalTetapTerbedakan` — **Error** (`SecurityException`) identik, POST ke `login/cektiket` tanpa CSRF — sebab sama persis dengan #2/#3.

      3 dari 4 error (#2, #3, #4) memiliki sebab TUNGGAL yang identik: test-test tersebut ditulis sebelum CSRF aktif sehingga tidak menyertakan token, dan filter csrf global (yang SELALU dieksekusi lebih dulu dari filter route-specific throttle) menolaknya sebelum sempat mencapai logika rate-limit yang diuji — persis pola presedan T1's task 18.4. Error #1 memiliki sebab BERBEDA (crash render-langsung tanpa dispatch HTTP, pra-existing dari task 19.1, di luar scope rate-limiting) namun kesimpulannya sama: TIDAK ADA satu pun dari 4 method yang dapat lagi berfungsi sebagai gate pass/fail bermakna terhadap expected behavior T2/M3 pasca-fix.

      **KEDUA file DIPERTAHANKAN apa adanya** sebagai dokumentasi/regression-guard historis (mendokumentasikan bug T2/M3 SEBELUM fix) — TIDAK dihapus, TIDAK diubah assertion-nya, **BUKAN gate checkpoint 19.3 lagi**. Gate checkpoint 19.3 yang BENAR adalah `T2M3RateLimitHttpIntegrationTest.php` (task 19.2) — diverifikasi ULANG LULUS 2/2 (8 assertions) pada `login/cektiket` (M3 + aspek rate-limit T2 pada mekanisme filter yang sama).
    - **Analisis cakupan T2 tambahan (instruksi task 19.3 poin 4)**: `T2M3RateLimitHttpIntegrationTest.php` (19.2) HANYA menguji `login/cektiket`. Aspek captcha-gambar T2 (Requirement 2.27, 2.28) SUDAH tercover penuh `T2CaptchaImageHttpIntegrationTest.php` (task 19.1, 3/3 test). NAMUN aspek rate-limiting T2 pada rute `otentifikasi`/`login/savetiket` (Requirement 2.29, 2.30 — berbeda dari M3's `login/cektiket`) **BELUM memiliki bukti HTTP-integration-level SAMA SEKALI** sebelum task ini (diverifikasi langsung, bukan diasumsikan) — ditutup dengan **menambahkan 1 method test baru** (`testOtentifikasiRequestKeNPlus1DalamWindowSamaMendapat429`) ke `T2M3RateLimitHttpIntegrationTest.php` YANG SUDAH ADA (bukan file baru), menyasar rute `otentifikasi` (limit 10/60detik dari `Config\Throttle` — maksimal 11 request, di bawah ambang 15 presedan `T2CaptchaRateLimitExplorationTest.php`). Field `captcha` dikirim SENGAJA SALAH (bukan diseed session) — `Otentifikasi::index()` memvalidasi `$aturan` (required-only) DAN filter `throttle:otentifikasi` berjalan di stage `before` SEBELUM controller body, sehingga request berhenti di gerbang captcha controller (200) TANPA PERNAH mencapai `cekDatabase()`/`OsmClient::postLogin()` — **0 panggilan HTTP nyata ke API pihak ketiga `osm.unmul.ac.id`** (bukan sekadar dibatasi ≤15, benar-benar nol). Diverifikasi ULANG LULUS 3/3 (13 assertions) setelah penambahan.
      `login/savetiket` SENGAJA TIDAK ditambah test serupa — keputusan teknis terdokumentasi: `ThrottleFilter` adalah satu class generik yang identik dipakai ketiga alias rute (hanya argumen alias config yang berbeda, bukan logika berbeda); properti inti mekanisme filter sudah dibuktikan lulus DUA KALI pada mekanisme yang sama persis (`login/cektiket` + `otentifikasi`) — menambah pengujian ketiga pada `login/savetiket` memerlukan ≥6 email SMTP nyata (limit 5/60detik) untuk nilai bukti tambahan yang marginal terhadap mekanisme yang sudah terbukti generik, dinilai tidak proporsional dengan biaya (konsisten prinsip skala-request-minimal presedan `T2CaptchaRateLimitExplorationTest.php`).
    - Regression check dijalankan ulang: `vendor/bin/phpunit tests/Bugfix/Klaster3PreservationTest.php tests/Bugfix/T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php tests/Bugfix/T2CaptchaImageHttpIntegrationTest.php tests/Bugfix/T2M3RateLimitHttpIntegrationTest.php --testdox` — LULUS 15/15 (83 assertions), TIDAK ADA regresi.
    - _Requirements: 2.27, 2.28, 2.29, 2.30, 2.42_

- [x] 20. Fix T3: Hapus fallback cookie captcha
  - [x] 20.1 Implementasikan fix pada `eult_captcha_check()`
    - Hapus baris `$tersimpan = get_cookie('captcha_code'); if (! $tersimpan) { ... }` SEPENUHNYA dari `app/Helpers/eult_captcha_helper.php`
    - Ganti langsung dengan `$tersimpan = session()->get('captcha');`
    - Sisa logika (`is_string`/`strtoupper` comparison) TIDAK berubah
    - Update docblock fungsi, hapus kalimat "Kompatibel dengan cookie lama 'captcha_code' bila masih ada"
    - Ini adalah keputusan final tanpa periode transisi — dihapus total, bukan dikondisikan
    - _Bug_Condition: input.clientCookie['captcha_code'] IS SET AND input.clientCookie['captcha_code'] != input.serverSession['captcha'] AND strtoupper(input.userInput) == strtoupper(input.clientCookie['captcha_code'])_
    - _Expected_Behavior: hasil eult_captcha_check(input) SHALL identik dengan hasil yang HANYA berdasarkan session()->get('captcha') — cookie sama sekali tidak dibaca_
    - _Preservation: captcha benar sesuai session tetap lolos (case-insensitive); captcha salah tetap gagal, tidak terpengaruh cookie apa pun_
    - _Requirements: 2.31, 2.32, 2.33_

  - [x] 20.2 Verifikasi test eksplorasi bug condition T3 sekarang lulus
    - **Property 1: Expected Behavior** - Cookie Captcha Tidak Lagi Membypass
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 14 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (cookie `captcha_code=ABCD` tidak lagi mempengaruhi hasil; hanya session yang dipakai)
    - **BUKAN redefinisi checkpoint gate** (berbeda dari presedan K1's 3.3-style/T4's 9.2/T1's 18.4/T2&M3's 19.3, di mana test lama tetap BENAR namun tidak lagi relevan sebagai gate akibat KETERBATASAN STRUKTURAL — misal dispatch in-process tidak bisa memicu mekanisme tertentu, atau assertion "ketidakhadiran fitur X" jadi berlawanan by-design setelah fitur X ditambahkan). Verifikasi ulang `vendor/bin/phpunit tests/Bugfix/T3CookieCaptchaBypassExplorationTest.php --testdox` pasca-fix 20.1 menghasilkan **11/12 method LULUS, 1 method GAGAL**: `testBaselineCaseInsensitiveTetapBerlakuMeskipunViaCookie`. Ditriase LANGSUNG (bukan dipercaya mentah-mentah): method ini SEBELUMNYA men-set `session()->set('captcha', 'lainlagi')`, cookie `'abcd'`, lalu assert `eult_captcha_check('aBcD')` SHALL `true` — docblock aslinya MENGKLAIM ini "baseline yang tidak terpengaruh fix T3" (menguji case-insensitivity "via cookie"). Klaim ini SALAH: nilai `'aBcD'` HANYA cocok (case-insensitive) dengan cookie (`'abcd'`), BUKAN dengan session (`'lainlagi'`) — sehingga method ini, tanpa disadari penulisnya, justru BERGANTUNG PADA precedence cookie yang menjadi INTI bug T3 itu sendiri. Ini adalah **DEFEK LOGIKA PADA TEST ITU SENDIRI** (bug pada skenario data test, bukan pada implementasi fix), BUKAN keterbatasan struktural — sesuai keputusan final user pada Requirement 2.31/2.32 ("SHALL TIDAK ada lagi... logika apa pun yang membaca nilai captcha dari cookie dalam bentuk apa pun", tanpa kondisi/periode transisi), hasil yang BENAR pasca-fix untuk skenario lama tersebut SEHARUSNYA `false` (cookie diabaikan total, `'aBcD'` tidak cocok session `'lainlagi'`) — bukan `true` seperti diklaim assertion lama.
    - **TINDAKAN yang diambil (perbaikan defek test, bukan mempertahankannya sebagai dokumentasi gagal ATAU redefinisi gate ke file lain)**: method diganti nama menjadi `testBaselineCaseInsensitiveTetapBerlakuMurniDariSessionCookieDiabaikan()` dengan docblock baru yang menjelaskan koreksi secara eksplisit (skenario lama vs baru, mengapa lama salah). Skenario BARU: session tetap `'lainlagi'`, cookie diset ke nilai BERBEDA (`'BERBEDASEKALI'`, bukan dihapus dari skenario — dipilih agar tetap membuktikan cookie diabaikan secara eksplisit, bukan sekadar tidak diuji), lalu `eult_captcha_check()` dipanggil dengan 4 variasi kapitalisasi dari NILAI SESSION itu sendiri (`'lainlagi'`/`'LAINLAGI'`/`'LainLagi'`/`'lAiNlAgI'`) — seluruhnya SHALL `true` (membuktikan `strtoupper()` kedua sisi TETAP diterapkan pasca-fix, tidak ada regresi case-insensitivity). Ditambahkan 1 assertion penutup: `eult_captcha_check('BERBEDASEKALI')` (nilai yang HANYA cocok cookie, tidak cocok session) SHALL `false` — pembuktian eksplisit tambahan bahwa cookie benar-benar tidak pernah dibaca dalam bentuk apa pun (Requirement 2.32), bukan sekadar kebetulan tidak tersentuh skenario. Tidak ditambahkan file/method baru lain di luar ini — cakupan "cookie diabaikan meski nilainya cocok input tapi session tidak cocok" sudah tercakup implisit oleh assertion penutup baru ini, dinilai cukup tanpa perlu variant terbalik `testCookieBerbedaDariSessionMembypassValidasi` yang sudah ada.
    - Diverifikasi ULANG LULUS 12/12 (25 assertions, naik dari 21 assertions sebelum perbaikan karena loop 4 variasi kapitalisasi + 1 assertion penutup baru) — **TIDAK ADA lagi method yang gagal**.
    - Regression check dijalankan ulang: `vendor/bin/phpunit tests/Bugfix/Klaster3PreservationTest.php tests/Bugfix/T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php tests/Bugfix/T2CaptchaImageHttpIntegrationTest.php tests/Bugfix/T2M3RateLimitHttpIntegrationTest.php tests/Bugfix/T4RatingHttpIntegrationTest.php --testdox` — LULUS 20/20 (111 assertions), TIDAK ADA regresi dari perbaikan test T3 ini (perubahan murni terisolasi pada satu file test eksplorasi, tidak menyentuh helper/controller apa pun).
    - _Requirements: 2.31, 2.32, 2.33_

- [x] 21. Fix M1: Aktifkan CSP dan header lengkap
  - [x] 21.1 Implementasikan CSP
    - Ubah `$CSPEnabled = false` menjadi `true` di `app/Config/App.php`
    - Konfigurasi whitelist minimal di `app/Config/ContentSecurityPolicy.php` mencakup sumber daya yang benar-benar dipakai (`self`, CDN font/JS tema `login.php`/`detail_user.php` — audit saat implementasi untuk daftar domain pasti berdasarkan observasi task 17)
    - Izinkan `unsafe-inline` pada `styleSrc`/`scriptSrc` HANYA jika inline script/style memang dipakai existing dan TIDAK direfaktor sebagai bagian scope M1 ini
    - `secureheaders` sudah aktif dari task 18.1 — header `X-Frame-Options`/`X-Content-Type-Options`/`Referrer-Policy` otomatis muncul sebagai efek samping
    - _Bug_Condition: NOT input.headers.contains('Content-Security-Policy') AND NOT input.headers.contains('X-Frame-Options')_
    - _Expected_Behavior: response HTTP SHALL menyertakan Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Referrer-Policy_
    - _Preservation: seluruh aset statis existing tetap termuat; fitur JS (AJAX, refresh captcha, chat) tetap jalan tanpa error CSP_
    - **Audit aset dilakukan langsung dari source** `app/Views/layouts/login.php` dan `app/Views/pages/ticketing/detail_user.php` (bukan asumsi/generalisasi):
      - `https://fonts.googleapis.com` — SATU-SATUNYA `<link rel="stylesheet">` eksternal (Google Fonts Poppins), dipakai di KEDUA file. Diverifikasi via fetch langsung terhadap CSS tersebut (bukan asumsi pola umum Google Fonts): CSS benar-benar mereferensikan file `.ttf` dari domain **`https://fonts.gstatic.com`** (5 varian weight, format `truetype`, bukan `.woff2`).
      - Satu `background-image: url("data:image/svg+xml,...")` (ikon search SVG inline Select2 dropdown, `login.php:951`, di dalam blok `<style>`) — HANYA SATU lokasi ditemukan via `grep -n "data:"`, bukan dua seperti perkiraan awal; `detail_user.php` tidak memiliki data: URI apa pun (match "data:" di file itu adalah properti object jQuery AJAX, bukan URI scheme).
      - Blok `<style>` inline besar (~800 baris, `login.php:29` s/d sebelum `</head>`) dan dua blok `<script>` inline (`KTAppOptions` config + handler jQuery document.ready, `login.php:2256` & `2288`) — dikonfirmasi ADA dan TIDAK direfaktor ke file eksternal (di luar scope aktivasi CSP M1 ini) → `unsafe-inline` diperlukan pada `styleSrc`/`scriptSrc`.
      - **Tidak ditemukan** atribut event-handler inline (`onclick=`, `onerror=`, dll) di kedua file — dikonfirmasi via `grep -nE "on(click|error|load|change|submit|...)="` (hasil kosong); seluruh binding event via jQuery `.on()` di dalam blok `<script>` yang sudah tercover di atas.
      - `https://wa.me/628115809970` (tombol WhatsApp helpdesk) dan namespace SVG `http://www.w3.org/2000/svg` (bagian dari `xmlns` di dalam data: URI) — BUKAN resource fetch (link navigasi biasa & string namespace, bukan network request) → sengaja TIDAK ditambahkan ke directive apa pun.
      - Seluruh form action (`login/savetiket`, `login/cektiket`, `otentifikasi`, `detail_user.php` composer reply, rating AJAX) dan `<link>`/`<script src>` lain adalah `base_url()`-prefixed (same-origin) — dikonfirmasi via `grep` form action & AJAX URL, tidak ada eksternal → `formAction`/`connectSrc` dipertahankan default `'self'`, tidak diubah.
    - **Diverifikasi (`vendor/codeigniter4/framework/system/HTTP/ContentSecurityPolicy.php`): CI4 MEMANG menerapkan `script-src-elem`/`script-src-attr`/`style-src-elem`/`style-src-attr` sebagai directive header HTTP TERPISAH** dari base `script-src`/`style-src` (`DIRECTIVES_ALLOWING_SOURCE_LISTS` memetakan keduanya independen, tidak ada fallback otomatis base→elem/attr) — nilai `-Elem`/`-Attr` di config app SEBELUMNYA `'self'` (bukan kosong array seperti default framework core), sehingga WAJIB disesuaikan eksplisit agar konsisten dengan base directive, jika tidak `<link>`/`<style>`/`<script>` element tetap terblokir meski base directive sudah benar.
    - **Whitelist final** (`app/Config/ContentSecurityPolicy.php`):
      - `scriptSrc` = `['self', 'unsafe-inline']`, `scriptSrcElem` = sama (2 blok `<script>` inline)
      - `scriptSrcAttr` = `'self'` (tidak diubah — tidak ada event-handler inline)
      - `styleSrc` = `['self', 'unsafe-inline', 'https://fonts.googleapis.com']`, `styleSrcElem` = sama (`<link>` googleapis + blok `<style>` besar)
      - `styleSrcAttr` = `['self', 'unsafe-inline']` (blok `<style>` element, tidak butuh domain eksternal — googleapis hanya via `<link>`, tercover elem)
      - `fontSrc` = `['self', 'https://fonts.gstatic.com']` (sebelumnya `null`/unset — WAJIB diisi eksplisit, jika tidak fallback ke `default-src` efektif `self` dan memblokir font gstatic meski CSS-nya sendiri sudah diizinkan)
      - `imageSrc` = `['self', 'data:']`
      - `connectSrc`, `formAction`, `objectSrc`, `childSrc` — dipertahankan default `'self'` (tidak ada kebutuhan eksternal, diverifikasi dari audit di atas)
    - **Verifikasi curl server live** (`https://eult.appdev-papenajam.me/`, `/login`, `/otentifikasi` — seluruhnya 404 karena deployment server live belum sync dengan kode workspace ini, TIDAK terkait fix CSP) dan PHP built-in server lokal (307 redirect HTTPS sesuai `$forceGlobalSecureRequests`) — pada SEMUA response (termasuk 404), header `Content-Security-Policy` terkirim persis sesuai whitelist di atas: `base-uri 'self'; child-src 'self'; connect-src 'self'; default-src 'self'; font-src 'self' https://fonts.gstatic.com; form-action 'self'; img-src 'self' data:; object-src 'self'; script-src 'self' 'unsafe-inline' 'nonce-...'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com 'nonce-...'; script-src-elem ...; script-src-attr 'self'; style-src-elem ...; style-src-attr 'self' 'unsafe-inline'` — mengonfirmasi CSP aktif secara global, whitelist sesuai audit, tidak ada gap terhadap resource yang teridentifikasi.
    - **TEMUAN STRUKTURAL PENTING untuk task 21.2** (mengikuti presedan T1 18.3/T3 20.2): `M1SecurityHeadersExplorationTest::testHalamanPublikTidakMengandungHeaderCspDanXFrameOptions` TIDAK BISA lulus via dispatch in-process `FeatureTestTrait` untuk bagian CSP-nya, KARENA `ContentSecurityPolicy::finalize()` (yang membangun header CSP) HANYA dipanggil di dalam `CodeIgniter\HTTP\ResponseTrait::send()` (`vendor/codeigniter4/framework/system/HTTP/ResponseTrait.php:370`) — method yang secara sengaja TIDAK dieksekusi oleh `FeatureTestTrait::get()`/`post()` (dispatch in-process mengembalikan Response object tanpa memanggil `send()` sungguhan, sama seperti keterbatasan `CSRFException`/AJAX handler pada T1). Dikonfirmasi via curl ke server live & PHP built-in server (di atas): CSP header BENAR-BENAR terkirim pada request HTTP sungguhan. `testKonfigurasiCspEnabledMasihFalsePadaKodeAsli` (cek `$CSPEnabled` programatik) dan `testHalamanPublikTidakMengandungHeaderXContentTypeOptionsDanReferrerPolicy` (`secureheaders` filter, bukan CSP) LULUS normal — HANYA assertion `Content-Security-Policy` pada test pertama yang tidak dapat lulus in-process. Ini bukan bug implementasi — task 21.2 (verifikasi ulang test task 15) kemungkinan perlu menerapkan pola cURL-ke-server-live yang sama seperti `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php`/`T3CookieCaptchaBypassExplorationTest.php` untuk skenario CSP spesifik ini, ATAU test tersebut memerlukan koreksi serupa T3 20.2. Keputusan ini diserahkan ke task 21.2, TIDAK diambil sepihak di sini (di luar scope task 21.1).
    - _Requirements: 2.38, 2.39_

  - [x] 21.2 Verifikasi test eksplorasi bug condition M1 sekarang lulus
    - **Property 1: Expected Behavior** - Header Keamanan Lengkap
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 15 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (response mengandung CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
    - **Redefinisi cakupan checkpoint (Opsi (b), presedan IDENTIK K1's task 3.3-style/T4's task 9.2/T1's task 18.4/T2&M3's task 19.3, BERBEDA dari T3's task 20.2 yang defek-test)**: Klaim struktural task 21.1 diverifikasi ULANG independen (bukan dipercaya mentah-mentah) via 3 langkah: (a) `vendor/bin/phpunit tests/Bugfix/M1SecurityHeadersExplorationTest.php --testdox` dijalankan ulang → hasil **2/3 method LULUS, 1 method GAGAL** persis seperti diklaim — `testHalamanPublikTidakMengandungHeaderCspDanXFrameOptions` gagal PERSIS pada assertion pertama (`Content-Security-Policy`, baris 99); PHPUnit menghentikan method pada assertion gagal pertama sehingga assertion `X-Frame-Options` pada method yang sama TIDAK PERNAH dieksekusi (bukan gagal terpisah, hanya tidak tercapai) — dikonfirmasi dari output PHPUnit yang hanya menyebut satu pesan kegagalan (CSP) untuk method tersebut, total 6 assertions tereksekusi dari 3 method (2 method lain 2+1 assertion penuh, method gagal hanya sampai 1 assertion); `testKonfigurasiCspEnabledMasihFalsePadaKodeAsli` (cek `$CSPEnabled` programatik) dan `testHalamanPublikTidakMengandungHeaderXContentTypeOptionsDanReferrerPolicy` (2 header dari filter `secureheaders`, bukan CSP) LULUS NORMAL, TIDAK terpengaruh keterbatasan struktural — TETAP dianggap lulus normal, bukan bagian redefinisi. (b) Source `vendor/codeigniter4/framework/system/HTTP/ResponseTrait.php` (method `send()`) dan `ContentSecurityPolicy.php` (method `finalize()`) dibaca LANGSUNG — dikonfirmasi PERSIS: `send()` memanggil `$this->CSP->finalize($this)` SEBELUM `sendHeaders()`; `finalize()` memanggil `generateNonces()` lalu `buildHeaders($response)` (method yang benar-benar menambahkan header `Content-Security-Policy`). `send()` HANYA dipanggil pada request lifecycle produksi sungguhan (`CodeIgniter::run()`), TIDAK PERNAH oleh `FeatureTestTrait::call()` (dispatch in-process mengembalikan Response object sebelum tahap output) — mengonfirmasi klaim struktural 21.1 persis, BUKAN kegagalan implementasi. (c) Verifikasi curl independen ke server live (`https://eult.appdev-papenajam.me/login`) pada saat task ini dikerjakan: server REACHABLE dan mengembalikan HTTP 200 (BERBEDA dari kondisi 404 yang dilaporkan task 21.1 — deployment server live sudah tersinkron ulang, TIDAK terkait fix CSP) — header response mengandung KEEMPAT header lengkap: `content-security-policy` (whitelist persis sesuai konfigurasi 21.1: `default-src 'self'`, `font-src 'self' https://fonts.gstatic.com`, `style-src 'self' 'unsafe-inline' https://fonts.googleapis.com ...`, `img-src 'self' data:`, `script-src 'self' 'unsafe-inline' 'nonce-...'`, dll), `x-frame-options: SAMEORIGIN`, `x-content-type-options: nosniff`, `referrer-policy: same-origin` — mengonfirmasi ULANG independen bahwa header CSP benar-benar terkirim pada request HTTP sungguhan.
    - `M1SecurityHeadersExplorationTest.php` (task 15) DIPERTAHANKAN apa adanya sebagai dokumentasi/regression-guard historis (mendokumentasikan bug M1 SEBELUM fix) — TIDAK dihapus, TIDAK diubah assertion-nya, **BUKAN gate checkpoint 21.2 lagi UNTUK BAGIAN CSP SPESIFIK-nya**. 2 method/assertion lain yang TIDAK terpengaruh keterbatasan struktural (`X-Frame-Options`/`X-Content-Type-Options`/`Referrer-Policy` dari filter `secureheaders`, dan `$CSPEnabled` programatik) TETAP dianggap lulus normal, bukan bagian redefinisi.
    - **Test verifikasi baru**: `tests/Bugfix/M1CspHeaderHttpIntegrationTest.php` (2 test, 15 assertions, LULUS) — cURL-ke-server-live (mengikuti pola PERSIS `K1SqlInjectionHttpIntegrationTest.php`/`T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php`: CAINFO bundle sistem `/etc/ssl/certs/ca-certificates.crt`, timeout 15s, `CURLOPT_SSL_VERIFYPEER`, versi GET dengan `CURLOPT_HEADERFUNCTION` untuk menangkap header response) men-dispatch `GET /login` sungguhan ke server live (dipilih atas PHP built-in server lokal — server live sudah reachable dan mengembalikan header lengkap sesuai whitelist saat diverifikasi, konsisten dengan SELURUH test HTTP-integration lain di direktori ini yang mengasumsikan server live reachable tanpa auto-start programatik; menambah start-server-sendiri di sini akan jadi pola baru tanpa precedent dan tanpa nilai tambah karena server live sudah cukup): (a) assert header `Content-Security-Policy` non-kosong DAN mengandung directive kunci sesuai whitelist final 21.1 (`default-src 'self'`, `font-src 'self' https://fonts.gstatic.com`, `style-src 'self' 'unsafe-inline' https://fonts.googleapis.com`, `img-src 'self' data:`, `script-src 'self' 'unsafe-inline'`); (b) regression-guard pelengkap memverifikasi `X-Frame-Options`/`X-Content-Type-Options`/`Referrer-Policy` juga hadir pada response HTTP sungguhan yang sama (bukan redefinisi gate untuk ketiganya, hanya bukti HTTP-level konsisten).
    - Regression check dijalankan ulang: `vendor/bin/phpunit tests/Bugfix/Klaster3PreservationTest.php tests/Bugfix/T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php tests/Bugfix/T2CaptchaImageHttpIntegrationTest.php tests/Bugfix/T2M3RateLimitHttpIntegrationTest.php tests/Bugfix/T3CookieCaptchaBypassExplorationTest.php tests/Bugfix/T4RatingHttpIntegrationTest.php --testdox` — LULUS 32/32 (136 assertions), TIDAK ADA regresi.
    - _Requirements: 2.38, 2.39_

  - [x] 21.3 Verifikasi seluruh test preservasi Klaster 3 masih lulus
    - **Property 2: Preservation** - Request Sah, Captcha Benar, Aset CSP, Pengguna Wajar Tidak Terblokir
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 17 — JANGAN tulis test baru
    - Jalankan test properti preservasi dari langkah 17 (CSRF valid, captcha benar dari gambar, aset CSP, rate limit wajar)
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi tidak ada regresi lintas T1/T2/T3/M1/M3)
    - Konfirmasi seluruh test masih lulus setelah fix (tidak ada regresi)
    - **Verifikasi gate murni** (`vendor/bin/phpunit tests/Bugfix/Klaster3PreservationTest.php --testdox`): **LULUS 6/6 (34 assertions)** — identik persis dengan hasil regression check task 21.2 (tidak ada perubahan assertion apa pun pada file ini, sesuai instruksi "JANGAN tulis test baru").
    - **Checkpoint menyeluruh Klaster 3** (10 file sekaligus, `vendor/bin/phpunit tests/Bugfix/Klaster3PreservationTest.php tests/Bugfix/T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php tests/Bugfix/T1CsrfExplorationTest.php tests/Bugfix/T2CaptchaImageHttpIntegrationTest.php tests/Bugfix/T2M3RateLimitHttpIntegrationTest.php tests/Bugfix/T2CaptchaRateLimitExplorationTest.php tests/Bugfix/T3CookieCaptchaBypassExplorationTest.php tests/Bugfix/M1SecurityHeadersExplorationTest.php tests/Bugfix/M1CspHeaderHttpIntegrationTest.php tests/Bugfix/M3TicketEnumerationExplorationTest.php --testdox`): total **39 tests, 141 assertions, 4 Errors, 3 Failures** — hasil PERSIS per file (dikonfirmasi satu per satu, bukan digeneralisasi):
      1. `Klaster3PreservationTest.php` — **6/6 LULUS** (34 assertions).
      2. `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` — **3/3 LULUS**.
      3. `T1CsrfExplorationTest.php` — **1 LULUS (sanity), 1 Error, 1 Failure** dari 3 method — PERSIS sama dengan yang didokumentasikan task 18.4 (`testPostSavetiketTanpaTokenCsrfDitolakDenganResponsErrorDanTidakAdaInsert` Error karena `SecurityException` lolos tidak tertangkap saat dispatch in-process; `testFormLoginTidakMengandungPemanggilanCsrfFieldApaPun` Failure karena `login.php` sekarang mengandung `csrf_field(` by design pasca-18.2) — jumlah TIDAK bertambah/berkurang.
      4. `T2CaptchaImageHttpIntegrationTest.php` — **3/3 LULUS**.
      5. `T2M3RateLimitHttpIntegrationTest.php` — **3/3 LULUS**.
      6. `T2CaptchaRateLimitExplorationTest.php` — **0/3 LULUS, 3 Error** dari 3 method (`testCaptchaTerbacaLangsungDariDomTanpaOcr` — assertion span `.captcha-display` gagal, `<img>` sekarang dipakai by design; `testSeluruhRequestOtentifikasiBeruntunDiprosesTanpaRateLimit` dan `testSeluruhRequestSavetiketSuksesBeruntunDiprosesTanpaRateLimit` — keduanya `SecurityException` karena test lama tidak menyertakan token CSRF).
      7. `T3CookieCaptchaBypassExplorationTest.php` — **12/12 LULUS** (termasuk seluruh data set `@dataProvider`).
      8. `M1SecurityHeadersExplorationTest.php` — **2 LULUS, 1 Failure** dari 3 method (`testHalamanPublikTidakMengandungHeaderCspDanXFrameOptions` — Failure PERSIS pada assertion CSP pertama, keterbatasan struktural `send()`/`FeatureTestTrait` yang sudah didokumentasikan task 21.2; 2 method lain LULUS normal) — PERSIS sama dengan task 21.2, jumlah TIDAK bertambah/berkurang.
      9. `M1CspHeaderHttpIntegrationTest.php` — **2/2 LULUS**.
      10. `M3TicketEnumerationExplorationTest.php` — **0/1 LULUS, 1 Error** dari 1 method (`testEnumerasiNomorTiketBeruntunDiprosesTanpaRateLimitDanSinyalTetapTerbedakan` — `SecurityException`, POST tanpa token CSRF, sebab identik dengan #6).
    - **Rekonsiliasi total "4 Error expected dari task 19.3"**: task 19.3 mendokumentasikan gabungan `T2CaptchaRateLimitExplorationTest.php` (3 method) + `M3TicketEnumerationExplorationTest.php` (1 method) = 4 Error lintas KEDUA file — hasil run ini PERSIS 3 Error (#6) + 1 Error (#10) = **4 Error total, IDENTIK**, tidak bertambah/berkurang.
    - **Kesimpulan**: SELURUH 7 method yang tidak lulus (4 Error + 3 Failure) adalah kegagalan struktural/by-design yang SUDAH terdokumentasi eksplisit pada task 18.4 (T1, 1E+1F), task 19.3 (T2+M3, 4E gabungan), dan task 21.2 (M1, 1F) — TIDAK ADA regresi baru, TIDAK ADA jumlah yang berbeda dari yang sudah didokumentasikan sebelumnya. Seluruh file preservasi murni (`Klaster3PreservationTest.php`) dan seluruh file HTTP-integration baru (`T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php`, `T2CaptchaImageHttpIntegrationTest.php`, `T2M3RateLimitHttpIntegrationTest.php`, `M1CspHeaderHttpIntegrationTest.php`, `T3CookieCaptchaBypassExplorationTest.php`) tetap 100% LULUS.
    - _Requirements: 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20, 3.23, 3.24, 3.27, 3.28_

- [x] 22. Checkpoint Klaster 3 - Pastikan seluruh test T1/T2/T3/M1/M3 lulus
  - Pastikan seluruh test lulus (5 eksplorasi + 1 preservasi gabungan), tanyakan ke user jika ada pertanyaan
  - Verifikasi integrasi antar-fix: form dengan CSRF token + captcha gambar + rate limit + CSP aktif secara bersamaan tidak saling menghalangi flow submit tiket end-to-end
  - Catatan operasional: M3 tidak memerlukan perubahan kode terpisah — bergantung pada `ThrottleFilter` yang sama dari task 19.2 diterapkan ke route `login/cektiket`

  **VERIFIKASI INTEGRASI ANTAR-FIX (nilai tambah task 22 di atas task 21.3) — pendekatan yang dipilih dan alasan:**

  Task 21.3 sudah mereverifikasi 10 file test SATU-PER-SATU (masing-masing menguji SATU fix secara terisolasi — misal test CSRF pakai captcha yang diseed manual ke session, test captcha image tidak menyertakan validasi CSRF penuh dalam skenario sukses). **BELUM ADA test yang membuktikan skenario PENGGUNA SUNGGUHAN** dengan KEEMPAT mekanisme (CSRF, captcha gambar, rate limit, CSP) aktif BERSAMAAN pada satu request nyata. Ditutup dengan **kombinasi Opsi 1 (test HTTP-integration baru) + Opsi 2 (analisis manual urutan filter)** — bukan Opsi 1/2 murni sendiri-sendiri, dan bukan test end-to-end kombinatorial N×M×K yang rapuh (properti inti yang perlu dibuktikan hanya SATU: "keempat mekanisme aktif bersamaan tidak saling memblokir request yang sah" — cukup dibuktikan sekali dengan bukti byte-level, bukan diulang berkali-kali dengan variasi kecil).

  **File baru**: `tests/Bugfix/Klaster3IntegrasiAntarFixHttpIntegrationTest.php` (2 test, 23 assertions, LULUS):

  1. `testFlowLengkapCsrfCaptchaRateLimitCspAktifBersamaanTidakSalingMenghalangi()` — test HTTP-integration end-to-end (cURL-ke-server-live, cookie jar sungguhan, mengikuti pola `T2CaptchaImageHttpIntegrationTest.php`/`M1CspHeaderHttpIntegrationTest.php`, TAMBAHAN cookie jar yang belum dipakai test HTTP-integration Klaster 3 lain karena masing-masing hanya menguji satu mekanisme). Langkah PERSIS skenario pengguna sungguhan: (a) `GET /login` sungguhan dengan cookie jar → dapat cookie `ci_session`+`csrf_cookie_name` ASLI dan token CSRF `csrf_test_name` di-parse LANGSUNG dari HTML form (bukan `csrf_token()`/`csrf_hash()` helper test manual); (b) nilai captcha SESUNGGUHNYA (yang di-generate request (a) dan dirender `Login::captchaImage()` sebagai gambar PNG pada request yang sama) dibaca dari **session store server-side** (`writable/session/ci_session{id}`, session ID dari cookie (a), session driver `FileHandler` — `app/Config/Session.php`) — **investigasi kunci task 22**: dikonfirmasi `eult.appdev-papenajam.me` di `/etc/hosts` memetakan ke `127.0.0.1` — "server live" pada proyek ini ADALAH proses PHP lokal yang menulis session ke `writable/session/` workspace ini sendiri, sehingga membaca file ini adalah membaca **store SERVER-SIDE**, BUKAN mengintersepsi apa pun yang dikirim ke KLIEN manapun — Requirement 2.28 ("captcha tidak pernah dikirim plaintext ke KLIEN") TIDAK dilanggar; (c) `POST login/savetiket` dengan cookie jar SAMA (a), token CSRF asli, captcha asli dari (b), field form lengkap — SATU request dengan SEMUA elemen asli sekaligus. **Hasil**: HTTP 200, `{"status":"success",...}`, baris `d_ticketing` ter-insert nyata (`WSMV-AHXJ-001` pada investigasi manual awal, dibersihkan segera; test permanen memakai fixture `k22-integrasi-antar-fix@example.invalid` dengan cleanup setUp/tearDown), DAN response 200 yang SAMA tetap menyertakan header `Content-Security-Policy` lengkap + `X-Frame-Options`/`X-Content-Type-Options`/`Referrer-Policy` — membuktikan KEEMPAT mekanisme aktif bersamaan pada request yang SAMA tanpa saling menghalangi.
  2. `testAnalisisUrutanFilterCsrfSelaluDieksekusiSebelumThrottleSehinggaTidakAdaFalsePositiveKonsumsiQuota()` — menjawab pertanyaan konkret task 22 ("apakah throttle counter bisa false-positive increment duluan sebelum CSRF gagal, menghabiskan quota rate-limit pengguna sah yang salah CSRF sekali") DENGAN BUKTI (bukan hanya diklaim): method ini menjalankan `Filters::initialize('login/savetiket')` SUNGGUHAN terhadap `Config\Filters` produksi proyek ini dan membaca urutan array `before` FINAL via `getFilters()` — **JAWABAN: TIDAK**. `app/Config/Feature.php::$oldFilterOrder = false` (dikonfirmasi assertion) menyebabkan `processGlobals()` (populate `csrf`/`invalidchars`) dipanggil PALING TERAKHIR dalam `initialize()` dan MEN-PREPEND hasilnya ke depan array yang sudah berisi filter route-specific (`throttle:login/savetiket`, dipopulate `processFilters()` lebih dulu) — sehingga posisi `csrf` pada array final SELALU lebih depan (indeks lebih kecil) daripada `throttle:login/savetiket`, dibuktikan via `array_search()` + `assertLessThan()` terhadap array SUNGGUHAN yang dihasilkan, bukan hanya baca source. Konsekuensi: request yang ditolak CSRF berhenti SEBELUM `ThrottleFilter::before()` (yang melakukan increment counter) dieksekusi sama sekali — **TIDAK ADA false-positive konsumsi quota akibat kegagalan CSRF**.

  **Temuan tambahan (didokumentasikan, BUKAN bug — observasi trade-off yang sudah disengaja sejak task 19.2, di luar pertanyaan spesifik task 22 namun relevan untuk kelengkapan analisis integrasi)**: captcha SALAH (berbeda dari CSRF) TIDAK mendapat proteksi struktural yang sama — `eult_captcha_check()` dipanggil DI DALAM controller body (`Login::savetiket()`), yang berjalan SETELAH SELURUH filter `before` (termasuk `throttle:login/savetiket`) sudah lolos dan counter-nya SUDAH di-increment (dikonfirmasi dari pembacaan `Login::savetiket()`: urutan `$this->validate()` → `eult_captcha_check()` → cek AJAX → insert, seluruhnya di controller body, SETELAH filter `before`). Artinya SATU percobaan captcha salah oleh pengguna sah (typo, bukan serangan) TETAP mengonsumsi 1 dari 5 quota `login/savetiket` per menit. **Bukan bug nyata yang memerlukan perbaikan pada checkpoint ini** karena: (1) ambang batas 5/60detik SUDAH didokumentasikan eksplisit task 19.2 sebagai "tetap memberi ruang untuk percobaan ulang jika validasi/captcha gagal beberapa kali" — trade-off disengaja, bukan temuan baru; (2) typo wajar realistis terjadi 1-2 kali per sesi manusia, jauh di bawah ambang 5; (3) memperbaikinya memerlukan refaktor arsitektur (memindahkan validasi captcha ke filter) yang di luar scope checkpoint/verifikasi task 22 (bukan task fix) dan berpotensi mengubah kontrak response existing — dilaporkan sebagai temuan analitis, tidak diperbaiki di sini sesuai instruksi task 22 (bug nyata dilaporkan, bukan langsung diperbaiki tanpa berhenti dulu).

  **Final sanity check — 10 file test task 21.3 dijalankan ulang** (`vendor/bin/phpunit tests/Bugfix/Klaster3PreservationTest.php tests/Bugfix/T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php tests/Bugfix/T1CsrfExplorationTest.php tests/Bugfix/T2CaptchaImageHttpIntegrationTest.php tests/Bugfix/T2M3RateLimitHttpIntegrationTest.php tests/Bugfix/T2CaptchaRateLimitExplorationTest.php tests/Bugfix/T3CookieCaptchaBypassExplorationTest.php tests/Bugfix/M1SecurityHeadersExplorationTest.php tests/Bugfix/M1CspHeaderHttpIntegrationTest.php tests/Bugfix/M3TicketEnumerationExplorationTest.php --testdox`): **39 tests, 141 assertions, 4 Errors, 3 Failures** — **PARITAS PERSIS PER-FILE** dengan hasil task 21.3 (diverifikasi satu per satu, bukan digeneralisasi): `Klaster3PreservationTest.php` 6/6; `M1CspHeaderHttpIntegrationTest.php` 2/2; `M1SecurityHeadersExplorationTest.php` 2✔+1✘ (CSP structural, task 21.2); `M3TicketEnumerationExplorationTest.php` 0✔+1✘ (SecurityException tanpa token, task 19.3); `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` 3/3; `T1CsrfExplorationTest.php` 1✔+1E+1F (task 18.4); `T2CaptchaImageHttpIntegrationTest.php` 3/3; `T2CaptchaRateLimitExplorationTest.php` 0✔+3E (task 19.3); `T2M3RateLimitHttpIntegrationTest.php` 3/3; `T3CookieCaptchaBypassExplorationTest.php` 12/12 — TIDAK ADA regresi baru, TIDAK ADA jumlah yang berbeda dari yang didokumentasikan task 21.3.

  **Regression check lintas klaster** (`vendor/bin/phpunit tests/Bugfix/Klaster3PreservationTest.php tests/Bugfix/T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php tests/Bugfix/T2CaptchaImageHttpIntegrationTest.php tests/Bugfix/T2M3RateLimitHttpIntegrationTest.php tests/Bugfix/T3CookieCaptchaBypassExplorationTest.php tests/Bugfix/M1CspHeaderHttpIntegrationTest.php tests/Bugfix/T4RatingHttpIntegrationTest.php tests/Bugfix/Klaster3IntegrasiAntarFixHttpIntegrationTest.php --testdox`) — **LULUS 36/36 (174 assertions)**, termasuk T4 (Klaster 2) dan file integrasi baru task 22 — TIDAK ADA regresi lintas klaster.

  **Verifikasi route** (`app/Config/Routes.php`): SATU-SATUNYA route baru yang relevan Klaster 3 adalah `login/captcha_image` (GET, `Login::captchaImage`, task 19.1) — dikonfirmasi terdaftar benar di dalam `$routes->group('login', ...)`. T1 (CSRF)/T2-rate-limit/T3(cookie fallback)/M1(CSP) SELURUHNYA perubahan filter/config-level, TIDAK menambah route baru apa pun (dikonfirmasi dari pembacaan lengkap `Routes.php` — tidak ada route lain yang relevan Klaster 3 di luar `captcha_image`).

  **Kesimpulan gate checkpoint Klaster 3 (FINAL)**: SELURUH 12 test (10 dari task 21.3 + 2 baru task 22) plus regression cross-klaster LULUS sesuai definisi gate yang berlaku — 4 Error + 3 Failure yang tersisa SELURUHNYA adalah kegagalan struktural/by-design yang SUDAH terdokumentasi eksplisit pada task 18.4 (T1), 19.3 (T2+M3), 21.2 (M1) — bukan regresi baru. Verifikasi integrasi antar-fix task 22 mengonfirmasi: CSRF token asli + captcha gambar asli + rate limit + CSP AKTIF BERSAMAAN pada request submit tiket sungguhan TIDAK saling menghalangi, dan urutan eksekusi filter (csrf sebelum throttle) mencegah false-positive konsumsi quota rate-limit akibat kegagalan CSRF. SATU observasi trade-off (captcha salah tetap mengonsumsi quota throttle) dicatat sebagai temuan analitis yang sudah disengaja sejak task 19.2, bukan bug baru. **Klaster 3 (T1, T2, T3, M1, M3) DINYATAKAN SELESAI — siap lanjut ke Klaster 4.**
  - _Requirements: 2.21-2.43 (T1/T2/T3/M1/M3 gabungan, bugfix.md)_

---

## Klaster 4: Konfigurasi & Observability (K3, K4)

- [x] 23. Tulis test eksplorasi bug condition K3 (fallback hardcode production)
  - **Property 1: Bug Condition** - Kunci Enkripsi Fallback Hardcode di Production
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample bahwa fallback hardcode aktif tanpa syarat di production
  - **Scoped PBT Approach**: Bug ini deterministik/config-based (Testing Strategy design.md: "K3: enum 3 environment") — skenario dikonkretkan sebagai kombinasi `ENVIRONMENT` × isi `.env`
  - Set `ENVIRONMENT=production`, hapus `EULT_ENCRYPTION_LEGACY_KEY` dari `.env` test → assert `Enkripsi::kunciLegacy()` pada kode asli TETAP mengembalikan `'SuPer_Enc-Key2010'` (`app/Libraries/Enkripsi.php:24-31`)
  - Set `ENVIRONMENT=production`, isi `EULT_ENCRYPTION_LEGACY_KEY=SuPer_Enc-Key2010` (identik fallback) → assert TIDAK ADA log/warning apa pun tercatat
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan fallback aktif tanpa syarat DAN tidak ada sinyal log)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.9, 1.10, 1.11_

- [x] 24. Tulis test eksplorasi bug condition K4 (toolbar debug publik)
  - **Property 1: Bug Condition** - Toolbar Debug Aktif Tanpa Syarat Environment
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample bahwa toolbar aktif di environment non-development
  - Set `ENVIRONMENT=testing` (setara production untuk tujuan fix ini), request `?debugbar_time={ts}` pada kode asli (`app/Config/Filters.php:64`, filter `toolbar` di `required.after`) → assert response mengandung data debug (session admin, riwayat SQL, `ticketEmail` pengguna lain)
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan toolbar aktif tanpa memeriksa environment)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.12, 1.13, 1.14_

  **KOREKSI PREMIS TASK TEXT (WAJIB dibaca sebelum melihat implementasi)**: Investigasi LANGSUNG source `vendor/codeigniter4/framework/system/Debug/Toolbar.php` SEBELUM menulis test menemukan bahwa skenario literal "Set `ENVIRONMENT=testing`, request `?debugbar_time`" TIDAK DAPAT mereproduksi bug secara bermakna. Ada DUA method dengan gerbang BERBEDA: (1) `prepare()` (menulis `writable/debugbar/*.json` + inject `<script>` loader, dipanggil filter `after` `toolbar`) — gerbang HANYA `if (CI_DEBUG && ! is_cli())`, **TIDAK ADA pengecekan `ENVIRONMENT` sama sekali**; (2) `respond()` (melayani `?debugbar_time=`/`?debugbar`, dipanggil `app/Config/Events.php:47-49` di dalam gerbang `CI_DEBUG` yang sama) — baris PALING AWAL method ini: `if (ENVIRONMENT === 'testing') { return; }`, **HANYA mengecualikan `'testing'` secara spesifik**, bukan `!== 'development'`. Konsekuensi: skenario literal task text justru mengenai pengecualian KHUSUS `respond()` bawaan framework (kemungkinan untuk mencegah gangguan test PHPUnit) yang SAMA SEKALI tidak berhubungan dengan fix K4 (task 27.1, menyasar titik filter/`prepare()`) — memakai `ENVIRONMENT=testing` akan menghasilkan test yang gagal untuk ALASAN YANG SALAH dan tidak akan lulus bermakna pasca-fix.

  **Investigasi tambahan** (`vendor/codeigniter4/framework/system/Boot.php`, ketiga file `app/Config/Boot/*.php`): dikonfirmasi TIDAK ADA validasi konsistensi `CI_DEBUG` ↔ `ENVIRONMENT` apa pun saat boot NORMAL. Nilai `CI_DEBUG` per Boot file: `development.php`=true, `testing.php`=true (SAMA dengan development), `production.php`=false (unconditional). `loadEnvironmentBootstrap()` HANYA berhasil jika file `app/Config/Boot/{ENVIRONMENT}.php` ada (`is_file()` check, `$exit=true` default → 503 jika tidak ada) — project ini HANYA memiliki 3 file tersebut, sehingga kombinasi "`ENVIRONMENT=production` TAPI `CI_DEBUG=true`" TIDAK BISA dicapai HANYA dengan mengatur `CI_ENVIRONMENT` pada BOOT NORMAL (`production.php` SENDIRI selalu `CI_DEBUG=false` unconditional ketika benar-benar dicapai).

  **Skenario final dipilih**: simulasi operator misconfiguration — `ENVIRONMENT` GENUINELY `'production'` (proses server test benar-benar boot dengan `CI_ENVIRONMENT=production`, dikonfirmasi via sanity test terpisah DAN `config.environment` pada JSON debugbar yang dihasilkan), `CI_DEBUG` disimulasikan ter-override `true` secara independen via `-d auto_prepend_file=` (BUKAN memodifikasi `production.php`/Boot file fisik apa pun) — mereplikasi PERSIS `isBugCondition()` formal spec design.md (`input.environment != 'development'`) tanpa memerlukan boot abnormal. Dua test bug condition dijalankan pada server yang sama: (1) `prepare()` — assert body `GET /login` TIDAK mengandung `debugbar_loader`/file debugbar TIDAK tertulis (expected/fixed behavior); (2) `respond()` — assert `GET ?debugbar_time={ts}` TIDAK 200 dengan data toolbar (expected/fixed behavior), pada `ENVIRONMENT=production` (bukan `'testing'` seperti literal task text, karena `respond()` hanya mengecualikan `'testing'`).

  **Mekanisme dispatch**: `proc_open()` men-spawn SERVER PHP built-in BARU (`php -S 127.0.0.1:{port} public/index.php`, `tests/Bugfix/K4DebugToolbarServerRunner.php`) — BUKAN `bootConsole()` seperti K3 (`Toolbar::prepare()` membutuhkan `$app->getPerformanceStats()`/benchmark `total_execution` yang HANYA di-start `CodeIgniter::run()` selama dispatch HTTP PENUH, tidak tersedia bermakna pada `bootConsole()` maupun `FeatureTestTrait::call()`), dan BUKAN cURL-ke-server-live (port 8099/8100 terikat permanen `.env` fisik project, `ENVIRONMENT` tidak dapat diubah tanpa restart proses yang dilarang eksplisit). **Kuirk kritis mesin ini yang wajib diatasi** (didokumentasikan lengkap `K4DebugToolbarServerRunner.php`): `variables_order=GPCS` di `php.ini` menyebabkan `$_ENV` tidak pernah otomatis terisi dari environment variable proses OS pada `php -S`, sehingga `CodeIgniter\Config\DotEnv::setVariable()` (dipanggil `loadDotEnv()` SEBELUM `defineEnvironment()` pada `Boot::bootWeb()`) menuliskan nilai `.env` FISIK project (`development`) ke `$_ENV['CI_ENVIRONMENT']` TANPA MEMANDANG `getenv()` yang sudah benar — `defineEnvironment()` menemukan `$_ENV` yang salah tersebut lebih dulu dalam null-coalesce chain-nya, membuat `ENVIRONMENT` diam-diam ter-resolve ke `'development'` MESKIPUN `CI_ENVIRONMENT=production` sudah diset benar di level environment variable proses OS. Solusi terverifikasi: `-d variables_order=EGPCS` pada invocation `php -S` (PHP sendiri mengisi `$_ENV` dari OS env SEBELUM `loadDotEnv()` berjalan, sehingga guard `empty($_ENV[$name])` pada `DotEnv` sudah `false`). Kuirk KEDUA yang juga diatasi: `app/Config/App.php::$forceGlobalSecureRequests=true` menyebabkan SETIAP request HTTP polos ke server test menerima 307 dengan `Location` menuju domain live publik — mengikuti redirect (`-L`/`CURLOPT_FOLLOWLOCATION`) akan diam-diam menguji server LIVE SUNGGUHAN, bukan server test terisolasi (jebakan investigasi manual yang ditemukan dan didokumentasikan sebelum test final ditulis); solusi: `CURLOPT_FOLLOWLOCATION=false`, body 307 itu sendiri tetap bermakna karena sudah melalui filter `after` (`toolbar`) sebelum status diubah filter `before` (`forcehttps`).

  **File baru**: `tests/Bugfix/K4DebugToolbarExplorationTest.php` (3 test method, 18 assertions), `tests/Bugfix/K4DebugToolbarServerRunner.php` (helper `proc_open()` server), `tests/Bugfix/K4ForceCiDebugTruePrepend.php` (prepend script simulasi misconfiguration).

  **Hasil run pada kode BELUM diperbaiki** (`vendor/bin/phpunit tests/Bugfix/K4DebugToolbarExplorationTest.php --testdox`): **3 tests, 18 assertions, 2 Failures** (direproduksi stabil 2× run berturut-turut, timestamp/nonce berbeda tiap run seperti diharapkan untuk request HTTP nyata):
  1. `testSanityBaselineNegatifProductionTanpaForceMenunjukkanToolbarNonaktif` — **LULUS** (mengonfirmasi mekanisme isolasi server test bekerja benar: `ENVIRONMENT=production` genuine tanpa override `CI_DEBUG` apa pun → toolbar TIDAK aktif, body 0 byte).
  2. `testBugConditionPrepareMenyuntikkanToolbarPadaEnvironmentProductionKetikaCiDebugTerOverride` — **GAGAL (expected)**: counterexample — body `GET /login` 28834 byte mengandung `<script id="debugbar_loader" data-time="...">`, CSP nonce, seluruh render Kint rich-script/style, pada `ENVIRONMENT=production` genuine (dikonfirmasi `config.environment` file debugbar yang ditulis = `"production"`, BUKAN `"development"` — membuktikan ini bukan kesalahan resolusi environment mekanisme test).
  3. `testBugConditionRespondMelayaniDebugbarTimeDenganDataLengkapPadaEnvironmentProduction` — **GAGAL (expected)**: counterexample — `GET ?debugbar_time={ts}` mengembalikan HTTP 200 dengan body 122316 byte (mengandung marker `kint-rich`, toolbar HTML penuh via `format()`/`toolbar.tpl.php`) pada `ENVIRONMENT=production` genuine — membuktikan `respond()` hanya mengecualikan `'testing'`, bukan `production`.

  Verifikasi kebersihan: tidak ada proses server test (`php -S`) tersisa menyala pasca-run (`ps aux` dikonfirmasi bersih), file `writable/debugbar/*.json` yang ditulis selama test dibersihkan `tearDown()` (jumlah file kembali ke baseline sebelum run). Regression check `vendor/bin/phpunit tests/Bugfix/` penuh (104 tests, 399 assertions, 4 Errors, 22 Failures) dan per-file (`vendor/bin/phpunit tests/Bugfix/{setiap file}.php` satu-per-satu) mengonfirmasi SELURUH file lain menunjukkan jumlah PERSIS SAMA dengan baseline yang sudah terdokumentasi task 18-23 sebelumnya (K1SqlInjectionExplorationTest 13F, K3EncryptionFallbackExplorationTest 2F, M1SecurityHeadersExplorationTest 1F, M3TicketEnumerationExplorationTest 1E, T1CsrfExplorationTest 1E+1F, T2CaptchaRateLimitExplorationTest 3E, T4RatingOwnershipExplorationTest 2F) — TIDAK ADA regresi baru, kontribusi K4 murni 2 Failures baru yang SEPENUHNYA sesuai metodologi bug condition (expected outcome: GAGAL).

  **Detail teknis penting untuk task 27.1 (fix)**: fix HARUS menambahkan pengecekan `ENVIRONMENT === 'development'` LANGSUNG pada titik filter `toolbar`/override `prepare()` (bukan hanya bergantung pada `CI_DEBUG`, yang terbukti dapat ter-override independen) — menutup KEDUA jalur sekaligus: (a) `CI_ENVIRONMENT` salah diatur (`development`/`testing` di production, kondisi server live SAAT INI) DAN (b) `CI_DEBUG` ter-override independen (skenario yang disimulasikan test ini). Fix juga perlu memastikan `respond()`/endpoint `?debugbar_time=` tidak lagi melayani apa pun ketika `ENVIRONMENT !== 'development'` (Requirement 2.18) — bukan hanya mengandalkan pengecualian `'testing'` bawaan framework yang terbukti tidak cukup.

- [x] 25. Tulis test properti preservasi Klaster 4 (SEBELUM implementasi fix)
  - **Property 2: Preservation** - Development Tetap Memakai Fallback dan Toolbar Penuh
  - **IMPORTANT**: Ikuti metodologi observation-first
  - Observasi pada kode BELUM diperbaiki: `ENVIRONMENT=development` dengan `.env` kosong → catat fallback hardcode tetap aktif tanpa warning (workflow lokal tidak terganggu)
  - Observasi: `EULT_ENCRYPTION_LEGACY_KEY` production sudah diisi nilai kuat non-hardcode → catat dipakai tanpa peringatan apa pun
  - Observasi: `ENVIRONMENT=development` → catat toolbar aktif penuh, file debug tersimpan di `writable/debugbar/*.json`
  - Observasi: filter `pagecache`/`performance` (kategori `required.after` sama dengan `toolbar`) → catat tidak terpengaruh
  - Tulis property-based test yang menangkap pola di atas sebagai baseline
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test LULUS pada kode belum diperbaiki (mengonfirmasi baseline)
  - _Requirements: 3.8, 3.9, 3.10, 3.11, 3.12_

  **File baru**: `tests/Bugfix/Klaster4PreservationTest.php` (4 test method, 40 assertions) — reuse LANGSUNG `K3EncryptionEnvironmentRunner.php` (task 23) untuk observasi a/b dan `K4DebugToolbarServerRunner.php` (task 24) untuk observasi c/d, TANPA mekanisme dispatch baru.

  **Modifikasi minimal pada `K3EncryptionEnvironmentRunner.php`**: ditambahkan flag opt-in `K3_OVERRIDE_LEGACY_KEY` (env var, dibaca SETELAH `bootConsole()` — pola identik `K3_FORCE_EMPTY_LEGACY_KEY` yang sudah ada, hanya mengisi bukan mengosongkan). Diperlukan karena investigasi empiris mengonfirmasi kuirk `variables_order=GPCS` (didokumentasikan `K4DebugToolbarServerRunner.php` untuk `php -S`) JUGA berlaku pada `php` CLI biasa di mesin ini (`ini_get('variables_order')` sama `'GPCS'` untuk keduanya) — mengisi `EULT_ENCRYPTION_LEGACY_KEY` murni lewat array `$env` `proc_open()` (tanpa flag ini) TIDAK CUKUP: `.env` FISIK project tetap menimpa `$_ENV` via `DotEnv::setVariable()` karena guard `empty($_ENV[$name])` (dikonfirmasi via reproduksi manual sebelum flag ditambahkan: `envKeyRawValue` SELALU `"SuPer_Enc-Key2010"` meski env var proses OS diisi nilai lain). Flag baru bersifat opt-in (default tidak diset) — regression check mengonfirmasi TIDAK ADA perubahan pada hasil `K3EncryptionFallbackExplorationTest.php` (task 23).

  **Hasil 4 observasi pada kode BELUM diperbaiki** (`vendor/bin/phpunit tests/Bugfix/Klaster4PreservationTest.php --testdox`): **4 tests, 40 assertions, 0 Failures — SEMUA LULUS**:
  1. `testK3Skenario1DevelopmentDenganEnvKeyKosongFallbackTetapAktifTanpaWarning` — **LULUS**: `ENVIRONMENT=development` + `EULT_ENCRYPTION_LEGACY_KEY` kosong → fallback hardcode tetap dipakai (`decodedWithLiteralHardcodedKey === 'TIKET_PROBE_K3'`), `logFileContainsMarker === false` (tidak ada warning).
  2. `testK3Skenario2ProductionDenganKunciKuatNonHardcodeDipakaiTanpaWarning` — **LULUS**: `ENVIRONMENT=production` + `EULT_ENCRYPTION_LEGACY_KEY` diisi kunci kuat 40 karakter non-hardcode (via `K3_OVERRIDE_LEGACY_KEY`) → kunci kustom dipakai sebagai kunci efektif (`decodedWithEnvKeyIfPresent === 'TIKET_PROBE_K3'`, `decodedWithLiteralHardcodedKey` GAGAL membuktikan bukan fallback), `logFileContainsMarker === false` (tidak ada warning untuk kunci non-hardcode apa pun).
  3. `testK4Skenario1DevelopmentToolbarAktifPenuhDenganFileDebugTertulis` — **LULUS**: `ENVIRONMENT=development` GENUINE (tanpa simulasi CI_DEBUG apa pun) → body mengandung `debugbar_loader`, file `writable/debugbar/*.json` tertulis dengan `config.environment === 'development'`.
  4. `testK4Skenario2FilterPagecachePerformanceTidakTerpengaruhToolbarAktif` — **LULUS**: dua request GET berturut ke `login` pada server development yang sama → KEDUA request 307 dengan `debugbar_loader` tetap muncul (pagecache/performance, yang dijamin `Filters::setToolbarToLast()` berjalan sebelum toolbar pada urutan `required.after` yang sama, tidak menghalangi/terhalangi toolbar).

  Regression check (`vendor/bin/phpunit tests/Bugfix/K3EncryptionFallbackExplorationTest.php tests/Bugfix/K4DebugToolbarExplorationTest.php --testdox`): K3 = 4 tests/28 assertions/2 Failures, K4 = 3 tests/18 assertions/2 Failures — PERSIS SAMA dengan baseline terdokumentasi task 23/24 sebelumnya, TIDAK ADA regresi baru akibat modifikasi runner K3.

  Verifikasi kebersihan: tidak ada proses server test (`php -S`) tersisa menyala pasca-run, tidak ada file `writable/debugbar/*.json` baru tersisa (seluruh file yang ditulis selama test dibersihkan `tearDown()`).

- [x] 26. Fix K3: Hapus fallback hardcode dari jalur production (keputusan final, tanpa transisi)
  - [x] 26.1 Implementasikan fix pada `Enkripsi::kunciLegacy()`
    - Tambahkan pengecekan `ENVIRONMENT` di awal fungsi: jika `ENVIRONMENT === 'production'`, JANGAN kembalikan fallback hardcode
    - Jika `EULT_ENCRYPTION_LEGACY_KEY` kosong pada production, kembalikan nilai yang menyebabkan operasi kriptografi gagal TERKONTROL (string kosong yang membuat `encode()`/`decode()` mengembalikan `false` melalui jalur validasi existing — BUKAN exception yang crash aplikasi)
    - Jika `EULT_ENCRYPTION_LEGACY_KEY` production terisi dan SAMA dengan fallback hardcode, `log_message('critical', 'Kunci enkripsi production masih sama dengan nilai default/hardcode — harus dirotasi.')` sebagai defense-in-depth, TETAP lanjutkan memakai nilai tersebut untuk sesi berjalan ini (tidak diblokir otomatis)
    - Untuk `ENVIRONMENT !== 'production'` (development/testing), fallback hardcode TETAP dikembalikan tanpa log/warning
    - Hapus komentar docblock "Jangan ganti kunci ini sampai seluruh URL lama kedaluwarsa", ganti dengan catatan bahwa fallback production sudah dihapus per keputusan K3 (`app/Libraries/Enkripsi.php`)
    - Ini adalah keputusan final tanpa periode transisi kode — langsung diimplementasikan tanpa flag/toggle sementara
    - _Bug_Condition: input.environment == 'production' AND (input.envKeyValue == '' OR input.envKeyValue == HARDCODE_FALLBACK)_
    - _Expected_Behavior: fungsi enkripsi/dekripsi SHALL TIDAK PERNAH menggunakan fallback hardcode sebagai kunci efektif di production; operasi kriptografi gagal terkontrol (bukan diam-diam berhasil dengan kunci publik)_
    - _Preservation: development/testing tetap pakai fallback tanpa gangguan; kunci non-hardcode yang sudah diisi operator tetap dipakai tanpa warning_
    - _Requirements: 2.12, 2.13, 2.14, 2.15, 2.16_
    - **Implementasi fix `app/Libraries/Enkripsi.php`**: `kunciLegacy()` sekarang memeriksa `defined('ENVIRONMENT') && ENVIRONMENT === 'production'` di awal. Production + env key kosong → return `''` (string kosong, bukan exception) — `decodeDenganKunci()` ditambah guard `if ($mentah === '') { return false; }` di awal agar konsisten dengan `encode()`. Production + env key terisi dan identik fallback hardcode (`'SuPer_Enc-Key2010'`) → `log_message('critical', 'Kunci enkripsi production masih sama dengan nilai default/hardcode — harus dirotasi.')` (string literal statis, tanpa parameter dinamis), tetap lanjut memakai nilai tersebut. Production + env key terisi non-hardcode → dipakai langsung tanpa log. Non-production (development/testing) → perilaku lama dipertahankan (fallback hardcode tanpa log apa pun).
    - **Gap instrumentasi test ditemukan + diperbaiki (task lanjutan, bukan redefinisi gate, mengikuti presedan format T3/20.2)**: Setelah fix di atas diimplementasikan, `vendor/bin/phpunit tests/Bugfix/K3EncryptionFallbackExplorationTest.php tests/Bugfix/Klaster4PreservationTest.php --testdox` menghasilkan 7/8 lulus — `testTidakAdaLogWarningKetikaKunciProductionIdentikFallbackHardcode` (Skenario B) gagal pada assertion `logFileContainsMarker`. Diagnosis via reproduksi manual `php K3EncryptionEnvironmentRunner.php` + `grep`/`tail` langsung ke `writable/logs/log-*.log`: baris log critical BENAR-BENAR tertulis (file bertambah, isi sesuai), TAPI `K3EncryptionEnvironmentRunner.php` (ditulis sebelum fix ada, task 23) SALAH MENGASUMSIKAN fix akan menyisipkan marker unik `K3_LOG_MARKER` ke dalam argumen `log_message()` — asumsi ini tidak cocok dengan instruksi task 26.1 yang eksplisit meminta string literal statis untuk pesan log. Ini adalah gap instrumentasi test (bukan bug pada fix). Perbaikan: `K3EncryptionEnvironmentRunner.php` diubah agar mendeteksi substring pesan log statis `'Kunci enkripsi production masih sama dengan nilai default/hardcode'` pada bagian file log yang bertambah (mekanisme offset `fseek($logSizeBefore)` dipertahankan, hanya substring yang dicari yang diganti); parameter `K3_LOG_MARKER` dan field output `logFileContainsMarker` DIPERTAHANKAN nama-nya untuk minimasi perubahan pada caller, namun nilainya tidak lagi dipakai untuk deteksi. Docblock runner dan `K3EncryptionFallbackExplorationTest.php::testTidakAdaLogWarningKetikaKunciProductionIdentikFallbackHardcode()` diupdate untuk mencerminkan mekanisme baru. `Klaster4PreservationTest.php` diverifikasi (bukan diubah) — kedua skenario yang memakai `K3_LOG_MARKER` mengharapkan `logFileContainsMarker === false`, yang tetap benar dengan deteksi baru karena kondisi log critical tidak terpenuhi pada kedua skenario tersebut.
    - **Hasil test final**: `vendor/bin/phpunit tests/Bugfix/K3EncryptionFallbackExplorationTest.php tests/Bugfix/Klaster4PreservationTest.php --testdox` → **8/8 LULUS** (68 assertions). Regression check `vendor/bin/phpunit tests/Bugfix/K1SqlInjectionHttpIntegrationTest.php tests/Bugfix/K2T4M2PreservationTest.php tests/Bugfix/T4RatingHttpIntegrationTest.php tests/Bugfix/CektiketUploadPdfOnlyHttpIntegrationTest.php --testdox` → **21/21 LULUS** (114 assertions), tidak ada regresi.

  - [x] 26.2 Verifikasi test eksplorasi bug condition K3 sekarang lulus
    - **Property 1: Expected Behavior** - Fallback Hardcode Tidak Aktif di Production
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 23 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (production dengan kunci kosong/hardcode gagal terkontrol; production dengan kunci sama fallback ter-log critical)
    - _Requirements: 2.12, 2.13, 2.14, 2.15_
    - **Hasil verifikasi final**: `vendor/bin/phpunit tests/Bugfix/K3EncryptionFallbackExplorationTest.php --testdox` → **4/4 LULUS** (28 assertions, 0 failures). Gap instrumentasi runner (marker-detection) yang ditemukan pasca-implementasi fix 26.1 SUDAH diperbaiki pada task 26.1 itu sendiri (perbaikan `K3EncryptionEnvironmentRunner.php` + docblock test, bukan mengubah `Enkripsi.php`) — lihat catatan lengkap di 26.1 untuk detail diagnosis dan perbaikan. Tidak ada perubahan kode pada verifikasi ini.

- [x] 27. Fix K4: Kunci toolbar hanya ke environment development (keputusan final)
  - [x] 27.1 Implementasikan environment-aware toolbar filter
    - Buat kelas filter custom `App\Filters\EnvironmentAwareToolbar` yang extends/wrap `DebugToolbar`, memeriksa `ENVIRONMENT === 'development'` SEBELUM memanggil parent `after()`
    - Pertahankan `'toolbar'` di `$required['after']` (JANGAN pindahkan kategori — agar filter `pagecache`/`performance` lain di kategori sama tidak terganggu, sesuai Preservation 3.12) namun gunakan kelas filter custom ini sebagai pengganti `DebugToolbar::class` langsung
    - Kelas custom SHALL mengembalikan response tanpa modifikasi apa pun (pass-through) ketika `ENVIRONMENT !== 'development'`, sehingga endpoint `debugbar_time` tidak pernah mendapat data (setara 404/kosong)
    - TIDAK mengubah mekanisme penyimpanan `writable/debugbar/*.json` — hanya syarat aktivasi
    - Tambahkan catatan dokumentasi (bukan kode) merekomendasikan `CI_ENVIRONMENT=production` wajib di server publik
    - _Bug_Condition: input.environment != 'development' AND toolbarFilterExecutes(input)_
    - _Expected_Behavior: toolbar aktif JIKA DAN HANYA JIKA ENVIRONMENT === 'development'; untuk nilai lain, endpoint tidak mengembalikan data debug apa pun_
    - _Preservation: developer lokal (ENVIRONMENT=development) tetap dapat toolbar penuh; filter pagecache/performance tidak berubah_
    - _Requirements: 2.17, 2.18, 2.19, 2.20_
    - **Implementasi fix — DUA titik terpisah (dikonfirmasi investigasi task 23/24, bug K4 punya dua method dengan gerbang berbeda)**:
      1. **`app/Filters/EnvironmentAwareToolbar.php` (file baru)** — extends `CodeIgniter\Filters\DebugToolbar`. `before()` delegasi penuh ke `parent::before()` (no-op, tidak ada logic relevan). `after()` memeriksa `defined('ENVIRONMENT') && ENVIRONMENT === 'development'` — jika TIDAK development, `return null` TANPA memanggil `parent::after()`/`service('toolbar')->prepare()` sama sekali (pass-through murni, `Toolbar::prepare()` vendor tidak pernah dieksekusi); jika development, delegasi penuh `parent::after($request, $response, $arguments)`. Menutup titik pertama bug K4: `prepare()` (menulis `writable/debugbar/*.json` + inject `<script id="debugbar_loader">`) yang sebelumnya HANYA bergantung `CI_DEBUG` tanpa cek `ENVIRONMENT` apa pun.
      2. **`app/Config/Filters.php`** — alias `'toolbar'` diganti dari `DebugToolbar::class` menjadi `EnvironmentAwareToolbar::class` (import `App\Filters\EnvironmentAwareToolbar` ditambahkan, import `CodeIgniter\Filters\DebugToolbar` dihapus karena tidak lagi dirujuk langsung di file ini). `$required['after']` TIDAK diubah sama sekali — urutan `['pagecache', 'performance', 'toolbar']` persis sama posisinya (diverifikasi `grep` pasca-edit).
      3. **`app/Config/Events.php`** — jalur KEDUA bug K4 yang ditemukan investigasi (BUKAN di filter sama sekali): `service('toolbar')->respond()` (melayani endpoint `?debugbar_time=`/`?debugbar` dengan data debug penuh) dipanggil TANPA syarat `ENVIRONMENT` apa pun di dalam gerbang `if (CI_DEBUG && ! is_cli())` — method `respond()` sendiri (vendor) HANYA mengecualikan `ENVIRONMENT === 'testing'` secara spesifik, TIDAK `production`. Ditambahkan guard `if (ENVIRONMENT === 'development') { service('toolbar')->respond(); }` LANGSUNG membungkus panggilan tersebut (titik panggil di file aplikasi, BUKAN vendor). Listener `Events::on('DBQuery', ...)` (kolektor data untuk toolbar, bukan penyaji data ke luar) dan route `__hot-reload` (SUDAH digating `ENVIRONMENT === 'development'` sebelumnya) TIDAK diubah/dipindah — struktur `if (CI_DEBUG && ! is_cli())` yang membungkus ketiganya dipertahankan persis.
      4. Docblock/komentar (bukan kode fungsional) ditambahkan di ketiga file merekomendasikan `CI_ENVIRONMENT=production` wajib eksplisit di server publik, menegaskan fix ini adalah pertahanan KEDUA (defense-in-depth), bukan pengganti konfigurasi environment yang benar.
    - **Hasil test K4 exploration** (`vendor/bin/phpunit tests/Bugfix/K4DebugToolbarExplorationTest.php --testdox`) — **2/3 method LULUS pasca-fix** (naik dari 1/3 sebelum fix): `testSanityBaselineNegatifProductionTanpaForceMenunjukkanToolbarNonaktif` TETAP LULUS (baseline negatif tidak terpengaruh); `testBugConditionPrepareMenyuntikkanToolbarPadaEnvironmentProductionKetikaCiDebugTerOverride` BERUBAH dari GAGAL → **LULUS** (fix titik 1 dikonfirmasi: `prepare()` tidak lagi menyuntikkan `debugbar_loader`/menulis file debugbar pada `ENVIRONMENT=production` meski `CI_DEBUG` ter-override). `testBugConditionRespondMelayaniDebugbarTimeDenganDataLengkapPadaEnvironmentProduction` **GAGAL pada assertion PRECONDITION** (`assertArrayHasKey(1, $dataTimeMatches)` baris 415), BUKAN pada assertion utama yang menguji `respond()` — **gap instrumentasi test** (bukan bug pada fix, pola identik gap K3 yang terdokumentasi task 26.1): method ini secara struktural men-chain langkah 1 (`prepare()` menghasilkan atribut `data-time` sebagai sumber timestamp) → langkah 2 (`respond()` diuji dengan timestamp tersebut via `?debugbar_time={timestamp}`). Karena fix titik 1 SEKARANG BENAR menekan `prepare()` di production, langkah 1 tidak lagi menghasilkan `data-time` apa pun, sehingga precondition test gagal SEBELUM assertion `respond()` (`assertNotSame(200, ...)`) sempat dieksekusi — fix titik 2 (guard `Events.php`) tetap SECARA LOGIS independen benar terlepas dari ada/tidaknya timestamp (guard dieksekusi berdasarkan `ENVIRONMENT`, bukan bergantung hasil `prepare()`), namun test method ini SECARA MEKANIS tidak dapat mencapai assertion tersebut lagi dengan struktur saat ini. Ditandai untuk task 27.2 (verifikasi resmi, tugas terpisah) untuk investigasi/perbaikan instrumentasi runner jika diperlukan — TIDAK diubah di task 27.1 ini (scope fix, bukan scope test eksplorasi).
    - **Hasil test Klaster4Preservation** (`vendor/bin/phpunit tests/Bugfix/Klaster4PreservationTest.php --testdox`) — **4/4 method TETAP LULUS, TIDAK ADA REGRESI**: `testK3Skenario1...`, `testK3Skenario2...` (tidak tersentuh fix K4), `testK4Skenario1DevelopmentToolbarAktifPenuhDenganFileDebugTertulis` (development genuine tetap toolbar aktif penuh, file debugbar tertulis, `config.environment === 'development'`), `testK4Skenario2FilterPagecachePerformanceTidakTerpengaruhToolbarAktif` (dua request GET berturut tetap 307 + `debugbar_loader` pada kedua request, pagecache/performance tidak terganggu).
    - **Regression check tambahan** (`vendor/bin/phpunit tests/Bugfix/Klaster3PreservationTest.php tests/Bugfix/T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php tests/Bugfix/M1CspHeaderHttpIntegrationTest.php tests/Bugfix/K1SqlInjectionHttpIntegrationTest.php --testdox`) — **14/14 LULUS** (78 assertions, 0 failures), tidak ada regresi pada HTTP-integration test lain (seluruhnya berjalan `ENVIRONMENT=development` baik proses PHPUnit utama maupun server live yang di-curl, sehingga toolbar tetap aktif normal, tidak terpengaruh guard baru).
    - Verifikasi kebersihan: tidak ada proses `php -S` tersisa menyala pasca-run, file `writable/debugbar/*.json` yang ditulis selama test dibersihkan `tearDown()`.

  - [x] 27.2 Verifikasi test eksplorasi bug condition K4 sekarang lulus
    - **Property 1: Expected Behavior** - Toolbar Hanya Aktif di Development
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 24 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (ENVIRONMENT=testing tidak lagi mengembalikan data debug apa pun)
    - _Requirements: 2.17, 2.18_
    - **Gap instrumentasi test ditemukan + diperbaiki (task lanjutan, bukan redefinisi gate, mengikuti presedan format K3 26.1)**: `vendor/bin/phpunit tests/Bugfix/K4DebugToolbarExplorationTest.php --testdox` pasca-fix 27.1 menghasilkan 2/3 lulus — `testBugConditionRespondMelayaniDebugbarTimeDenganDataLengkapPadaEnvironmentProduction` gagal pada assertion PRECONDITION `assertArrayHasKey(1, $dataTimeMatches)` (baris 415), BUKAN pada assertion utama yang menguji `respond()`. Diagnosis: method ini men-chain 2 langkah (GET `/login` untuk memperoleh timestamp dari atribut `data-time` yang disuntikkan `prepare()` → GET `?debugbar_time={timestamp}` menguji `respond()`); asumsi "langkah 1 selalu menghasilkan timestamp" valid SEBELUM fix (kedua titik sama-sama bug) namun TIDAK LAGI valid PASCA-fix karena `prepare()` sekarang benar ditutup pada `ENVIRONMENT != development`, sehingga langkah 1 tidak lagi menghasilkan `data-time` apa pun. Investigasi source (`app/Config/Events.php`) + reproduksi manual (curl ke server test `ENVIRONMENT=production` genuine, tanpa file `writable/debugbar/*.json` apa pun) mengonfirmasi guard `if (ENVIRONMENT === 'development') { service('toolbar')->respond(); }` membungkus TITIK PANGGIL `respond()` itu sendiri — ketika `ENVIRONMENT != 'development'`, `respond()` tidak pernah dipanggil sama sekali, sehingga keberadaan/isi file debugbar sama sekali tidak relevan untuk assertion utama. Perbaikan: method diubah dari 2-langkah chaining menjadi SATU request tunggal ke `?debugbar_time={timestamp sintetis}` (`sprintf('%.6F', microtime(true))`, format persis pola `Toolbar::prepare()` vendor) — tanpa ketergantungan pada `prepare()`/file debugbar apa pun. Opsi A (tulis file JSON manual) dan Opsi B (server kedua `ENVIRONMENT=development` untuk hasilkan file) dievaluasi namun TIDAK dipilih karena keduanya terbukti tidak diperlukan (file tidak pernah dibaca ketika guard aktif). Substansi assertion utama (`assertNotSame(200, ...)` + `assertStringNotContainsString('kint-rich', ...)`) TIDAK diubah.
    - **Hasil test final**: `vendor/bin/phpunit tests/Bugfix/K4DebugToolbarExplorationTest.php --testdox` → **3/3 LULUS** (16 assertions). `vendor/bin/phpunit tests/Bugfix/Klaster4PreservationTest.php --testdox` → **4/4 TETAP LULUS** (40 assertions), tidak ada regresi. Regression check `vendor/bin/phpunit tests/Bugfix/Klaster3PreservationTest.php tests/Bugfix/T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php tests/Bugfix/M1CspHeaderHttpIntegrationTest.php --testdox` → **11/11 LULUS** (68 assertions), tidak ada regresi. Verifikasi kebersihan: tidak ada proses `php -S` tersisa menyala pasca-run, file `writable/debugbar/*.json` residu dibersihkan (jumlah kembali ke baseline).

  - [x] 27.3 Verifikasi seluruh test preservasi Klaster 4 masih lulus
    - **Property 2: Preservation** - Development Tetap Memakai Fallback dan Toolbar Penuh
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 25 — JANGAN tulis test baru
    - Jalankan test properti preservasi dari langkah 25
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi tidak ada regresi pada development/testing lokal)
    - Konfirmasi seluruh test masih lulus setelah fix (tidak ada regresi)
    - _Requirements: 3.8, 3.9, 3.10, 3.11, 3.12_

- [x] 28. Checkpoint Klaster 4 - Pastikan seluruh test K3/K4 lulus
  - Pastikan seluruh test lulus (eksplorasi K3 + eksplorasi K4 + preservasi), tanyakan ke user jika ada pertanyaan
  - **PENTING (operasional, di luar scope kode)**: Setelah checkpoint ini, ingatkan user bahwa rotasi `EULT_ENCRYPTION_LEGACY_KEY` di `.env` production adalah tindakan operasional terpisah yang menjadi tanggung jawab operator — TIDAK ada periode migrasi bertahap; seluruh link tiket lama (`cektiket/index/{kunci}`, `validitas/{kunci}`, `cektiket/cetakterima/{kunci}`) yang beredar akan langsung invalid begitu rotasi dilakukan (Requirements 2.16)

---

## R1: Audit Ulang esc() (Independen, Dapat Dikerjakan Paralel)

**Catatan**: Klaster ini TIDAK bergantung pada Klaster 1-4 dan dapat dikerjakan kapan saja secara paralel, karena scope-nya (audit binding view di `detail_user.php`) tidak menyentuh file/fungsi yang dimodifikasi klaster manapun di atas.

- [x] 29. Tulis test eksplorasi bug condition R1 (audit binding tanpa esc())
  - **Property 1: Bug Condition** - Audit Binding View Tanpa Escaping
  - **CATATAN KHUSUS R1**: Berbeda dari 11 bug lain, test ini pada kode SAAT INI diharapkan LULUS (bukan gagal) untuk 3 binding utama (`repliesMessage`, `detailHistory`, `ticketTrackingId`) — investigasi kode aktual mengonfirmasi ketiganya SUDAH terbungkus `esc()`. Task ini tetap ditulis sebagai audit formal, bukan diasumsikan berdasarkan requirement yang mengutip baris kode yang sudah tidak relevan (baris 141/152/203/246/257 saat ini berisi CSS, bukan output dinamis)
  - **GOAL**: Verifikasi status escaping SELURUH binding di `detail_user.php` yang berasal dari input pengguna publik (bukan hanya 3 binding yang disebut requirement)
  - Render `detail_user.php` dengan `repliesMessage = '<script>alert(1)</script>'` pada kode SAAT INI → assert output SUDAH ter-escape (test ini diharapkan LULUS, mengonfirmasi temuan verifikasi)
  - Render dengan payload XSS serupa pada `detailHistory` dan `ticketTrackingId` → assert output SUDAH ter-escape
  - Audit MENYELURUH seluruh interpolasi `<?= $variabel ?>`/`<?php echo $variabel ?>` di file ini untuk binding lain yang berasal dari data pengguna tidak langsung (`ticketName`, `ticketSubject`, `ticketMessage`, dll jika dirender di halaman ini) — untuk binding manapun yang DITEMUKAN tanpa `esc()`, catat sebagai counterexample nyata
  - Jalankan test pada kode SAAT INI (tidak ada "unfixed" dalam arti sama seperti bug lain — ini adalah audit status quo)
  - **EXPECTED OUTCOME**: Test LULUS untuk 3 binding utama (mengonfirmasi verifikasi); JIKA audit menemukan binding LAIN yang tidak diescape, itu menjadi counterexample nyata yang harus diperbaiki pada task 31
  - Dokumentasikan hasil audit lengkap (binding mana yang sudah aman vs yang perlu diperbaiki jika ada)
  - _Requirements: 1.31, 1.32, 1.33_

- [x] 30. Tulis test properti preservasi R1 (SEBELUM implementasi fix, jika ada)
  - **Property 2: Preservation** - Tampilan Teks Wajar dan Link Lampiran Tidak Rusak
  - **IMPORTANT**: Ikuti metodologi observation-first
  - Observasi pada kode SAAT INI: konten balasan chat berisi karakter HTML wajar (`<`, `>`, `&`) dalam teks normal (bukan payload XSS) → catat tampil benar secara visual (entity-encoded oleh `esc()` yang sudah terpasang, sudah terverifikasi berfungsi)
  - Observasi: lampiran file (`repliesFile`) ditautkan dalam balasan chat (`<a href="...">`) dirender terpisah dari `repliesMessage` pada baris yang sama → catat link tidak rusak
  - Tulis property-based test yang menangkap pola di atas sebagai baseline
  - Jalankan test pada kode SAAT INI
  - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi baseline yang sudah berfungsi benar)
  - _Requirements: 3.29, 3.30_

- [x] 31. Fix R1 (kondisional): Tambahkan esc() pada binding yang ditemukan belum aman saat audit
  - [x] 31.1 Implementasikan esc() pada binding yang ditemukan TIDAK aman (jika ada dari hasil audit task 29)
    - **CATATAN**: Task ini bersifat kondisional — HANYA berlaku pada binding yang benar-benar ditemukan tanpa `esc()` pada audit task 29 (3 binding utama yang disebut requirement SUDAH aman dan TIDAK memerlukan perubahan)
    - Untuk binding yang ditemukan tanpa `esc()`, bungkus dengan `esc()` sesuai konteks (`'html'` default untuk teks, `'attr'` untuk atribut HTML, `'js'` jika dirender ke dalam blok `<script>`) pada `app/Views/pages/ticketing/detail_user.php`
    - Dokumentasikan hasil audit (binding mana yang sudah aman vs yang diperbaiki) sebagai bagian dari catatan implementasi, karena requirement mengutip baris yang sudah tidak relevan dengan kode saat ini
    - _Bug_Condition: input.originatesFromPublicUserInput == true AND input.renderedAsRawHtml == true_
    - _Expected_Behavior: binding manapun yang ditemukan tanpa esc() SHALL dibungkus esc() sesuai konteks sehingga karakter HTML pada payload dirender sebagai teks, bukan dieksekusi_
    - _Preservation: karakter HTML wajar dalam teks normal tetap tampil benar visual setelah esc(); link lampiran chat tidak rusak_
    - _Requirements: 2.44, 2.45, 2.46_

  - [x] 31.2 Verifikasi test eksplorasi bug condition R1 sekarang lulus (untuk binding yang diperbaiki, jika ada)
    - **Property 1: Expected Behavior** - Audit Binding View
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 29 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS untuk SEMUA binding (baik yang sudah aman sejak awal maupun yang baru diperbaiki)
    - _Requirements: 2.44, 2.45, 2.46_

  - [x] 31.3 Verifikasi test preservasi R1 masih lulus
    - **Property 2: Preservation** - Tampilan Teks Wajar dan Link Lampiran Tidak Rusak
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 30 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (karakter HTML wajar tetap tampil benar; link lampiran tidak rusak)
    - _Requirements: 3.29, 3.30_

- [x] 32. Checkpoint R1 - Pastikan seluruh test audit lulus
  - Pastikan seluruh test lulus (eksplorasi/audit + preservasi), tanyakan ke user jika ada pertanyaan
  - Sertakan ringkasan hasil audit sebagai bagian dari laporan akhir: binding mana yang terverifikasi sudah aman sejak awal vs binding baru yang diperbaiki (jika ada)

---

## Task Dependency Graph

```json
{
  "waves": [
    {
      "wave": 1,
      "tasks": [1, 2, 29, 30],
      "description": "Eksplorasi + preservasi Klaster 1 (task 1,2) berjalan independen; sepenuhnya paralel dengan eksplorasi + preservasi R1 (task 29,30) karena scope file tidak overlap."
    },
    {
      "wave": 2,
      "tasks": [3, 31],
      "depends_on": {
        "3": [1, 2],
        "31": [29, 30]
      },
      "description": "Fix K1 (task 3) bergantung pada wave 1 K1; fix R1 kondisional (task 31) bergantung pada wave 1 R1. Kedua fix paralel karena file berbeda (Cektiket.php/Login.php/ModelMaster.php vs detail_user.php)."
    },
    {
      "wave": 3,
      "tasks": [4, 32],
      "depends_on": {
        "4": [3],
        "32": [31]
      },
      "description": "Checkpoint Klaster 1 (task 4) paralel dengan Checkpoint R1 (task 32) — jalur R1 selesai total di wave ini, tidak muncul lagi di wave berikutnya."
    },
    {
      "wave": 4,
      "tasks": [5, 6, 7],
      "depends_on": {
        "5": [4],
        "6": [4],
        "7": [4]
      },
      "description": "Eksplorasi K2/M2 (5), eksplorasi T4 (6), dan preservasi gabungan Klaster 2 (7) bergantung pada checkpoint Klaster 1 karena akan menerapkan ulang pola array binding pada file yang sama (Cektiket.php)."
    },
    {
      "wave": 5,
      "tasks": [8, 9, 10],
      "depends_on": {
        "8": [5, 7],
        "9": [6, 7],
        "10": [5, 7]
      },
      "description": "Fix K2 (8), fix T4 (9), fix M2 (10) — ketiganya menyentuh Cektiket.php/Ticketing.php dengan overlap method pada task 8/9 (rating, loadpdf); dikerjakan pada wave yang sama dengan koordinasi manual, tidak strictly sequential secara file karena method berbeda."
    },
    {
      "wave": 6,
      "tasks": [11],
      "depends_on": {
        "11": [8, 9, 10]
      },
      "description": "Checkpoint Klaster 2 — gate sebelum Klaster 3."
    },
    {
      "wave": 7,
      "tasks": [12, 13, 14, 15, 16, 17],
      "depends_on": {
        "12": [11],
        "13": [11],
        "14": [11],
        "15": [11],
        "16": [11],
        "17": [11]
      },
      "description": "Eksplorasi T1/T2/T3/M1/M3 (12-16) dan preservasi gabungan Klaster 3 (17) bergantung pada checkpoint Klaster 2 sesuai urutan design.md (Cektiket.php/Login.php disentuh lagi oleh filter CSRF di Klaster 3)."
    },
    {
      "wave": 8,
      "tasks": [18, 19],
      "depends_on": {
        "18": [12, 17],
        "19": [13, 17]
      },
      "description": "Fix T1 (18, Filters.php+login.php) paralel dengan fix T2 (19, eult_captcha_helper.php+Login.php: captcha gambar+rate limiting). M3 tidak punya task fix terpisah di wave ini (memakai ulang ThrottleFilter dari task 19.2)."
    },
    {
      "wave": 9,
      "tasks": [20],
      "depends_on": {
        "20": [14, 17, 19]
      },
      "description": "Fix T3 (20, eult_captcha_helper.php) dijadwalkan SETELAH task 19 (bukan paralel) karena keduanya memodifikasi file yang sama — T2 menambah eult_captcha_image()/rate limiting, T3 menghapus fallback cookie di eult_captcha_check() pada file yang sama; sequencing ini menghindari conflict edit meski kedua fix secara logis independen."
    },
    {
      "wave": 10,
      "tasks": [21],
      "depends_on": {
        "21": [18]
      },
      "description": "Fix M1 (CSP) bergantung pada task 18 karena tiga header non-CSP muncul sebagai efek samping secureheaders yang diaktifkan di T1."
    },
    {
      "wave": 11,
      "tasks": [22],
      "depends_on": {
        "22": [18, 19, 20, 21]
      },
      "description": "Checkpoint Klaster 3 — gate sebelum Klaster 4, memverifikasi integrasi CSRF+captcha gambar+rate limit+CSP tidak saling menghalangi."
    },
    {
      "wave": 12,
      "tasks": [23, 24, 25],
      "depends_on": {
        "23": [22],
        "24": [22],
        "25": [22]
      },
      "description": "Eksplorasi K3 (23), eksplorasi K4 (24), preservasi gabungan Klaster 4 (25) bergantung pada checkpoint Klaster 3 mengikuti urutan design.md meski tidak ada dependency teknis langsung (file berbeda: Enkripsi.php/toolbar filter vs Filters.php/App.php/captcha helper)."
    },
    {
      "wave": 13,
      "tasks": [26, 27],
      "depends_on": {
        "26": [23, 25],
        "27": [24, 25]
      },
      "description": "Fix K3 (Enkripsi.php) paralel dengan fix K4 (filter toolbar custom) — file berbeda, tidak ada overlap."
    },
    {
      "wave": 14,
      "tasks": [28],
      "depends_on": {
        "28": [26, 27]
      },
      "description": "Checkpoint Klaster 4 — task terakhir keseluruhan pipeline (jalur R1 sudah tuntas di wave 3, tidak lagi berkontribusi pada wave ini)."
    }
  ]
}
```

Skema di atas: setiap wave berisi task ID yang secara teori dapat dieksekusi paralel di dalam wave tersebut, dengan pengecualian task 20 yang sengaja ditempatkan pada wave tersendiri (wave 9) SETELAH task 19 (wave 8) karena file overlap `eult_captcha_helper.php` — lihat catatan pada wave 9. `depends_on` memetakan setiap task ke task/wave sebelumnya yang menjadi prasyaratnya. Diagram ASCII di bawah ini menyajikan penjelasan visual yang sama dengan penekanan pada dua jalur utama (Klaster 1→2→3→4 dan R1 independen).

```
Klaster 1 (K1)                    R1 (Audit esc())
   │                                 │
   │ 1 → 2 → 3(3.1-3.4) → 4          │ 29 → 30 → 31(31.1-31.3) → 32
   │  [prasyarat pola aman]          │  [independen, paralel dengan
   ▼   array binding dipakai         │   Klaster 1-4 kapan pun]
Klaster 2 (K2/T4/M2)                 │
   │                                 │
   │ 5,6 → 7 → 8(8.1-8.4),           │
   │       9(9.1-9.2),               │
   │       10(10.1-10.3) → 11        │
   │  [K2/T4 pakai ulang array       │
   │   binding dari Klaster 1]       │
   ▼                                 │
Klaster 3 (T1/T2/T3/M1/M3)           │
   │ 12,13,14,15,16 → 17 →           │
   │   18(18.1-18.4),                │
   │   19(19.1-19.3),                │
   │   20(20.1-20.2),                │
   │   21(21.1-21.3) → 22            │
   │  [M1 CSP bangun di atas         │
   │   secureheaders T1;             │
   │   M3 pakai ulang ThrottleFilter │
   │   T2]                           │
   ▼                                 │
Klaster 4 (K3/K4)                    │
   │ 23,24 → 25 →                    │
   │   26(26.1-26.2),                │
   │   27(27.1-27.3) → 28            │
   │  [independen secara teknis dari │
   │   Klaster 1-3, ditempatkan      │
   │   terakhir sesuai urutan        │
   │   disarankan design.md]         │
   ▼                                 ▼
              Selesai (semua checkpoint lulus)
```

**Catatan ketergantungan:**
- **Klaster 1 → Klaster 2**: Klaster 2 (task 8.1, task 9.1) menerapkan pola array binding yang SAMA seperti Klaster 1 pada method `Cektiket::loadpdf()` dan `Cektiket::rating()` (file yang sama, `Cektiket.php`) — mengerjakan Klaster 1 lebih dulu memastikan pola sudah solid sebelum dipakai ulang.
- **Klaster 2 → Klaster 3**: Tidak ada dependensi teknis langsung (file berbeda), namun urutan disarankan design.md menempatkan Klaster 3 setelah Klaster 2 karena Klaster 3 menyentuh `Cektiket.php`/`Login.php` juga (filter CSRF membungkus method yang sama seperti `rating()` yang diubah di Klaster 2) — mengerjakan berurutan mengurangi risiko conflict saat implementasi.
- **Dalam Klaster 3**: task 21 (M1/CSP) bergantung pada task 18.1 (`secureheaders` aktif) karena tiga header non-CSP (`X-Frame-Options`, dst) muncul sebagai efek samping aktivasi `secureheaders`. Task 19.3 (M3 rate limit) memakai ulang `ThrottleFilter` yang dibuat di task 19.2 (T2) — keduanya berada di task yang sama (19) karena mekanismenya identik.
- **Klaster 3 → Klaster 4**: Tidak ada dependensi teknis (file berbeda: `Filters.php`/`App.php`/captcha helper vs `Enkripsi.php`/toolbar filter) — ditempatkan setelah Klaster 3 murni mengikuti urutan yang disarankan design.md Overview (klaster lebih akhir menumpuk di atas klaster sebelumnya secara organisasi kerja, bukan dependensi kode).
- **R1**: Sepenuhnya independen dari Klaster 1-4 (scope file `detail_user.php` untuk binding esc() tidak overlap dengan file yang diubah klaster manapun) — dapat dimulai kapan saja, termasuk sebelum, sejajar, atau setelah Klaster 1-4.
- **Checkpoint (4, 11, 22, 28, 32)**: Masing-masing adalah gate — lanjut ke klaster berikutnya hanya setelah checkpoint klaster saat ini lulus, KECUALI R1 yang dapat berjalan independen dari gate manapun.

---

## Notes

Catatan operasional yang relevan lintas task, dirangkum di satu tempat:

- **Rotasi kunci K3 (task 28)**: Setelah checkpoint Klaster 4 lulus, ingatkan user bahwa rotasi `EULT_ENCRYPTION_LEGACY_KEY` di `.env` production adalah tindakan operasional terpisah yang menjadi tanggung jawab operator — TIDAK ada periode migrasi bertahap. Seluruh link tiket lama (`cektiket/index/{kunci}`, `validitas/{kunci}`, `cektiket/cetakterima/{kunci}`) yang beredar akan langsung invalid begitu rotasi dilakukan (Requirements 2.16).
- **M3 tanpa task fix terpisah (task 22)**: M3 (enumerasi tiket tanpa rate limit) tidak memerlukan perubahan kode terpisah — bergantung pada `ThrottleFilter` yang sama dari task 19.2 (T2) yang diterapkan ke route `login/cektiket`.
- **R1 independen (task 29-32)**: Klaster R1 TIDAK bergantung pada Klaster 1-4 dan dapat dikerjakan kapan saja secara paralel, karena scope-nya (audit binding view di `detail_user.php`) tidak menyentuh file/fungsi yang dimodifikasi klaster manapun. Task 29 (eksplorasi) diharapkan LULUS (bukan gagal) untuk 3 binding utama yang sudah terverifikasi aman — task 31 (fix) bersifat kondisional, hanya berlaku jika audit menemukan binding lain yang belum diescape.
- **File overlap T2/T3 (task 19-20)**: Task 19 (T2, captcha gambar + rate limiting) dan task 20 (T3, hapus fallback cookie captcha) sama-sama memodifikasi `app/Helpers/eult_captcha_helper.php`. Keduanya ditandai sequential pada dependency graph untuk menghindari conflict edit pada file yang sama, meski secara logis kedua fix independen satu sama lain.
- **Verifikasi route baru (task 11)**: Route baru (`cektiket/loadpdf/(:kunci)`, `cektiket/loadattach/(:kunci)/(:namaFile)`, `validitas/loadpdf/(:kunci)`, `cektiket/rating/(:any)` atau field POST `kunci`) harus terdaftar dengan benar di `app/Config/Routes.php` sebelum checkpoint Klaster 2 dianggap lulus.
