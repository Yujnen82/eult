# Bugfix Requirements Document

## Introduction

Dokumen ini mencakup seluruh temuan dari audit security komprehensif portal publik EULT v2 (CodeIgniter 4) yang telah **diverifikasi nyata** melalui reproduksi read-only di server dev live (curl terhadap endpoint publik, probe `spark`, dan pembacaan kode sumber langsung) — bukan asumsi. Setiap temuan didokumentasikan sebagai satu Requirement independen menggunakan metodologi bug condition C(X), diurutkan berdasarkan severity: Kritis (K1–K4), Tinggi (T1–T4), Menengah (M1–M3), lalu Rendah (R1) sebagai requirement opsional paling akhir.

Prinsip preservasi yang berlaku lintas SEMUA requirement di bawah (tidak diulang per-requirement kecuali ada nuansa khusus):
- Alur lacak tiket publik (`Cektiket::index()`, `Login::cektiket()`) harus tetap berfungsi untuk pemegang kunci/nomor tiket yang sah.
- Alur buat tiket publik (`Login::savetiket()`) harus tetap berfungsi end-to-end termasuk penyimpanan record, pembuatan PDF arsip, dan pengiriman email notifikasi via `PengirimEmail::buat()`.
- Alur login staf/admin (`AuthFilter`, `GuestFilter`, route terproteksi di `app/Config/Filters.php`) harus tetap berfungsi tanpa perubahan mekanisme auth.
- Pengiriman email notifikasi (`PengirimEmail::buat()`, `PengirimEmail::selesai()`) harus tetap terpicu pada kondisi yang sama seperti sebelumnya.
- Upload lampiran chat (`Cektiket::saveReplies()` via `eult_upload_custom()`, validasi ekstensi `pdf|jpg|png` dan ukuran `15 * 1024`) harus tetap berfungsi tanpa perubahan validasi ekstensi/ukuran yang sudah benar.
- Scope perbaikan murni bugfix/hardening atas temuan yang sudah teridentifikasi — TIDAK melakukan redesain arsitektur enkripsi tiket, dan TIDAK mengganti mekanisme auth admin dengan sesuatu yang baru.

## Bug Analysis

### Current Behavior (Defect)

**K1 — SQL Injection via kondisi string mentah (Kritis)**

1.1 WHEN request POST publik ke `Cektiket::rating()` (`app/Controllers/Cektiket.php:112`) mengirim parameter `nomorTiket` berisi payload seperti `X' OR '1'='1`, THEN sistem merakit kondisi WHERE sebagai string SQL mentah `"ticketTrackingId = '" . $nomorTiket . "'"` melalui `$this->tiket->byId(...)`, dan query mengembalikan seluruh baris tabel `d_ticketing` (terverifikasi 38441 baris) alih-alih baris tunggal yang cocok.
1.2 WHEN request POST publik ke `Cektiket::rating()` (`app/Controllers/Cektiket.php:119`) mengirim `nomorTiket` berisi payload SQL, THEN sistem merakit kondisi WHERE sebagai string mentah `"archiveTrackingId = '" . $nomorTiket . "' AND ..."` melalui `$this->tiket->ambilSatu('d_archive', ...)`, membuka jalur injeksi yang sama.
1.3 WHEN request POST publik ke `Login::savetiket()` memicu `eult_auto_increment('d_archive', 'archiveId', $arsipId, "archiveTrackingId='" . $idTiket . "'")` di `app/Controllers/Login.php:128`, THEN kondisi string dirakit dari `$idTiket` yang sumber pembentukannya bergantung pada input request tanpa binding parameter, membuka jalur injeksi pada method model yang menerima kondisi tersebut (`app/Helpers/eult_kode_helper.php:29`, `app/Models/ModelMaster.php`).
1.4 WHEN request POST publik ke `Login::savetiket()` memicu pembacaan tiket via `$this->tiket->byId("ticketTrackingId = '" . $idTiket . "'")` di `app/Controllers/Login.php:168`, THEN kondisi dirakit sebagai string mentah alih-alih array binding, meski `$idTiket` di titik ini dihasilkan server-side (risiko lebih rendah dari 1.1–1.3, namun pola tidak konsisten dan harus diseragamkan).

**K2 — IDOR pada endpoint download berkas publik (Kritis)**

1.5 WHEN request GET publik ke `Cektiket::loadpdf($namaFile)` (`app/Controllers/Cektiket.php:145-152`) menyertakan nama file mentah pada URL segment (contoh: `TIKET_QEHOHTTV001_20260913143103.pdf`), THEN sistem langsung melakukan `file_exists()` dan menyajikan (serve) berkas tersebut tanpa validasi kepemilikan atau kecocokan dengan kunci terenkripsi apa pun.
1.6 WHEN request GET publik ke `Cektiket::loadattach($namaFile)` (`app/Controllers/Cektiket.php:155-163`) menyertakan nama file lampiran chat mentah, THEN sistem menyajikan berkas tersebut tanpa validasi kepemilikan.
1.7 WHEN request GET publik ke `Validitas::loadpdf($namaFile)` (`app/Controllers/Validitas.php:39-46`) menyertakan nama file mentah, THEN sistem menyajikan berkas PDF tersebut tanpa validasi kepemilikan/kecocokan.
1.8 WHEN penyerang mengetahui atau menebak pola nama file (yang mengandung nomor tiket sebagai bagian nama, contoh `TIKET_{arsipId}_{timestamp}.pdf`), THEN penyerang dapat mengunduh PDF/lampiran tiket milik pengguna lain — termasuk data pribadi (nama, email) — tanpa login dan tanpa memegang kunci terenkripsi tiket tersebut (terverifikasi via `curl` langsung ke server live).

**K3 — Kunci enkripsi tiket fallback hardcode ter-commit di git (Kritis)**

1.9 WHEN `Enkripsi::kunciLegacy()` (`app/Libraries/Enkripsi.php:24-31`) dipanggil dan variabel environment `EULT_ENCRYPTION_LEGACY_KEY` tidak terset atau kosong, THEN sistem mengembalikan nilai fallback hardcode `'SuPer_Enc-Key2010'` yang ter-commit di riwayat git dan dapat dibaca siapa pun dengan akses ke source code/repo.
1.10 WHEN nilai `EULT_ENCRYPTION_LEGACY_KEY` di `.env` production diperiksa, THEN nilai tersebut TERBUKTI IDENTIK dengan fallback hardcode `'SuPer_Enc-Key2010'` (terverifikasi langsung), sehingga kunci enkripsi/dekripsi yang melindungi akses `cektiket/index/{kunci}`, `validitas/{kunci}`, dan `cektiket/cetakterima/{kunci}` secara efektif adalah nilai publik yang siapa pun dapat temukan di source code, bukan rahasia.
1.11 WHEN sistem berjalan di environment production dengan kunci efektif sama dengan fallback hardcode, THEN TIDAK ADA mekanisme apa pun (log, warning, exception) yang memberi tahu operator bahwa kondisi ini terjadi.

**K4 — Debug toolbar publik membocorkan session & PII lintas pengguna (Kritis)**

1.12 WHEN filter `toolbar` (alias `DebugToolbar::class`) terdaftar sebagai `required.after` di `app/Config/Filters.php:64` tanpa pengecekan environment, THEN toolbar debug dapat aktif dan menyajikan data debug pada request apa pun tanpa mempertimbangkan apakah `CI_ENVIRONMENT` adalah `production` atau `development`.
1.13 WHEN server live memiliki `CI_ENVIRONMENT=development` di `.env` sementara server tersebut publicly accessible, THEN request `curl https://.../index.php?debugbar_time={timestamp}` mengembalikan HTTP 200 berisi data debug lengkap — terverifikasi 421 kemunculan `ticketEmail` milik pengguna lain, data session admin (`logged_in`, `susrSgroupNama`, `susrProfil`), dan riwayat SQL query lengkap — seluruhnya tanpa autentikasi apa pun.
1.14 WHEN file debug tersimpan di `writable/debugbar/*.json`, THEN nama file tersebut predictable (`debugbar_{unix_timestamp}.json`), sehingga dapat ditemukan/diakses tanpa mekanisme otorisasi tambahan.

**T1 — Filter keamanan global (CSRF, secureheaders, honeypot, invalidchars) dinonaktifkan (Tinggi)**

1.15 WHEN entri `'csrf'`, `'honeypot'`, `'invalidchars'` di `$globals['before']` dan `'honeypot'`, `'secureheaders'` di `$globals['after']` (`app/Config/Filters.php:76-83`) dikomentari, THEN seluruh request — termasuk POST publik — TIDAK melewati filter-filter tersebut sama sekali.
1.16 WHEN form publik di `app/Views/layouts/login.php` dan view terkait dikirim, THEN TIDAK ADA satupun form yang menyertakan `csrf_field()`/`csrf_token()` (terverifikasi tidak ada kemunculan token CSRF di view tersebut).
1.17 WHEN endpoint POST publik (`login/savetiket`, `login/cektiket`, `cektiket/save_replies`, `cektiket/rating`, endpoint otentikasi admin) menerima request tanpa token CSRF, THEN request tersebut diproses sepenuhnya, membuka celah Cross-Site Request Forgery pada aksi-aksi tersebut.

**T2 — Captcha kosmetik (teks polos) tanpa rate limiting (Tinggi)**

1.18 WHEN halaman login/buat tiket dirender, THEN captcha ditampilkan sebagai teks polos dalam elemen `<span class="captcha-display">` (`app/Views/layouts/login.php:1626` dan duplikatnya baris 1755), yang nilainya dapat langsung dibaca dari HTML/DOM tanpa perlu OCR atau upaya apa pun (`app/Helpers/eult_captcha_helper.php:14-27` men-generate teks polos, bukan gambar).
1.19 WHEN request POST dikirim ke endpoint otentikasi admin, `Login::savetiket()`, atau `Login::cektiket()` berulang kali dalam interval singkat, THEN TIDAK ADA implementasi `Throttler` apa pun di codebase yang membatasi jumlah percobaan per-IP atau per-sesi.
1.20 WHEN kombinasi 1.18 dan 1.19 dieksploitasi, THEN penyerang dapat melakukan brute force kredensial admin tanpa batas, dan/atau membuat tiket secara massal — setiap tiket sukses memicu pengiriman email (`PengirimEmail::buat()`), sehingga ini menjadi vektor mail bombing/cost amplification yang sepenuhnya terbuka.

**T3 — Cookie `captcha_code` dapat membypass validasi captcha (Tinggi)**

1.21 WHEN `eult_captcha_check()` (`app/Helpers/eult_captcha_helper.php:36-40`) dipanggil, THEN sistem memeriksa `get_cookie('captcha_code')` TERLEBIH DAHULU sebelum memeriksa `session()->get('captcha')` — cookie memiliki prioritas lebih tinggi daripada session dalam urutan pengecekan saat ini.
1.22 WHEN penyerang mengatur cookie `captcha_code=ABCD` pada browser/klien sendiri (cookie sepenuhnya dikontrol klien, tidak signed/tidak diverifikasi server), lalu mengirim parameter `captcha=ABCD` pada request, THEN validasi captcha SHALL lolos (`strtoupper('ABCD') === strtoupper('ABCD')`) tanpa penyerang perlu mengetahui nilai captcha sesi server yang sebenarnya.

**T4 — `Cektiket::rating()` tanpa validasi kepemilikan tiket (Tinggi)**

1.23 WHEN request POST dikirim ke `Cektiket::rating()` (`app/Controllers/Cektiket.php:106-125`), THEN sistem menerima `nomorTiket` langsung dari POST body tanpa validasi kecocokan dengan kunci terenkripsi yang seharusnya dipegang pemohon (tidak ada parameter kunci yang di-decode sama sekali pada method ini).
1.24 WHEN 1.23 dikombinasikan dengan K1 (SQL Injection) dan T1 (tidak ada CSRF), THEN penyerang dapat memberi rating sembarang dan memicu pengiriman email arbitrer (`PengirimEmail::selesai()`) ke `ticketEmail` tiket manapun yang nomornya diketahui/ditebak, tanpa otorisasi apa pun.

**M1 — Header security browser tidak lengkap (Menengah, opsional)**

1.25 WHEN response HTTP dikirim ke klien manapun, THEN TIDAK ADA header `Content-Security-Policy` yang disertakan karena `$CSPEnabled = false` di `app/Config/App.php:192`.
1.26 WHEN filter `secureheaders` tidak aktif (bagian dari T1), THEN header `X-Frame-Options`, `X-Content-Type-Options`, dan `Referrer-Policy` juga tidak disertakan pada response.

**M2 — IDOR serupa di admin `loadpdf`/`loadattach` (Menengah, opsional)**

1.27 WHEN staf yang sudah lolos filter `auth` (sesi login valid, grup apa pun) mengakses `Ticketing::loadpdf()`/`Ticketing::loadattach()` (`app/Controllers/Ticketing.php:1083-1104`), THEN sistem menyajikan berkas berdasarkan nama file mentah dari URL tanpa memeriksa apakah staf tersebut memiliki hak disposisi/unit atas tiket yang berkas tersebut terasosiasi.
1.28 WHEN staf dari unit A mengetahui/menebak nama file berkas milik tiket unit B, THEN staf tersebut dapat mengakses berkas tiket unit B meski tidak pernah mendapat disposisi ke tiket tersebut.

**M3 — Enumerasi nomor tiket tanpa rate limit (Menengah, opsional)**

1.29 WHEN request POST dikirim ke `Login::cektiket()` (`app/Controllers/Login.php:56-81`) dengan nomor tiket yang tidak ada, THEN sistem mengembalikan pesan berbeda ("Nomor tiket tidak ditemukan") dibanding saat nomor tiket ditemukan ("Nomor tiket ditemukan..."), memungkinkan penyerang membedakan nomor tiket valid vs tidak valid.
1.30 WHEN 1.29 tidak dibatasi rate limit apa pun, THEN penyerang dapat melakukan enumerasi nomor tiket valid secara masif (bergantung pada mekanisme rate limiting yang sama seperti T2).

**R1 — Stored XSS potensial pada halaman detail tiket (Rendah, opsional)**

1.31 WHEN halaman `app/Views/pages/ticketing/detail_user.php` merender `$value['repliesMessage']` (baris 246, area chat) tanpa fungsi `esc()`, THEN konten balasan chat (yang sebagian berasal dari input `repliesMessage` pada `Cektiket::saveReplies()`, berasal dari POST publik) dirender langsung sebagai HTML tanpa escaping.
1.32 WHEN halaman yang sama merender `$value['detailHistory']` (baris 203) tanpa `esc()`, THEN konten riwayat (yang sebagian dibentuk dari `eult_save_history()` menggunakan `$nama` dari input publik `ticketName`) dirender langsung sebagai HTML.
1.33 WHEN halaman yang sama merender `$datas['ticketTrackingId']` (baris 141 dan duplikatnya baris 152, 257) dan elemen lain tanpa `esc()`, THEN nilai-nilai tersebut dirender tanpa escaping meski risikonya lebih rendah karena format terkontrol server.

### Expected Behavior (Correct)

**K1 — SQL Injection via kondisi string mentah (Kritis)**

2.1 WHEN `Cektiket::rating()` menerima `nomorTiket` dari POST body, THEN sistem SHALL menggunakan kondisi array/binding (`$this->tiket->byId(['ticketTrackingId' => $nomorTiket])`) sehingga payload seperti `X' OR '1'='1` diperlakukan sebagai nilai literal dan hanya mengembalikan baris yang benar-benar cocok (0 baris untuk payload yang tidak valid, bukan seluruh tabel).
2.2 WHEN `Cektiket::rating()` melakukan query ke `d_archive` menggunakan `nomorTiket` dari POST body, THEN sistem SHALL menggunakan kondisi array (`['archiveTrackingId' => $nomorTiket, 'archiveJenis' => 'OUTPUT']` atau setara dengan `whereIn`/`groupStart` untuk klausa OR) alih-alih string terkonkatenasi.
2.3 WHEN `Login::savetiket()` maupun controller lain memanggil `eult_auto_increment()` dengan kondisi yang dibentuk dari input yang berasal dari request, THEN fungsi tersebut SHALL menerima dan menerapkan kondisi sebagai array/binding (bukan string terkonkatenasi) pada implementasinya di `app/Helpers/eult_kode_helper.php` dan `app/Models/ModelMaster.php`, atau nilai yang dikonkatenasi SHALL dijamin berasal dari sumber yang sudah divalidasi/sanitasi ketat (misal hasil `eult_generate_kode()` yang formatnya sudah terkontrol) — pilihan pendekatan didokumentasikan pada saat implementasi, namun hasil akhirnya WAJIB tidak dapat diinjeksi oleh input request publik.
2.4 WHEN `Login::savetiket()` membaca kembali tiket yang baru dibuat melalui `byId()`, THEN sistem SHALL menggunakan kondisi array (`['ticketTrackingId' => $idTiket]`) untuk konsistensi pola aman di seluruh codebase.
2.5 WHEN audit kode dilakukan pasca-fix pada seluruh pemanggilan `where()`/`ambilSatu()`/`byId()` di `app/Controllers/Cektiket.php`, `app/Controllers/Login.php`, `app/Models/ModelTicketing.php`, dan `app/Models/ModelMaster.php` yang menerima parameter turunan dari request publik, THEN TIDAK SATUPUN pemanggilan tersebut SHALL merakit kondisi WHERE dengan konkatenasi string langsung dari input request tanpa escaping/binding.

**K2 — IDOR pada endpoint download berkas publik (Kritis)**

2.6 WHEN request GET dikirim ke endpoint download PDF tiket publik, THEN sistem SHALL menerima kunci terenkripsi (bukan nama file mentah) mengikuti pola yang sudah benar pada `Cektiket::index()`/`Cektiket::cetakterima()` — signature endpoint SHALL berubah dari `loadpdf(string $namaFile)` menjadi menerima kunci (contoh: `loadpdf(string $kunci)`), lalu SHALL men-decode kunci via `Enkripsi::decode()` untuk memperoleh `nomorTiket`/`archiveTrackingId`, SHALL melakukan query untuk memastikan file yang direquest benar-benar terasosiasi dengan tiket hasil decode tersebut, dan HANYA menyajikan file jika kecocokan tersebut valid.
2.7 WHEN kunci yang diberikan ke endpoint download tidak valid, tidak dapat di-decode, atau hasil decode tidak memiliki berkas yang cocok, THEN sistem SHALL mengembalikan respons 403 atau 404 dan SHALL TIDAK menyajikan konten berkas apa pun.
2.8 WHEN request GET dikirim ke `Cektiket::loadattach()`, THEN sistem SHALL menerapkan pola validasi kepemilikan yang sama (kunci terenkripsi → decode → query kecocokan `repliesTicketId`/`repliesFile` → serve jika cocok) sebelum menyajikan lampiran chat.
2.9 WHEN request GET dikirim ke `Validitas::loadpdf()`, THEN sistem SHALL menerapkan pola validasi kepemilikan yang sama (kunci terenkripsi → decode → query kecocokan `archiveTrackingId`/`archiveFile` → serve jika cocok) sebelum menyajikan berkas.
2.10 WHEN endpoint download menerima path yang mengandung karakter traversal (`../`, absolute path), THEN sistem SHALL CONTINUE TO menerapkan `basename()` (perilaku existing) sebagai lapisan pertahanan tambahan di atas validasi kepemilikan pada 2.6–2.9, bukan sebagai satu-satunya kontrol.
2.11 (Catatan sekunder non-kritis, opsional, severity Menengah — lihat juga M2) WHEN request GET dikirim ke `Ticketing::loadattach()`/`Ticketing::loadpdf()` versi admin (`app/Controllers/Ticketing.php:1083-1104`, di balik filter `auth`), oleh staf yang lolos autentikasi namun bukan pemilik disposisi/unit terkait tiket tersebut, THEN sistem SEBAIKNYA menambahkan validasi ownership terhadap unit/disposisi staf sebelum menyajikan file — requirement ini didetailkan lebih lanjut pada M2.

**K3 — Kunci enkripsi tiket fallback hardcode ter-commit di git (Kritis)**

2.12 WHEN aplikasi melakukan bootstrap/boot pada environment `production` (`ENVIRONMENT === 'production'`) SELAMA masa transisi sebelum fallback dihapus penuh dari jalur decode production, THEN sistem SHALL memeriksa apakah kunci efektif yang dihasilkan `Enkripsi::kunciLegacy()` sama dengan string fallback hardcode `'SuPer_Enc-Key2010'`, dan JIKA sama, THEN sistem SHALL mencatat peringatan level kritis via `log_message('critical', ...)` yang menyatakan kunci enkripsi production masih menggunakan nilai default/hardcode dan HARUS dirotasi SEBELUM fallback dihapus dari kode.
2.13 WHEN kondisi 2.12 terdeteksi, THEN mekanisme deteksi SHALL TIDAK menghentikan (block/exit) aplikasi secara paksa — cukup mencatat peringatan yang terlihat oleh operator (log file dan/atau area admin bila memungkinkan) sebagai sinyal transisi sebelum penghapusan fallback dilakukan, agar operator memiliki jendela waktu untuk merotasi kunci dan menyiapkan komunikasi ke pengguna sebelum fallback benar-benar dihapus dari jalur decode production.
2.14 WHEN kode fallback hardcode di `Enkripsi::kunciLegacy()` diperbarui sebagai bagian dari fix ini, THEN fallback hardcode `'SuPer_Enc-Key2010'` SHALL dihapus sepenuhnya dari jalur decode/encode yang berlaku pada environment `production` — kunci production yang valid (nilai `EULT_ENCRYPTION_LEGACY_KEY` di `.env`, WAJIB nilai baru non-hardcode yang diisi operator) SHALL menjadi satu-satunya kunci yang diterima untuk decode di production; TIDAK ADA lagi jalur kompatibilitas mundur terhadap kunci hardcode di production setelah fix ini di-deploy DAN operator merotasi `EULT_ENCRYPTION_LEGACY_KEY`.
2.15 WHEN fix ini di-deploy ke production DAN operator merotasi `EULT_ENCRYPTION_LEGACY_KEY` ke nilai baru non-hardcode, THEN SEMUA link tiket lama yang beredar (`cektiket/index/{kunci}`, `validitas/{kunci}`, `cektiket/cetakterima/{kunci}`) yang dibuat menggunakan kunci hardcode SHALL langsung invalid/gagal decode — ini SHALL didokumentasikan secara eksplisit sebagai trade-off yang disengaja dan disetujui (bukan regresi), dan SHALL TIDAK ada mekanisme fallback tersembunyi apa pun di kode production yang menerima kunci hardcode setelah rotasi dilakukan.
2.16 WHEN dokumentasi operasional dibuat sebagai bagian dari fix ini, THEN dokumentasi SHALL menjelaskan langkah rotasi kunci (mengisi `EULT_ENCRYPTION_LEGACY_KEY` di `.env` production dengan nilai acak kuat ≥32 byte, berbeda dari fallback hardcode) sebagai tindakan operasional yang menjadi tanggung jawab user/operator, DAN SHALL secara eksplisit memperingatkan operator SEBELUM rotasi dilakukan bahwa: (a) tidak ada periode migrasi bertahap — begitu kunci dirotasi dan fix sudah di-deploy, seluruh link lama langsung invalid tanpa masa tenggang; (b) operator SEBAIKNYA mengumumkan periode transisi kepada pengguna sebelum rotasi, dan/atau memastikan bahwa tiket lama yang masih dalam proses aktif dapat dicek ulang oleh pemohon melalui nomor tiket (bukan link lama) setelah rotasi — dokumentasi ini SHALL TIDAK menyertakan nilai kunci baru yang sebenarnya (itu bukan bagian dari code fix).

**K4 — Debug toolbar publik membocorkan session & PII lintas pengguna (Kritis)**

2.17 WHEN filter `toolbar` dievaluasi pada request apa pun, THEN sistem SHALL memastikan toolbar HANYA aktif ketika `ENVIRONMENT === 'development'` — perbaikan SHALL dilakukan pada titik filter (`app/Config/Filters.php` dan/atau override perilaku `DebugToolbar` filter) agar pengecekan environment dilakukan sebelum toolbar diaktifkan, bukan unconditional seperti kondisi saat ini.
2.18 WHEN `ENVIRONMENT !== 'development'` (misal `production` atau `testing`), THEN endpoint `index.php?debugbar_time=...` SHALL TIDAK mengembalikan data debug apa pun — respons SHALL setara dengan toolbar yang benar-benar nonaktif (404/kosong), bukan hanya UI toolbar yang disembunyikan di sisi klien.
2.19 WHEN fix ini diimplementasikan, THEN sistem SHALL menyertakan rekomendasi konfigurasi (didokumentasikan, bukan dipaksakan via kode) bahwa server mana pun yang menerima traffic publik WAJIB menggunakan `CI_ENVIRONMENT=production` di `.env` — perbaikan kode pada 2.17–2.18 berfungsi sebagai defense-in-depth di samping perbaikan konfigurasi tersebut, karena perbaikan konfigurasi environment adalah tindakan operasional yang tidak dapat dipaksakan sepenuhnya lewat commit kode.
2.20 WHEN toolbar aktif pada environment development, THEN sistem SHALL CONTINUE TO menyimpan file debug di `writable/debugbar/` seperti perilaku existing (perbaikan ini tidak mengubah mekanisme penyimpanan, hanya syarat aktivasinya).

**T1 — Filter keamanan global (CSRF, secureheaders, honeypot, invalidchars) dinonaktifkan (Tinggi)**

2.21 WHEN konfigurasi filter diperbarui, THEN `'csrf'` SHALL diaktifkan pada `$globals['before']` di `app/Config/Filters.php`.
2.22 WHEN form POST publik dirender (`app/Views/layouts/login.php` dan view form lain yang mengirim POST ke endpoint publik/admin), THEN setiap form tersebut SHALL menyertakan `csrf_field()` (atau `<?= csrf_token() ?>`/`<?= csrf_hash() ?>` sesuai konvensi CI4) di dalam elemen `<form>`.
2.23 WHEN request dikirim secara AJAX (JavaScript existing yang mengirim POST ke `login/savetiket`, `login/cektiket`, `cektiket/save_replies`, `cektiket/rating`), THEN JavaScript tersebut SHALL menyertakan token CSRF pada header `X-CSRF-TOKEN` (sesuai `Config\Security::$headerName` yang sudah bernilai `'X-CSRF-TOKEN'`) atau pada body request, dengan token diperbarui setiap kali form berhasil disubmit sesuai `$regenerate = true` yang sudah aktif di `Config\Security`.
2.24 WHEN validasi CSRF gagal pada request AJAX (yang mengharapkan respons JSON), THEN sistem SHALL mengembalikan respons yang dapat ditangani secara graceful oleh JavaScript existing (idealnya respons JSON dengan status error yang konsisten dengan pola respons endpoint lain, `{'status': 'danger', 'message': '...'}`) — solusi ini SHALL diupayakan tanpa menambahkan URI apa pun ke pengecualian CSRF (`CSRFExcludeURIs`) kecuali benar-benar tidak ada solusi lain yang memungkinkan, dan penambahan exclude URI SHALL dikonfirmasi ke user terlebih dahulu jika terpaksa diperlukan.
2.25 WHEN konfigurasi filter diperbarui, THEN `'secureheaders'` SHALL diaktifkan pada `$globals['after']` dan `'invalidchars'` SHALL diaktifkan pada `$globals['before']` di `app/Config/Filters.php`.
2.26 WHEN filter `'honeypot'` diaktifkan kembali, THEN form publik yang relevan (form buat tiket, form login) SHALL menyertakan field honeypot sesuai konvensi `CodeIgniter\Filters\Honeypot` (biasanya otomatis disisipkan via helper, namun perlu diverifikasi kompatibilitasnya dengan struktur form existing).

**T2 — Captcha kosmetik (teks polos) tanpa rate limiting (Tinggi)**

2.27 WHEN captcha di-generate untuk ditampilkan ke pengguna, THEN sistem SHALL merender captcha sebagai gambar terdistorsi menggunakan text-to-image rendering (GD atau Imagick, tergantung ekstensi PHP yang tersedia di server), BUKAN lagi teks polos di DOM.
2.28 WHEN mekanisme penyimpanan/pembandingan captcha diperbarui, THEN alur session-based existing SHALL dipertahankan persis (`eult_captcha_generate()` tetap menyimpan nilai captcha ke `session()->set('captcha', ...)`, `eult_captcha_check()` tetap membandingkan input dengan `session()->get('captcha')`) — HANYA cara render nilai captcha ke pengguna yang berubah dari teks-di-DOM menjadi gambar; nilai captcha itu sendiri (yang disimpan session) SHALL TIDAK pernah dikirim ke klien dalam bentuk plain text apa pun (termasuk di response AJAX `new_captcha`, yang harus berubah menjadi URL/endpoint gambar, bukan string captcha polos seperti sekarang).
2.29 WHEN route otentikasi admin (`otentifikasi`/login POST), `login/savetiket`, dan `login/cektiket` menerima request, THEN sistem SHALL menerapkan rate limiting per-IP menggunakan `Config\Throttle` bawaan CI4, diterapkan melalui filter pada route-route tersebut di `app/Config/Filters.php`.
2.30 WHEN limit rate tercapai untuk suatu IP pada route yang diberi rate limiting, THEN sistem SHALL mengembalikan respons yang menandakan terlalu banyak percobaan (HTTP 429 atau respons JSON dengan pesan yang sesuai untuk endpoint AJAX) alih-alih memproses request seperti biasa.

**T3 — Cookie `captcha_code` dapat membypass validasi captcha (Tinggi)**

2.31 WHEN kode `eult_captcha_check()` diperbarui sebagai bagian dari fix ini, THEN fungsi tersebut SHALL menghapus seluruh pemanggilan `get_cookie('captcha_code')` dan logika fallback terkait — mekanisme cookie ini adalah warisan CI3 yang tidak lagi relevan pada arsitektur session-based CI4 saat ini, dan penghapusannya SHALL TIDAK bergantung pada kondisi atau konfirmasi tambahan apa pun.
2.32 WHEN `eult_captcha_check()` memvalidasi input captcha setelah fix ini, THEN validasi SHALL murni berasal dari `session()->get('captcha')` — SHALL TIDAK ada lagi pemanggilan `get_cookie('captcha_code')` atau logika apa pun yang membaca nilai captcha dari cookie dalam bentuk apa pun.
2.33 WHEN nilai captcha di session tidak ada atau kosong (`! is_string($tersimpan) || $tersimpan === ''`), THEN sistem SHALL CONTINUE TO mengembalikan `false` (perilaku existing untuk kasus ini dipertahankan).

**T4 — `Cektiket::rating()` tanpa validasi kepemilikan tiket (Tinggi)**

2.34 WHEN `Cektiket::rating()` didefinisikan ulang, THEN signature/route method tersebut SHALL menerima kunci terenkripsi (bukan `nomorTiket` mentah) mengikuti pola yang sama seperti `Cektiket::index()`/`Cektiket::cetakterima()` — parameter `nomorTiket` dari POST body SHALL TIDAK lagi digunakan sebagai sumber identifikasi tiket.
2.35 WHEN kunci terenkripsi diterima oleh `Cektiket::rating()`, THEN sistem SHALL men-decode kunci tersebut via `Enkripsi::decode()` di dalam controller, dan hasil decode SHALL menjadi satu-satunya sumber `nomorTiket`/`ticketTrackingId` yang digunakan untuk SEMUA query berikutnya dalam method ini (`byId()`, `ambilSatu('d_rating', ...)`, `tambah()`/`ubah()` pada `d_rating`, `ambilSatu('d_archive', ...)`).
2.36 WHEN kunci yang diberikan tidak valid atau tidak dapat di-decode, THEN sistem SHALL menghentikan pemrosesan rating dan mengembalikan respons error yang sesuai (tidak memproses insert/update rating maupun mengirim email).
2.37 WHEN halaman `pages/ticketing/detail_user.php` yang memanggil aksi rating diperbarui (form/AJAX submit rating), THEN pemanggilan tersebut SHALL menyertakan kunci terenkripsi (`$kunci` yang sudah tersedia di context halaman tersebut, sama seperti yang dipakai `cetakterima`) sebagai pengganti pengiriman `nomorTiket` mentah dari sisi klien.

**M1 — Header security browser tidak lengkap (Menengah, opsional)**

2.38 WHEN filter `secureheaders` diaktifkan sesuai T1 (Acceptance Criteria 2.25), THEN header `X-Frame-Options`, `X-Content-Type-Options`, dan `Referrer-Policy` SHALL disertakan otomatis pada response sesuai perilaku default `CodeIgniter\Filters\SecureHeaders`.
2.39 WHEN `$CSPEnabled` diaktifkan (`true`) di `app/Config/App.php`, THEN sistem SHALL menyertakan header `Content-Security-Policy` pada response, dengan whitelist minimal yang dikonfigurasi di `app/Config/ContentSecurityPolicy.php` agar tidak memblokir aset (CSS/JS/gambar) yang sudah dipakai halaman existing (termasuk CDN eksternal jika ada yang dipakai tema/plugin JS di `login.php`/`detail_user.php`).

**M2 — IDOR serupa di admin `loadpdf`/`loadattach` (Menengah, opsional)**

2.40 WHEN `Ticketing::loadpdf()`/`Ticketing::loadattach()` menerima request dari staf terautentikasi, THEN sistem SHALL memvalidasi bahwa staf tersebut memiliki hak akses ke tiket yang berkas tersebut terasosiasi — validasi SHALL menggunakan mekanisme otorisasi unit/disposisi yang SUDAH ADA di codebase (misal pengecekan `disposisiUnit` terhadap `s_user_group_unit`/grup staf, konsisten dengan pola otorisasi yang dipakai controller admin lain), BUKAN mekanisme otorisasi baru.
2.41 WHEN staf tidak memiliki hak akses ke tiket terkait, THEN sistem SHALL mengembalikan 403 dan SHALL TIDAK menyajikan konten berkas.

**M3 — Enumerasi nomor tiket tanpa rate limit (Menengah, opsional)**

2.42 WHEN rate limiting diterapkan pada route `login/cektiket` sesuai T2 (Acceptance Criteria 2.29 pada T2), THEN percobaan enumerasi nomor tiket SHALL dibatasi secara signifikan oleh mekanisme rate limit per-IP yang sama.
2.43 WHEN sistem tetap membedakan pesan "ditemukan"/"tidak ditemukan" (perilaku ini SHALL TIDAK diubah kecuali user memutuskan lain, karena mengubah pesan menjadi generik dapat menurunkan UX lacak tiket yang merupakan fitur inti), THEN mitigasi utama untuk M3 SHALL berupa rate limiting (2.42) sebagai kontrol primer, bukan penyamaran pesan.

**R1 — Stored XSS potensial pada halaman detail tiket (Rendah, opsional)**

2.44 WHEN `detail_user.php` merender `$value['repliesMessage']`, THEN output SHALL dibungkus `esc()` (contoh: `esc($value['repliesMessage'])`) sebelum ditampilkan, dengan mode escaping yang sesuai konteks HTML.
2.45 WHEN `detail_user.php` merender `$value['detailHistory']`, THEN output SHALL dibungkus `esc()` sebelum ditampilkan.
2.46 WHEN `detail_user.php` merender field lain yang berasal (langsung atau tidak langsung) dari input pengguna tanpa `esc()` (`ticketTrackingId` pada baris 141, 152, 257, dan field terkait lain pada rentang baris 141-257 yang disebutkan dalam temuan), THEN output tersebut SHALL dibungkus `esc()` sebagai defense-in-depth, konsisten dengan pola `esc()` yang sudah dipakai benar di bagian lain codebase (contoh: `Login::getLayanan()` yang sudah memakai `esc()`).

### Unchanged Behavior (Regression Prevention)

**K1 — SQL Injection via kondisi string mentah (Kritis)**

3.1 WHEN `nomorTiket` yang dikirim adalah nomor tiket valid yang benar-benar ada di `d_ticketing`, THEN sistem SHALL CONTINUE TO mengembalikan data rating/tiket yang sesuai dan memproses `Cektiket::rating()` (insert/update `d_rating`, pengiriman email via `PengirimEmail::selesai()`) persis seperti sebelumnya.
3.2 WHEN `Login::savetiket()` dipanggil dengan data valid, THEN sistem SHALL CONTINUE TO menghasilkan `archiveId` berurutan yang benar via `eult_auto_increment()`, menyimpan record `d_ticketing` dan `d_archive`, serta mengirim email `PengirimEmail::buat()` tanpa perubahan perilaku untuk kondisi normal.
3.3 WHEN method model lain yang TIDAK menerima parameter dari request publik (misal `getDisposisiById()`, `getTicketAssign()` di `ModelTicketing.php` yang menggunakan `$this->db->escapeString()`) tetap menggunakan kondisi string dengan escaping manual yang sudah ada, THEN perubahan pada K1 SHALL TIDAK mengubah perilaku method-method tersebut di luar scope temuan ini.

**K2 — IDOR pada endpoint download berkas publik (Kritis)**

3.4 WHEN pemegang kunci terenkripsi yang sah mengakses link output PDF dari halaman `Cektiket::index()` (link `$output_url` di `pages/ticketing/detail_user.php`), THEN sistem SHALL CONTINUE TO menyajikan file PDF yang benar tanpa gangguan.
3.5 WHEN pemegang kunci terenkripsi yang sah mengklik link lampiran chat yang muncul di riwayat balasan (`$load_attach` di `detail_user.php`), THEN sistem SHALL CONTINUE TO menyajikan lampiran yang benar.
3.6 WHEN pengguna mengakses `Validitas::index($kunci)` dengan kunci valid hasil scan QR, THEN sistem SHALL CONTINUE TO menampilkan halaman validasi surat dan (setelah fix) link download PDF terkait SHALL CONTINUE TO berfungsi untuk kunci yang sama.
3.7 WHEN mime-type dan header `Content-Disposition`/`Content-Type` disajikan untuk file yang valid, THEN sistem SHALL CONTINUE TO mengirim header yang sama seperti perilaku existing (`application/pdf` untuk PDF, deteksi mime dinamis untuk lampiran chat).

**K3 — Kunci enkripsi tiket fallback hardcode ter-commit di git (Kritis)**

3.8 WHEN environment adalah `development` atau `testing`, THEN sistem SHALL CONTINUE TO menggunakan fallback hardcode tanpa peringatan blocking, agar workflow development tidak terganggu — penghapusan fallback pada 2.14 HANYA berlaku untuk jalur decode/encode di environment `production`.
3.9 WHEN kunci `EULT_ENCRYPTION_LEGACY_KEY` sudah diisi nilai kuat non-hardcode oleh operator, THEN sistem SHALL CONTINUE TO menggunakan nilai tersebut dan SHALL TIDAK menampilkan peringatan apa pun (peringatan hanya muncul saat kunci efektif == fallback hardcode DAN fallback belum dihapus dari kode).
3.10 WHEN operator merotasi `EULT_ENCRYPTION_LEGACY_KEY` ke nilai baru setelah fix ini di-deploy, THEN TIDAK ADA periode migrasi bertahap yang disediakan oleh sistem — link `cektiket/index/{kunci}`, `validitas/{kunci}`, `cektiket/cetakterima/{kunci}` yang di-generate SEBELUM rotasi SHALL langsung gagal decode begitu rotasi selesai, tanpa mekanisme decode multi-kandidat yang menerima kunci hardcode lama di production; mekanisme decode multi-kandidat existing di `Enkripsi::decode()` SHALL CONTINUE TO berfungsi HANYA untuk kandidat kunci yang valid di production (kunci baru dari `.env`), bukan untuk fallback hardcode yang sudah dihapus.

**K4 — Debug toolbar publik membocorkan session & PII lintas pengguna (Kritis)**

3.11 WHEN developer bekerja pada environment `development` di mesin lokal, THEN toolbar debug SHALL CONTINUE TO aktif dan menyajikan informasi debug seperti biasa, agar workflow development tidak terganggu.
3.12 WHEN filter `required.after` lain (`pagecache`, `performance`) dievaluasi, THEN perubahan pada K4 SHALL TIDAK mengubah perilaku filter-filter tersebut.

**T1 — Filter keamanan global (CSRF, secureheaders, honeypot, invalidchars) dinonaktifkan (Tinggi)**

3.13 WHEN request AJAX yang sah (menyertakan token CSRF yang valid) dikirim ke `login/savetiket`, `login/cektiket`, `cektiket/save_replies`, `cektiket/rating`, THEN sistem SHALL CONTINUE TO memproses request tersebut dan mengembalikan respons JSON yang sama seperti perilaku sebelum fix (status success/danger, redirect_url, new_captcha, dsb).
3.14 WHEN respons JSON dari endpoint AJAX existing diperiksa strukturnya (field `status`, `message`, `redirect_url`, `new_captcha`), THEN struktur tersebut SHALL CONTINUE TO sama persis — fix T1 SHALL TIDAK mengubah kontrak response API existing selain menambah validasi CSRF di lapisan sebelumnya.
3.15 WHEN halaman non-form (GET biasa) diakses, THEN aktivasi `secureheaders`/`invalidchars`/`honeypot` SHALL TIDAK menyebabkan perubahan tampilan atau perilaku yang terlihat pengguna, kecuali penambahan header HTTP response yang tidak mengubah rendering halaman.

**T2 — Captcha kosmetik (teks polos) tanpa rate limiting (Tinggi)**

3.16 WHEN pengguna sah memasukkan captcha dengan benar (dibaca dari gambar, bukan DOM teks) dalam jumlah percobaan wajar, THEN sistem SHALL CONTINUE TO menerima captcha tersebut dan memproses request seperti biasa.
3.17 WHEN pengguna sah menekan tombol refresh captcha (`refresh-captcha` di view), THEN sistem SHALL CONTINUE TO menghasilkan captcha baru dan memperbarui tampilan, hanya berbeda formatnya (gambar, bukan teks) dari sebelumnya.
3.18 WHEN pengguna sah membuat tiket atau login dalam frekuensi normal (bukan pola serangan), THEN rate limiting pada 2.29 SHALL TIDAK memblokir aktivitas normal tersebut — ambang batas rate limit harus ditetapkan cukup longgar untuk pengguna wajar namun cukup ketat untuk mencegah brute force/spam.

**T3 — Cookie `captcha_code` dapat membypass validasi captcha (Tinggi)**

3.19 WHEN pengguna sah memasukkan captcha yang benar sesuai nilai yang tersimpan di session miliknya, THEN validasi SHALL CONTINUE TO lolos (case-insensitive, sesuai perilaku existing `strtoupper()` pada kedua sisi perbandingan).
3.20 WHEN pengguna sah memasukkan captcha yang salah, THEN validasi SHALL CONTINUE TO gagal, tanpa terpengaruh oleh keberadaan cookie apa pun di browser pengguna tersebut.

**T4 — `Cektiket::rating()` tanpa validasi kepemilikan tiket (Tinggi)**

3.21 WHEN pemegang kunci terenkripsi yang sah memberikan rating pada tiketnya sendiri, THEN sistem SHALL CONTINUE TO menyimpan rating (insert jika belum ada, update jika sudah ada — logika `empty($cek) ? tambah() : ubah()` dipertahankan) dan mengirim email `PengirimEmail::selesai()` beserta lampiran output/TTD jika ada, persis seperti perilaku sebelumnya.
3.22 WHEN rating berhasil disimpan, THEN sistem SHALL CONTINUE TO menampilkan pesan sukses (`eult_message_kirim(...)`) seperti perilaku existing.

**M1 — Header security browser tidak lengkap (Menengah, opsional)**

3.23 WHEN halaman publik/admin dirender setelah CSP diaktifkan, THEN seluruh aset statis (CSS, JS, gambar, font) yang sudah dimuat sebelumnya SHALL CONTINUE TO dapat dimuat browser tanpa diblokir CSP — whitelist SHALL disesuaikan mencakup seluruh sumber daya yang benar-benar dipakai halaman existing.
3.24 WHEN fitur JavaScript existing (AJAX submit, refresh captcha, chat lampiran) dijalankan setelah CSP/secureheaders aktif, THEN fitur-fitur tersebut SHALL CONTINUE TO berfungsi tanpa error yang disebabkan pemblokiran CSP (misal `unsafe-inline` untuk inline script jika memang dipakai existing, kecuali direfaktor terpisah — SHALL TIDAK memaksa refactor inline script sebagai bagian scope M1 ini).

**M2 — IDOR serupa di admin `loadpdf`/`loadattach` (Menengah, opsional)**

3.25 WHEN staf admin/superadmin atau staf dengan hak disposisi yang sah mengakses berkas tiket yang memang menjadi tanggung jawabnya, THEN sistem SHALL CONTINUE TO menyajikan berkas tersebut tanpa gangguan.
3.26 WHEN filter `auth` yang sudah melindungi route `ticketing/*` (`app/Config/Filters.php`) dievaluasi, THEN perubahan M2 SHALL TIDAK mengubah mekanisme filter `auth` itu sendiri — hanya menambah lapisan validasi ownership di dalam controller.

**M3 — Enumerasi nomor tiket tanpa rate limit (Menengah, opsional)**

3.27 WHEN pengguna sah mengecek nomor tiketnya sendiri dalam frekuensi wajar, THEN sistem SHALL CONTINUE TO mengembalikan pesan "ditemukan" beserta `redirect_url` yang benar, tanpa terhalang rate limit yang wajar.
3.28 WHEN pengguna sah salah mengetik nomor tiket sesekali, THEN sistem SHALL CONTINUE TO mengembalikan pesan "tidak ditemukan" seperti biasa, tanpa langsung terblokir pada percobaan pertama/kedua.

**R1 — Stored XSS potensial pada halaman detail tiket (Rendah, opsional)**

3.29 WHEN konten balasan chat yang berisi karakter HTML biasa (bukan payload XSS, misal tanda `<`, `>`, `&` dalam teks wajar) ditampilkan, THEN karakter tersebut SHALL CONTINUE TO tampil sebagai teks yang benar secara visual (di-encode entity dengan benar oleh `esc()`, bukan hilang atau merusak tampilan).
3.30 WHEN lampiran file (`repliesFile`) ditautkan dalam balasan chat (`<a href="...">`), THEN penambahan `esc()` pada `repliesMessage` SHALL TIDAK merusak link lampiran yang dirender secara terpisah dari `repliesMessage` pada baris yang sama.
