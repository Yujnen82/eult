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

- [ ] 3. Fix K1: Ganti kondisi WHERE string mentah dengan array binding
  - [x] 3.1 Implementasikan fix pada `Cektiket::rating()`
    - Ganti `$this->tiket->byId("ticketTrackingId = '" . $nomorTiket . "'")` menjadi `$this->tiket->byId(['ticketTrackingId' => $nomorTiket])` (`app/Controllers/Cektiket.php`)
    - Ganti `ambilSatu('d_archive', "archiveTrackingId = '" . $nomorTiket . "' AND (archiveJenis = 'OUTPUT' or archiveJenis = 'TTD')")` menjadi kondisi array (`['archiveTrackingId' => $nomorTiket]`) dikombinasikan dengan `whereIn('archiveJenis', ['OUTPUT', 'TTD'])` pada level query builder
    - _Bug_Condition: input.source IN ['Cektiket::rating'] AND input.kondisiType == 'string' AND containsUserControlledValue(input.kondisi) AND NOT isParameterBound(input.kondisi)_
    - _Expected_Behavior: query HANYA mengembalikan baris exact-match, 0 baris untuk nilai tidak ada, tidak pernah SQL error akibat metacharacter_
    - _Preservation: nomorTiket valid tetap mengembalikan data rating/tiket yang sesuai, insert/update d_rating dan email selesai() tidak berubah_
    - _Requirements: 2.1, 2.2_

  - [-] 3.2 Implementasikan fix pada `Login::savetiket()` dan helper terkait
    - Ganti `byId("ticketTrackingId = '" . $idTiket . "'")` (readback pasca-insert) menjadi `byId(['ticketTrackingId' => $idTiket])` (`app/Controllers/Login.php`)
    - Ubah signature `eult_auto_increment()` agar menerima kondisi sebagai `array` (`function eult_auto_increment(string $tabel, string $kolom, string $nip, array $kondisi): string`) dan update caller: `eult_auto_increment('d_archive', 'archiveId', $arsipId, ['archiveTrackingId' => $idTiket])` (`app/Helpers/eult_kode_helper.php`)
    - Verifikasi `ModelMaster::getByLastId()` menerima `array` dari seluruh caller baru; pertahankan dukungan `string` HANYA untuk caller internal yang tidak menerima input publik (agar Preservation 3.3 tidak dilanggar), tandai deprecated-for-public-input pada docblock (`app/Models/ModelMaster.php`)
    - _Bug_Condition: input.source IN ['Login::savetiket', 'Login::index-readback'] AND input.kondisiType == 'string' AND containsUserControlledValue(input.kondisi) AND NOT isParameterBound(input.kondisi)_
    - _Expected_Behavior: query HANYA mengembalikan baris exact-match, tidak pernah SQL error akibat metacharacter_
    - _Preservation: archiveId berurutan tetap benar, method model lain yang memakai escapeString() manual (getDisposisiById(), dll) tidak disentuh_
    - _Requirements: 2.3, 2.4, 2.5_

  - [~] 3.3 Verifikasi test eksplorasi bug condition sekarang lulus
    - **Property 1: Expected Behavior** - SQL Injection via Kondisi String Mentah
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 1 — JANGAN tulis test baru
    - Test dari task 1 mengenkode expected behavior; ketika lulus, ini mengonfirmasi expected behavior terpenuhi
    - Jalankan test eksplorasi bug condition dari langkah 1
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi bug telah diperbaiki — payload SQLi diperlakukan sebagai literal string)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [~] 3.4 Verifikasi test preservasi masih lulus
    - **Property 2: Preservation** - Query Valid Tetap Berfungsi Normal
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 2 — JANGAN tulis test baru
    - Jalankan test properti preservasi dari langkah 2
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi tidak ada regresi pada query valid)
    - Konfirmasi seluruh test masih lulus setelah fix (tidak ada regresi)
    - _Requirements: 3.1, 3.2, 3.3_

- [~] 4. Checkpoint Klaster 1 - Pastikan seluruh test K1 lulus
  - Pastikan seluruh test lulus (eksplorasi + preservasi), tanyakan ke user jika ada pertanyaan
  - Klaster 1 adalah prasyarat pola aman yang dipakai ulang di Klaster 2 (K2/T4 menerapkan array binding yang sama pada method yang sama) — pastikan pola ini solid sebelum lanjut

---

## Klaster 2: Kepemilikan Berkas & Rating (K2, T4, M2)

- [~] 5. Tulis test eksplorasi bug condition K2/M2 (endpoint download tanpa validasi kepemilikan)
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

- [~] 6. Tulis test eksplorasi bug condition T4 (rating tanpa validasi kepemilikan)
  - **Property 1: Bug Condition** - Rating Tanpa Validasi Kepemilikan Tiket
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample bahwa `Cektiket::rating()` beroperasi murni dari `nomorTiket` POST body tanpa decode kunci apa pun
  - POST ke `rating()` dengan `nomorTiket` tebakan/diketahui (tanpa kunci terenkripsi apa pun) pada kode asli → assert rating tersimpan di `d_rating` DAN email `PengirimEmail::selesai()` terpicu ke `ticketEmail` tiket tersebut (`app/Controllers/Cektiket.php:106-125`)
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan siapa pun yang menebak nomorTiket dapat memicu rating+email tanpa memegang kunci)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.23, 1.24_

- [~] 7. Tulis test properti preservasi K2/T4/M2 (SEBELUM implementasi fix)
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

- [ ] 8. Fix K2: Kepemilikan berkas via kunci terenkripsi pada endpoint publik
  - [~] 8.1 Implementasikan fix pada `Cektiket::loadpdf()`
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

  - [~] 8.2 Implementasikan fix pada `Cektiket::loadattach()`
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

  - [~] 8.3 Implementasikan fix pada `Validitas::loadpdf()`
    - Ubah signature dari `loadpdf(string $namaFile)` menjadi `loadpdf(string $kunci)`
    - Decode `$kunci`, query `d_archive` dengan `archiveTrackingId` hasil decode dan `archiveJenis` yang relevan
    - HANYA serve jika kecocokan valid; kunci tidak valid → 403/404
    - Route `validitas/loadpdf/(:any)` tetap satu segment (kunci); update caller pemanggil link di view agar mengirim kunci yang sama dengan halaman validitas
    - Pertahankan `basename()` sebagai lapisan tambahan
    - _Bug_Condition: input.endpoint == 'Validitas::loadpdf' AND input.pathSegment == rawFileName AND NOT ownershipValidated(input.pathSegment)_
    - _Expected_Behavior: HANYA menyajikan konten ketika kunci dapat didecode dan berasosiasi dengan archiveFile yang benar_
    - _Preservation: pemegang kunci valid hasil scan QR tetap dapat download PDF terkait_
    - _Requirements: 2.9, 2.10_

  - [~] 8.4 Verifikasi test eksplorasi bug condition K2 sekarang lulus
    - **Property 1: Expected Behavior** - IDOR pada Endpoint Download Berkas
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 5 (bagian K2 publik) — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi endpoint publik memvalidasi kepemilikan via kunci)
    - _Requirements: 2.6, 2.7, 2.8, 2.9, 2.10_

- [ ] 9. Fix T4: Rating memakai kunci terenkripsi (bukan nomorTiket mentah)
  - [~] 9.1 Implementasikan fix pada `Cektiket::rating()`
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

  - [~] 9.2 Verifikasi test eksplorasi bug condition T4 sekarang lulus
    - **Property 1: Expected Behavior** - Rating Tanpa Validasi Kepemilikan Tiket
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 6 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi rating hanya beroperasi atas hasil decode kunci, kunci tidak valid tidak memicu side-effect apa pun)
    - _Requirements: 2.34, 2.35, 2.36, 2.37_

- [ ] 10. Fix M2: Ownership check pada `Ticketing::loadpdf()`/`loadattach()` (admin)
  - [~] 10.1 Implementasikan fix pada `Ticketing::loadpdf()`/`Ticketing::loadattach()`
    - Tambahkan pengecekan ownership SEBELUM serve: ambil `logged_in` session (`susrSgroupNama`)
    - Jika grup `ADMIN`/`OPERATOR*` → lanjutkan tanpa batasan tambahan (konsisten pola existing `Ticketing.php:89-91`)
    - Jika grup lain → decode/cari tiket pemilik file terkait (lookup `archiveTrackingId`/`repliesTicketId` dari nama file), lalu cek disposisi/unit staf terhadap tiket tersebut menggunakan `s_user_group_unit`/`d_disposisi` yang SUDAH ADA (bukan mekanisme baru)
    - Jika tidak match → 403, JANGAN sajikan konten berkas
    - Filter `auth` itu sendiri TIDAK diubah — hanya menambah validasi di dalam body controller setelah filter lolos
    - _Bug_Condition: input.endpoint IN ['Ticketing::loadpdf', 'Ticketing::loadattach'] AND input.staffAuthenticated == true AND NOT staffHasOwnershipOfAssociatedTicket(input.staffGroup, input.fileOwnerTicket)_
    - _Expected_Behavior: staf non-owner (bukan ADMIN/OPERATOR, tanpa disposisi) → 403; staf admin/operator/dengan disposisi sah → tetap 200_
    - _Preservation: staf admin/operator/staf dengan disposisi sah tetap akses berkas tanpa gangguan; mekanisme filter auth tidak berubah_
    - _Requirements: 2.11, 2.40, 2.41_

  - [~] 10.2 Verifikasi test eksplorasi bug condition M2 sekarang lulus
    - **Property 1: Expected Behavior** - IDOR Admin loadpdf/loadattach
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 5 (bagian M2 admin) — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (staf non-owner unit A ditolak 403 mengakses file unit B; staf admin tetap 200)
    - _Requirements: 2.40, 2.41_

  - [~] 10.3 Verifikasi seluruh test preservasi Klaster 2 masih lulus
    - **Property 2: Preservation** - Pemilik Sah Tetap Dapat Mengunduh dan Rating
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 7 — JANGAN tulis test baru
    - Jalankan test properti preservasi dari langkah 7 (download publik, download admin, rating)
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi tidak ada regresi pada pemilik/staf sah)
    - Konfirmasi seluruh test masih lulus setelah fix (tidak ada regresi)
    - _Requirements: 3.4, 3.5, 3.6, 3.7, 3.21, 3.22, 3.25, 3.26_

- [~] 11. Checkpoint Klaster 2 - Pastikan seluruh test K2/T4/M2 lulus
  - Pastikan seluruh test lulus (eksplorasi K2 + eksplorasi T4 + eksplorasi M2 + preservasi), tanyakan ke user jika ada pertanyaan
  - Verifikasi route baru (`cektiket/loadpdf/(:kunci)`, `cektiket/loadattach/(:kunci)/(:namaFile)`, `validitas/loadpdf/(:kunci)`, `cektiket/rating/(:any)` atau field POST `kunci`) terdaftar dengan benar di `app/Config/Routes.php`

---

## Klaster 3: Pertahanan Perimeter (T1, T2, T3, M1, M3)

- [~] 12. Tulis test eksplorasi bug condition T1 (CSRF/filter global dinonaktifkan)
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

- [~] 13. Tulis test eksplorasi bug condition T2 (captcha kosmetik + tidak ada rate limit)
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

- [~] 14. Tulis test eksplorasi bug condition T3 (cookie captcha bypass)
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

- [~] 15. Tulis test eksplorasi bug condition M1 (header keamanan tidak lengkap)
  - **Property 1: Bug Condition** - Header Keamanan Browser Tidak Lengkap
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample bahwa header CSP/X-Frame-Options absen
  - Assert response HTTP halaman apa pun (publik/admin) pada kode asli TIDAK mengandung header `Content-Security-Policy`/`X-Frame-Options` (`$CSPEnabled = false` di `app/Config/App.php:192`)
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan header keamanan absen)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.25, 1.26_

- [~] 16. Tulis test eksplorasi bug condition M3 (enumerasi tiket tanpa rate limit)
  - **Property 1: Bug Condition** - Enumerasi Nomor Tiket Tanpa Rate Limit
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample enumerasi masif nomor tiket tanpa hambatan
  - Kirim 100 (atau 10.000 sesuai skenario bugfix.md) request enumerasi nomor tiket beruntun ke `Login::cektiket()` (`app/Controllers/Login.php:56-81`) pada kode asli → assert SELURUHNYA diproses dan pesan "ditemukan"/"tidak ditemukan" tetap dapat dibedakan tanpa hambatan
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan tidak ada rate limit pada enumerasi)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.29, 1.30_

- [~] 17. Tulis test properti preservasi Klaster 3 (SEBELUM implementasi fix)
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

- [ ] 18. Fix T1: Aktifkan CSRF, secureheaders, honeypot, invalidchars
  - [~] 18.1 Aktifkan filter global di `app/Config/Filters.php`
    - Uncomment `'csrf'`, `'invalidchars'` di `$globals['before']`
    - Uncomment `'secureheaders'` di `$globals['after']`
    - JANGAN tambahkan `except`/exclude URI apa pun kecuali benar-benar diperlukan DAN dikonfirmasi user terlebih dahulu (default: tidak ada exclude)
    - _Bug_Condition: input.method == 'POST' AND input.route IN [...] AND NOT globalFilterActive('csrf') AND NOT formContainsCsrfToken(input.origin)_
    - _Requirements: 2.21, 2.25_

  - [~] 18.2 Tambahkan CSRF token pada form dan JavaScript AJAX
    - Tambahkan `<?= csrf_field() ?>` di dalam setiap elemen `<form>` yang mengirim POST pada `app/Views/layouts/login.php`
    - Sebelum setiap `$.ajax()`/`fetch()` POST ke `login/savetiket`, `login/cektiket`, `cektiket/save_replies`, `cektiket/rating`, tambahkan header `X-CSRF-TOKEN` dengan nilai token terkini
    - Setelah setiap submit sukses, update nilai token dari respons server (mengikuti `$regenerate = true`)
    - Verifikasi kompatibilitas field honeypot otomatis CI4 dengan struktur form existing setelah `'honeypot'` diaktifkan
    - _Expected_Behavior: request dengan token CSRF valid diproses normal; tanpa token ditolak graceful_
    - _Requirements: 2.22, 2.23, 2.26_

  - [~] 18.3 Tambahkan graceful handler untuk kegagalan CSRF pada request AJAX
    - Tambahkan handler untuk `CSRFException`/kegagalan validasi CSRF pada request AJAX (deteksi via header `X-Requested-With`/`Accept: application/json`) di `app/Controllers/BaseController.php` atau exception handler khusus
    - Kembalikan JSON graceful (`{'status': 'danger', 'message': '...'}`) alih-alih HTML error page mentah
    - _Bug_Condition: request AJAX tanpa token CSRF valid_
    - _Expected_Behavior: response JSON graceful, dapat ditangani JS existing tanpa exception 500 mentah_
    - _Preservation: struktur respons JSON existing (status, message, redirect_url, new_captcha) tidak berubah kontraknya_
    - _Requirements: 2.24_

  - [~] 18.4 Verifikasi test eksplorasi bug condition T1 sekarang lulus
    - **Property 1: Expected Behavior** - Filter Keamanan Global Dinonaktifkan
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 12 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (POST tanpa token CSRF ditolak graceful, tidak diproses penuh)
    - _Requirements: 2.21, 2.22, 2.23, 2.24, 2.25, 2.26_

- [ ] 19. Fix T2/M3: Captcha gambar + rate limiting
  - [~] 19.1 Implementasikan captcha sebagai gambar
    - Tambahkan fungsi baru `eult_captcha_image(string $teks): string` (binary PNG) menggunakan GD (`imagecreate`, `imagestring`/`imagettftext` dengan distorsi garis/noise); deteksi `extension_loaded('gd')` dengan fallback Imagick (`app/Helpers/eult_captcha_helper.php`)
    - `eult_captcha_generate()` TETAP menyimpan nilai ke `session()->set('captcha', ...)` — TIDAK berubah
    - Tambahkan method `captchaImage()` di `Login.php` yang generate/baca teks captcha dari session, render via `eult_captcha_image()`, return response `Content-Type: image/png`
    - Ubah `refreshCaptcha()` agar respons berisi URL gambar (`captcha_image_url`) alih-alih string captcha polos
    - Ubah respons `new_captcha` pada `Otentifikasi::index()`, `Login::savetiket()` dari string captcha menjadi URL endpoint gambar
    - Ganti `<span class="captcha-display">` menjadi `<img src="{captcha_image_url}">` di `app/Views/layouts/login.php`
    - _Bug_Condition: input.type == 'render' AND capchaRenderedAsPlainDomText(input)_
    - _Expected_Behavior: nilai captcha session TIDAK PERNAH terkirim plaintext ke klien (Property 13); hanya URL gambar_
    - _Preservation: captcha benar (dari gambar) tetap diterima; refresh captcha tetap berfungsi (beda format saja)_
    - _Requirements: 2.27, 2.28_

  - [~] 19.2 Implementasikan rate limiting per-IP
    - Buat file `app/Config/Throttle.php` (bawaan CI4, belum ada di codebase) dengan definisi rate `Services::throttler()`
    - Buat filter custom `App\Filters\ThrottleFilter` yang memanggil `Services::throttler()->check($ip, $limit, $seconds)`, kembalikan 429 jika `false`
    - Terapkan pada route `otentifikasi`, `login/savetiket`, `login/cektiket` via `$filters` (BUKAN `$globals`, agar hanya route ini dibatasi) di `app/Config/Filters.php`
    - _Bug_Condition: input.type == 'request' AND input.route IN AUTH_AND_TICKET_ROUTES AND NOT rateLimitEnforced(input.ip)_
    - _Expected_Behavior: request ke-(N+1) dalam window yang sama dari IP sama → 429; request ke-N tetap diproses normal_
    - _Preservation: rate limit tidak memblokir pengguna wajar; ambang batas ditetapkan longgar untuk normal namun ketat untuk brute force; pesan "ditemukan"/"tidak ditemukan" pada login/cektiket TIDAK diubah menjadi generik — rate limiting (bukan penyamaran pesan) adalah kontrol primer untuk M3_
    - _Requirements: 2.29, 2.30, 2.42, 2.43_

  - [~] 19.3 Verifikasi test eksplorasi bug condition T2 dan M3 sekarang lulus
    - **Property 1: Expected Behavior** - Captcha Gambar + Rate Limiting, Enumerasi Dibatasi
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 13 dan task 16 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (captcha tidak lagi terbaca dari DOM sebagai plaintext; request ke-(N+1) mengembalikan 429; enumerasi tiket dibatasi rate limit yang sama)
    - _Requirements: 2.27, 2.28, 2.29, 2.30, 2.42_

- [ ] 20. Fix T3: Hapus fallback cookie captcha
  - [~] 20.1 Implementasikan fix pada `eult_captcha_check()`
    - Hapus baris `$tersimpan = get_cookie('captcha_code'); if (! $tersimpan) { ... }` SEPENUHNYA dari `app/Helpers/eult_captcha_helper.php`
    - Ganti langsung dengan `$tersimpan = session()->get('captcha');`
    - Sisa logika (`is_string`/`strtoupper` comparison) TIDAK berubah
    - Update docblock fungsi, hapus kalimat "Kompatibel dengan cookie lama 'captcha_code' bila masih ada"
    - Ini adalah keputusan final tanpa periode transisi — dihapus total, bukan dikondisikan
    - _Bug_Condition: input.clientCookie['captcha_code'] IS SET AND input.clientCookie['captcha_code'] != input.serverSession['captcha'] AND strtoupper(input.userInput) == strtoupper(input.clientCookie['captcha_code'])_
    - _Expected_Behavior: hasil eult_captcha_check(input) SHALL identik dengan hasil yang HANYA berdasarkan session()->get('captcha') — cookie sama sekali tidak dibaca_
    - _Preservation: captcha benar sesuai session tetap lolos (case-insensitive); captcha salah tetap gagal, tidak terpengaruh cookie apa pun_
    - _Requirements: 2.31, 2.32, 2.33_

  - [~] 20.2 Verifikasi test eksplorasi bug condition T3 sekarang lulus
    - **Property 1: Expected Behavior** - Cookie Captcha Tidak Lagi Membypass
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 14 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (cookie `captcha_code=ABCD` tidak lagi mempengaruhi hasil; hanya session yang dipakai)
    - _Requirements: 2.31, 2.32, 2.33_

- [ ] 21. Fix M1: Aktifkan CSP dan header lengkap
  - [~] 21.1 Implementasikan CSP
    - Ubah `$CSPEnabled = false` menjadi `true` di `app/Config/App.php`
    - Konfigurasi whitelist minimal di `app/Config/ContentSecurityPolicy.php` mencakup sumber daya yang benar-benar dipakai (`self`, CDN font/JS tema `login.php`/`detail_user.php` — audit saat implementasi untuk daftar domain pasti berdasarkan observasi task 17)
    - Izinkan `unsafe-inline` pada `styleSrc`/`scriptSrc` HANYA jika inline script/style memang dipakai existing dan TIDAK direfaktor sebagai bagian scope M1 ini
    - `secureheaders` sudah aktif dari task 18.1 — header `X-Frame-Options`/`X-Content-Type-Options`/`Referrer-Policy` otomatis muncul sebagai efek samping
    - _Bug_Condition: NOT input.headers.contains('Content-Security-Policy') AND NOT input.headers.contains('X-Frame-Options')_
    - _Expected_Behavior: response HTTP SHALL menyertakan Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Referrer-Policy_
    - _Preservation: seluruh aset statis existing tetap termuat; fitur JS (AJAX, refresh captcha, chat) tetap jalan tanpa error CSP_
    - _Requirements: 2.38, 2.39_

  - [~] 21.2 Verifikasi test eksplorasi bug condition M1 sekarang lulus
    - **Property 1: Expected Behavior** - Header Keamanan Lengkap
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 15 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (response mengandung CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
    - _Requirements: 2.38, 2.39_

  - [~] 21.3 Verifikasi seluruh test preservasi Klaster 3 masih lulus
    - **Property 2: Preservation** - Request Sah, Captcha Benar, Aset CSP, Pengguna Wajar Tidak Terblokir
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 17 — JANGAN tulis test baru
    - Jalankan test properti preservasi dari langkah 17 (CSRF valid, captcha benar dari gambar, aset CSP, rate limit wajar)
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi tidak ada regresi lintas T1/T2/T3/M1/M3)
    - Konfirmasi seluruh test masih lulus setelah fix (tidak ada regresi)
    - _Requirements: 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20, 3.23, 3.24, 3.27, 3.28_

- [~] 22. Checkpoint Klaster 3 - Pastikan seluruh test T1/T2/T3/M1/M3 lulus
  - Pastikan seluruh test lulus (5 eksplorasi + 1 preservasi gabungan), tanyakan ke user jika ada pertanyaan
  - Verifikasi integrasi antar-fix: form dengan CSRF token + captcha gambar + rate limit + CSP aktif secara bersamaan tidak saling menghalangi flow submit tiket end-to-end
  - Catatan operasional: M3 tidak memerlukan perubahan kode terpisah — bergantung pada `ThrottleFilter` yang sama dari task 19.2 diterapkan ke route `login/cektiket`

---

## Klaster 4: Konfigurasi & Observability (K3, K4)

- [~] 23. Tulis test eksplorasi bug condition K3 (fallback hardcode production)
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

- [~] 24. Tulis test eksplorasi bug condition K4 (toolbar debug publik)
  - **Property 1: Bug Condition** - Toolbar Debug Aktif Tanpa Syarat Environment
  - **CRITICAL**: Test ini HARUS GAGAL pada kode belum diperbaiki
  - **DO NOT attempt to fix the test or the code when it fails**
  - **GOAL**: Surface counterexample bahwa toolbar aktif di environment non-development
  - Set `ENVIRONMENT=testing` (setara production untuk tujuan fix ini), request `?debugbar_time={ts}` pada kode asli (`app/Config/Filters.php:64`, filter `toolbar` di `required.after`) → assert response mengandung data debug (session admin, riwayat SQL, `ticketEmail` pengguna lain)
  - Jalankan test pada kode BELUM diperbaiki
  - **EXPECTED OUTCOME**: Test GAGAL (membuktikan toolbar aktif tanpa memeriksa environment)
  - Dokumentasikan counterexample yang ditemukan
  - _Requirements: 1.12, 1.13, 1.14_

- [~] 25. Tulis test properti preservasi Klaster 4 (SEBELUM implementasi fix)
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

- [ ] 26. Fix K3: Hapus fallback hardcode dari jalur production (keputusan final, tanpa transisi)
  - [~] 26.1 Implementasikan fix pada `Enkripsi::kunciLegacy()`
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

  - [~] 26.2 Verifikasi test eksplorasi bug condition K3 sekarang lulus
    - **Property 1: Expected Behavior** - Fallback Hardcode Tidak Aktif di Production
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 23 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (production dengan kunci kosong/hardcode gagal terkontrol; production dengan kunci sama fallback ter-log critical)
    - _Requirements: 2.12, 2.13, 2.14, 2.15_

- [ ] 27. Fix K4: Kunci toolbar hanya ke environment development (keputusan final)
  - [~] 27.1 Implementasikan environment-aware toolbar filter
    - Buat kelas filter custom `App\Filters\EnvironmentAwareToolbar` yang extends/wrap `DebugToolbar`, memeriksa `ENVIRONMENT === 'development'` SEBELUM memanggil parent `after()`
    - Pertahankan `'toolbar'` di `$required['after']` (JANGAN pindahkan kategori — agar filter `pagecache`/`performance` lain di kategori sama tidak terganggu, sesuai Preservation 3.12) namun gunakan kelas filter custom ini sebagai pengganti `DebugToolbar::class` langsung
    - Kelas custom SHALL mengembalikan response tanpa modifikasi apa pun (pass-through) ketika `ENVIRONMENT !== 'development'`, sehingga endpoint `debugbar_time` tidak pernah mendapat data (setara 404/kosong)
    - TIDAK mengubah mekanisme penyimpanan `writable/debugbar/*.json` — hanya syarat aktivasi
    - Tambahkan catatan dokumentasi (bukan kode) merekomendasikan `CI_ENVIRONMENT=production` wajib di server publik
    - _Bug_Condition: input.environment != 'development' AND toolbarFilterExecutes(input)_
    - _Expected_Behavior: toolbar aktif JIKA DAN HANYA JIKA ENVIRONMENT === 'development'; untuk nilai lain, endpoint tidak mengembalikan data debug apa pun_
    - _Preservation: developer lokal (ENVIRONMENT=development) tetap dapat toolbar penuh; filter pagecache/performance tidak berubah_
    - _Requirements: 2.17, 2.18, 2.19, 2.20_

  - [~] 27.2 Verifikasi test eksplorasi bug condition K4 sekarang lulus
    - **Property 1: Expected Behavior** - Toolbar Hanya Aktif di Development
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 24 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (ENVIRONMENT=testing tidak lagi mengembalikan data debug apa pun)
    - _Requirements: 2.17, 2.18_

  - [~] 27.3 Verifikasi seluruh test preservasi Klaster 4 masih lulus
    - **Property 2: Preservation** - Development Tetap Memakai Fallback dan Toolbar Penuh
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 25 — JANGAN tulis test baru
    - Jalankan test properti preservasi dari langkah 25
    - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi tidak ada regresi pada development/testing lokal)
    - Konfirmasi seluruh test masih lulus setelah fix (tidak ada regresi)
    - _Requirements: 3.8, 3.9, 3.10, 3.11, 3.12_

- [~] 28. Checkpoint Klaster 4 - Pastikan seluruh test K3/K4 lulus
  - Pastikan seluruh test lulus (eksplorasi K3 + eksplorasi K4 + preservasi), tanyakan ke user jika ada pertanyaan
  - **PENTING (operasional, di luar scope kode)**: Setelah checkpoint ini, ingatkan user bahwa rotasi `EULT_ENCRYPTION_LEGACY_KEY` di `.env` production adalah tindakan operasional terpisah yang menjadi tanggung jawab operator — TIDAK ada periode migrasi bertahap; seluruh link tiket lama (`cektiket/index/{kunci}`, `validitas/{kunci}`, `cektiket/cetakterima/{kunci}`) yang beredar akan langsung invalid begitu rotasi dilakukan (Requirements 2.16)

---

## R1: Audit Ulang esc() (Independen, Dapat Dikerjakan Paralel)

**Catatan**: Klaster ini TIDAK bergantung pada Klaster 1-4 dan dapat dikerjakan kapan saja secara paralel, karena scope-nya (audit binding view di `detail_user.php`) tidak menyentuh file/fungsi yang dimodifikasi klaster manapun di atas.

- [~] 29. Tulis test eksplorasi bug condition R1 (audit binding tanpa esc())
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

- [~] 30. Tulis test properti preservasi R1 (SEBELUM implementasi fix, jika ada)
  - **Property 2: Preservation** - Tampilan Teks Wajar dan Link Lampiran Tidak Rusak
  - **IMPORTANT**: Ikuti metodologi observation-first
  - Observasi pada kode SAAT INI: konten balasan chat berisi karakter HTML wajar (`<`, `>`, `&`) dalam teks normal (bukan payload XSS) → catat tampil benar secara visual (entity-encoded oleh `esc()` yang sudah terpasang, sudah terverifikasi berfungsi)
  - Observasi: lampiran file (`repliesFile`) ditautkan dalam balasan chat (`<a href="...">`) dirender terpisah dari `repliesMessage` pada baris yang sama → catat link tidak rusak
  - Tulis property-based test yang menangkap pola di atas sebagai baseline
  - Jalankan test pada kode SAAT INI
  - **EXPECTED OUTCOME**: Test LULUS (mengonfirmasi baseline yang sudah berfungsi benar)
  - _Requirements: 3.29, 3.30_

- [ ] 31. Fix R1 (kondisional): Tambahkan esc() pada binding yang ditemukan belum aman saat audit
  - [~] 31.1 Implementasikan esc() pada binding yang ditemukan TIDAK aman (jika ada dari hasil audit task 29)
    - **CATATAN**: Task ini bersifat kondisional — HANYA berlaku pada binding yang benar-benar ditemukan tanpa `esc()` pada audit task 29 (3 binding utama yang disebut requirement SUDAH aman dan TIDAK memerlukan perubahan)
    - Untuk binding yang ditemukan tanpa `esc()`, bungkus dengan `esc()` sesuai konteks (`'html'` default untuk teks, `'attr'` untuk atribut HTML, `'js'` jika dirender ke dalam blok `<script>`) pada `app/Views/pages/ticketing/detail_user.php`
    - Dokumentasikan hasil audit (binding mana yang sudah aman vs yang diperbaiki) sebagai bagian dari catatan implementasi, karena requirement mengutip baris yang sudah tidak relevan dengan kode saat ini
    - _Bug_Condition: input.originatesFromPublicUserInput == true AND input.renderedAsRawHtml == true_
    - _Expected_Behavior: binding manapun yang ditemukan tanpa esc() SHALL dibungkus esc() sesuai konteks sehingga karakter HTML pada payload dirender sebagai teks, bukan dieksekusi_
    - _Preservation: karakter HTML wajar dalam teks normal tetap tampil benar visual setelah esc(); link lampiran chat tidak rusak_
    - _Requirements: 2.44, 2.45, 2.46_

  - [~] 31.2 Verifikasi test eksplorasi bug condition R1 sekarang lulus (untuk binding yang diperbaiki, jika ada)
    - **Property 1: Expected Behavior** - Audit Binding View
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 29 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS untuk SEMUA binding (baik yang sudah aman sejak awal maupun yang baru diperbaiki)
    - _Requirements: 2.44, 2.45, 2.46_

  - [~] 31.3 Verifikasi test preservasi R1 masih lulus
    - **Property 2: Preservation** - Tampilan Teks Wajar dan Link Lampiran Tidak Rusak
    - **IMPORTANT**: Jalankan ulang test YANG SAMA dari task 30 — JANGAN tulis test baru
    - **EXPECTED OUTCOME**: Test LULUS (karakter HTML wajar tetap tampil benar; link lampiran tidak rusak)
    - _Requirements: 3.29, 3.30_

- [~] 32. Checkpoint R1 - Pastikan seluruh test audit lulus
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
