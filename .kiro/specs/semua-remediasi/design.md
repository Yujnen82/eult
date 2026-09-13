# Semua Remediasi Bugfix Design

## Overview

Dokumen ini merancang perbaikan teknis untuk 12 bug condition yang telah diverifikasi pada audit security portal publik EULT v2 (K1-K4 Kritis, T1-T4 Tinggi, M1-M3 Menengah, R1 Rendah). Setiap bug condition ditangani sebagai unit perbaikan independen dengan root cause, perubahan kode spesifik, dan strategi testing sendiri, namun seluruhnya mengikuti prinsip yang sama: **perbaikan bersifat murni bugfix/hardening pada pola yang sudah ada di codebase** — tidak ada redesain arsitektur enkripsi tiket (`Enkripsi` tetap AES-256-CBC + HKDF-SHA512 + HMAC, hanya sumber kunci fallback yang diubah), dan tidak ada penggantian mekanisme auth admin (`AuthFilter`/`GuestFilter`/`Otentifikasi::cekDatabase()` tetap sama, hanya filter tambahan seperti CSRF/rate-limit yang dipasang di depannya).

Dua keputusan final (tidak ambigu, sudah dikonfirmasi user, langsung diimplementasikan di fase final tanpa periode transisi kode):
- **K3**: Fallback hardcode `'SuPer_Enc-Key2010'` dihapus total dari jalur decode/encode ketika `ENVIRONMENT === 'production'`. Tidak ada fallback tersembunyi apa pun di production setelah fix ini.
- **T3**: `get_cookie('captcha_code')` dan seluruh logika fallback cookie dihapus total dari `eult_captcha_check()`. Validasi captcha murni dari session.

Pendekatan perbaikan dikelompokkan menjadi 4 klaster teknis yang saling terkait, dengan urutan implementasi yang disarankan (klaster lebih awal adalah prasyarat/independen; klaster lebih akhir menumpuk di atas klaster sebelumnya):

1. **Klaster Query Aman** (K1) — mengganti seluruh kondisi WHERE string-concatenation dengan array binding pada method yang dijangkau input publik.
2. **Klaster Kepemilikan Berkas & Rating** (K2, T4, M2) — memperkenalkan pola "kunci terenkripsi → decode → validasi kecocokan → serve" secara konsisten di seluruh endpoint download dan endpoint rating, plus lapisan ownership unit/disposisi untuk admin.
3. **Klaster Pertahanan Perimeter** (T1, T2, T3, M1, M3) — mengaktifkan kembali filter keamanan global (CSRF, secureheaders, honeypot, invalidchars), mengganti captcha kosmetik menjadi gambar, menerapkan rate limiting, menghapus bypass cookie captcha, dan mengaktifkan CSP.
4. **Klaster Konfigurasi & Observability** (K3, K4) — mengetatkan kunci enkripsi production dan mengunci toolbar debug hanya ke environment development, keduanya dengan sinyal log yang eksplisit.

R1 (Stored XSS) ditangani terpisah sebagai audit verifikasi karena investigasi kode aktual (lihat bagian Catatan Verifikasi R1 di bawah) menunjukkan ketiga binding yang disebut requirement (`repliesMessage`, `detailHistory`, `ticketTrackingId`) SUDAH terbungkus `esc()` pada baris kode aktual saat ini — bukan pada baris 141/152/203/246/257 seperti yang dikutip requirement (baris tersebut saat ini berisi CSS, bukan output dinamis). Fix untuk R1 akan berupa audit ulang menyeluruh + defense-in-depth pada binding lain yang belum diverifikasi, bukan mengasumsikan root cause yang sudah tidak ada.


## Glossary

- **Bug_Condition (C)**: Kondisi input/environment yang memicu masing-masing dari 12 bug. Setiap bug condition K1-R1 punya C(X) sendiri, didefinisikan per-bug pada bagian Bug Details.
- **Property (P)**: Perilaku benar yang diharapkan ketika C(X) bernilai true untuk bug tersebut.
- **Preservation**: Perilaku existing yang WAJIB tidak berubah untuk input di mana C(X) bernilai false — didefinisikan lintas-bug pada bagian Expected Behavior dan berlaku sebagai prinsip preservasi global (lihat Introduction `bugfix.md`): alur lacak tiket, buat tiket, login staf, notifikasi email, dan upload lampiran chat harus tetap berfungsi.
- **kunci**: String hasil `Enkripsi::encode()` yang dikirim via URL (`cektiket/index/{kunci}`, `validitas/{kunci}`, `cektiket/cetakterima/{kunci}`) sebagai representasi tidak-langsung dari `ticketTrackingId`/`archiveTrackingId`. Selalu didecode via `Enkripsi::decode()` sebelum dipakai sebagai identitas tiket.
- **nomorTiket / ticketTrackingId**: Nomor tiket format `XXXX-XXXX-NNN` yang menjadi primary lookup key ke `d_ticketing`.
- **archiveTrackingId**: Kolom di `d_archive` yang menyimpan `ticketTrackingId` terkait, dipakai untuk mencari file PDF arsip (`archiveFile`) per jenis (`archiveJenis`: `TIKET`, `OUTPUT`, `TTD`).
- **repliesTicketId / repliesFile**: Kolom di `d_replies` yang mengaitkan lampiran chat (`repliesFile`) dengan tiket asalnya.
- **kondisi array/binding**: Cara memanggil `where()`/`ambilSatu()`/`byId()` CodeIgniter Query Builder dengan argumen `array` (contoh `['kolom' => $nilai]`) — builder secara otomatis melakukan parameter binding/escaping. Kontras dengan **kondisi string mentah**: argumen `string` hasil concatenation langsung, yang diteruskan builder sebagai raw SQL fragment TANPA escaping/binding apa pun (ini adalah mekanisme SQLi yang sebenarnya pada K1 — bukan `query()` mentah, melainkan raw-string WHERE clause yang diterima Query Builder).
- **kunci efektif**: Nilai kunci yang benar-benar dipakai `Enkripsi::kunciMentah()` saat runtime — hasil dari `EULT_ENCRYPTION_LEGACY_KEY` di `.env` jika terisi, atau fallback hardcode jika tidak.
- **kandidat kunci decode**: Daftar kunci yang dicoba `Enkripsi::decode()` secara berurutan (kunci legacy/eksplisit, lalu `encryption.key` dari `.env` bila berformat `hex2bin:`) — mekanisme multi-kandidat ini SUDAH ADA dan dipertahankan, K3 hanya mengubah ISI kandidat pertama pada environment production.
- **ownership/disposisi**: Mekanisme otorisasi existing yang menentukan apakah seorang staf (grup `ADMIN`/`OPERATOR` = akses penuh; grup lain = terbatas ke unit sendiri via `s_user_group_unit.sgroupunitSgroupNama`) berhak atas tiket tertentu — dipakai ulang (bukan mekanisme baru) untuk M2.

## Bug Details

### K1 — SQL Injection via kondisi string mentah

**Bug Condition:**

Bug termanifestasi ketika input dari request publik (`nomorTiket` pada `Cektiket::rating()`, `$idTiket` pada `Login::savetiket()`) dipakai untuk membentuk kondisi WHERE sebagai string ter-concatenate, lalu diteruskan ke `where()`/`ambilSatu()`/`byId()` CodeIgniter Query Builder sebagai argumen `string` (bukan `array`). Builder CI4 memperlakukan argumen `string` sebagai raw SQL condition — TIDAK melakukan parameter binding untuk kasus ini, sehingga metacharacter SQL pada input (`'`, `OR`, `--`, dll) diinterpretasikan sebagai bagian dari struktur query, bukan nilai literal.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type QueryCall
  OUTPUT: boolean

  RETURN input.source IN ['Cektiket::rating', 'Login::savetiket', 'Login::index-readback']
         AND input.kondisiType == 'string'  // bukan array
         AND containsUserControlledValue(input.kondisi)  // berasal dari POST body tanpa validasi format ketat
         AND NOT isParameterBound(input.kondisi)
END FUNCTION
```

**Examples:**
- `Cektiket::rating()` baris `byId("ticketTrackingId = '" . $nomorTiket . "'")` — kirim `nomorTiket = "X' OR '1'='1"` → WHERE menjadi `ticketTrackingId = 'X' OR '1'='1'`, cocok dengan SEMUA baris di `d_ticketing` (bukan 0 baris seperti yang diharapkan untuk nomor tiket tidak valid).
- `Cektiket::rating()` baris `ambilSatu('d_archive', "archiveTrackingId = '" . $nomorTiket . "' AND archiveJenis = 'OUTPUT'")` — payload yang sama membuka jalur OR-injection serupa pada tabel `d_archive`.
- `Login::savetiket()` baris `eult_auto_increment('d_archive', 'archiveId', $arsipId, "archiveTrackingId='" . $idTiket . "'")` → diteruskan ke `ModelMaster::getByLastId()` yang memanggil `where($kondisi)` dengan kondisi string; `$idTiket` dibentuk dari `eult_generate_kode()` (server-generated, format terkontrol `XXXX-XXXX-NNN`) sehingga risiko eksploitasi lebih rendah — namun pola tidak konsisten dengan bagian lain codebase yang sudah aman.
- `Login::savetiket()` baris `byId("ticketTrackingId = '" . $idTiket . "'")` untuk readback pasca-insert — kondisi sama, `$idTiket` server-generated, risiko rendah namun pola harus diseragamkan (Acceptance Criteria 2.4).
- Edge case: `nomorTiket` berupa string kosong `''` — setelah fix, `byId(['ticketTrackingId' => ''])` SHALL mengembalikan 0 baris (tidak error, tidak crash), konsisten dengan perilaku Query Builder standar untuk pencarian exact-match yang tidak cocok.

### K2 — IDOR pada endpoint download berkas publik

**Bug Condition:**

Bug termanifestasi ketika endpoint download (`Cektiket::loadpdf()`, `Cektiket::loadattach()`, `Validitas::loadpdf()`) menerima **nama file mentah** langsung dari URL segment, lalu langsung melakukan `file_exists()`/`is_file()` dan menyajikan isi file TANPA langkah decode-kunci maupun query pencocokan kepemilikan apa pun. `basename()` sudah diterapkan (mencegah path traversal), namun ini adalah satu-satunya kontrol — tidak ada kontrol otorisasi.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type HttpRequest
  OUTPUT: boolean

  RETURN input.endpoint IN ['Cektiket::loadpdf', 'Cektiket::loadattach', 'Validitas::loadpdf']
         AND input.pathSegment == rawFileName  // bukan kunci terenkripsi
         AND NOT ownershipValidated(input.pathSegment)
END FUNCTION
```

**Examples:**
- `GET cektiket/loadpdf/TIKET_QEHOHTTV001_20260913143103.pdf` tanpa memegang kunci terenkripsi tiket manapun → file tersaji (200, `application/pdf`) karena nama file predictable (`TIKET_{arsipId}_{timestamp}.pdf`) dan hanya diperiksa keberadaan fisiknya.
- `GET cektiket/loadattach/CHAT_XXXX-XXXX-001_20260913150000.jpg` milik pemohon lain → tersaji tanpa validasi kepemilikan.
- `GET validitas/loadpdf/{namaFile}` → pola identik pada controller berbeda.
- Edge case: path traversal (`../../.env`) — SHALL tetap diblokir oleh `basename()` (perilaku existing dipertahankan sebagai lapisan tambahan, bukan diganti).
- Edge case (setelah fix): kunci valid ter-decode namun hasil decode tidak punya file yang cocok pada tabel terkait (contoh: tiket ada tapi belum ada `archiveJenis = 'OUTPUT'`) → SHALL 404, bukan 403 (karena kunci itu sendiri sah, hanya resource-nya tidak ada — dibedakan dari kunci tidak valid yang SHALL 403).

### K3 — Kunci enkripsi tiket fallback hardcode ter-commit di git

**Bug Condition:**

Bug termanifestasi karena `Enkripsi::kunciLegacy()` selalu punya fallback hardcode `'SuPer_Enc-Key2010'` yang aktif tanpa syarat apa pun ketika `EULT_ENCRYPTION_LEGACY_KEY` kosong — termasuk di production, di mana nilai `.env` yang terisi TERBUKTI identik dengan fallback tersebut (bukan rahasia efektif). Tidak ada mekanisme apa pun yang mendeteksi/melaporkan kondisi ini.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type RuntimeContext
  OUTPUT: boolean

  RETURN input.environment == 'production'
         AND (input.envKeyValue == '' OR input.envKeyValue == HARDCODE_FALLBACK)
         // Bug = tidak ada log sinyal DAN fallback masih bisa dipakai untuk decode production
END FUNCTION
```

**Examples:**
- Production `.env` punya `EULT_ENCRYPTION_LEGACY_KEY=SuPer_Enc-Key2010` (identik fallback) → kunci efektif = nilai publik yang siapa pun bisa baca di source code, bukan rahasia. Tidak ada log/warning.
- Production `.env` tidak punya baris `EULT_ENCRYPTION_LEGACY_KEY` sama sekali → `env()` mengembalikan `null`, `kunciLegacy()` jatuh ke fallback hardcode secara diam-diam.
- Edge case (SETELAH fix, keputusan final): production dengan kunci efektif == fallback hardcode ATAU kosong → `decode()`/`encode()` SHALL tidak lagi memakai fallback hardcode sama sekali; operasi kriptografi pada jalur production SHALL gagal terkontrol (bukan diam-diam memakai kunci publik).
- Edge case (preservation): development/testing dengan `.env` kosong → fallback hardcode SHALL tetap dipakai tanpa gangguan (workflow lokal tidak boleh rusak).

### K4 — Debug toolbar publik membocorkan session & PII lintas pengguna

**Bug Condition:**

Bug termanifestasi karena filter `toolbar` (alias `DebugToolbar::class`) terdaftar di `$required['after']` (`app/Config/Filters.php:64`) — kategori filter yang SELALU dijalankan untuk request apa pun tanpa terkecuali, dan `DebugToolbar` filter bawaan CI4 tidak melakukan pengecekan `ENVIRONMENT` di titik registrasi ini (pengecekan environment yang ada di framework CI4 untuk toolbar berada di level lain yang tidak konsisten dijalankan pada konfigurasi project ini — perlu override eksplisit).

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type HttpRequest
  OUTPUT: boolean

  RETURN input.environment != 'development'
         AND toolbarFilterExecutes(input)  // required.after tanpa syarat environment
END FUNCTION
```

**Examples:**
- Server live dengan `CI_ENVIRONMENT=development` (misconfiguration operasional) + `curl https://.../index.php?debugbar_time={ts}` → 200 dengan 421 kemunculan `ticketEmail` pengguna lain, session admin (`logged_in`, `susrSgroupNama`, `susrProfil`), riwayat SQL lengkap — seluruhnya tanpa autentikasi.
- File `writable/debugbar/debugbar_{unix_timestamp}.json` dapat ditemukan/diakses tanpa otorisasi tambahan karena nama predictable.
- Edge case (preservation): developer lokal dengan `ENVIRONMENT=development` → toolbar SHALL tetap aktif penuh, tidak ada regresi workflow development.
- Edge case: `ENVIRONMENT=testing` → toolbar SHALL nonaktif (setara production untuk tujuan fix ini — hanya `development` yang diizinkan).

### T1 — Filter keamanan global dinonaktifkan

**Bug Condition:**

Bug termanifestasi karena `'csrf'`, `'honeypot'`, `'invalidchars'` (before) dan `'honeypot'`, `'secureheaders'` (after) dikomentari di `$globals` (`app/Config/Filters.php:76-83`), sehingga seluruh request — termasuk POST publik ke endpoint sensitif — tidak pernah melewati filter-filter tersebut, dikombinasikan dengan tidak adanya `csrf_field()` pada form manapun di `login.php`.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type HttpRequest
  OUTPUT: boolean

  RETURN input.method == 'POST'
         AND input.route IN ['login/savetiket', 'login/cektiket', 'cektiket/save_replies', 'cektiket/rating', 'otentifikasi', 'otentifikasi/index']
         AND NOT globalFilterActive('csrf')
         AND NOT formContainsCsrfToken(input.origin)
END FUNCTION
```

**Examples:**
- Form buat tiket (`login.php`) dikirim tanpa `csrf_field()` apa pun → diproses penuh oleh `Login::savetiket()`.
- Request POST dari origin manapun (cross-site) ke `login/savetiket` tanpa token → diproses sama seperti request sah, membuka CSRF.
- Edge case (setelah fix): request AJAX existing yang SUDAH mengirim token CSRF valid via header `X-CSRF-TOKEN` → SHALL tetap diproses normal (lihat Preservation 3.13).
- Edge case: request AJAX tanpa token CSRF (skenario serangan) SETELAH fix → SHALL ditolak dengan respons JSON graceful (`{'status': 'danger', ...}`), bukan exception 500 mentah yang tidak bisa ditangani JS existing.

### T2 — Captcha kosmetik tanpa rate limiting

**Bug Condition:**

Bug termanifestasi karena dua kondisi independen yang bergabung: (a) `eult_captcha_helper.php` men-generate teks polos yang dirender langsung ke `<span class="captcha-display">` — nilai captcha terbaca langsung dari HTML/DOM; (b) tidak ada implementasi `Throttler` apa pun di codebase untuk endpoint otentikasi/buat-tiket/lacak-tiket.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type RenderOrRequest
  OUTPUT: boolean

  RETURN (input.type == 'render' AND capchaRenderedAsPlainDomText(input))
         OR (input.type == 'request' AND input.route IN AUTH_AND_TICKET_ROUTES AND NOT rateLimitEnforced(input.ip))
END FUNCTION
```

**Examples:**
- HTML response halaman login mengandung `<span class="captcha-display">ABCD</span>` — nilai captcha terbaca tanpa OCR/effort apa pun.
- 1000 request POST ke `otentifikasi` dalam 60 detik dari IP yang sama → seluruhnya diproses (brute force credential tanpa hambatan).
- 1000 request POST ke `login/savetiket` yang sukses → 1000 email terkirim via `PengirimEmail::buat()` (mail bombing/cost amplification).
- Edge case (setelah fix): request tepat pada batas limit (misal percobaan ke-N dari N maksimum dalam window) → SHALL masih diproses normal; percobaan ke-(N+1) dalam window yang sama → SHALL 429.

### T3 — Cookie `captcha_code` dapat membypass validasi captcha

**Bug Condition:**

Bug termanifestasi karena `eult_captcha_check()` memeriksa `get_cookie('captcha_code')` LEBIH DULU sebelum `session()->get('captcha')` — cookie yang sepenuhnya dikontrol klien (tidak signed, tidak diverifikasi server) memiliki prioritas mengalahkan nilai session server yang sebenarnya valid.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type CaptchaCheckCall
  OUTPUT: boolean

  RETURN input.clientCookie['captcha_code'] IS SET
         AND input.clientCookie['captcha_code'] != input.serverSession['captcha']
         AND strtoupper(input.userInput) == strtoupper(input.clientCookie['captcha_code'])
END FUNCTION
```

**Examples:**
- Penyerang set cookie `captcha_code=ABCD` di browser sendiri, kirim `captcha=ABCD` → lolos validasi TANPA tahu nilai session captcha server yang asli (yang mungkin `XYZ9`).
- Edge case: cookie tidak diset sama sekali (pengguna normal) → fallback ke session — perilaku ini SAMA di kode lama dan baru (bug hanya muncul ketika cookie DISET dan tidak cocok session).

### T4 — `Cektiket::rating()` tanpa validasi kepemilikan tiket

**Bug Condition:**

Bug termanifestasi karena `rating()` menerima `nomorTiket` langsung dari POST body sebagai satu-satunya sumber identifikasi tiket — tidak ada parameter kunci terenkripsi yang di-decode sama sekali pada method ini, berbeda dari `index()`/`cetakterima()` yang konsisten memakai pola kunci→decode.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type HttpRequest
  OUTPUT: boolean

  RETURN input.endpoint == 'Cektiket::rating'
         AND input.identitySource == 'POST.nomorTiket'  // bukan kunci terenkripsi yang didecode
END FUNCTION
```

**Examples:**
- POST ke `cektiket/rating` dengan `nomorTiket` tiket manapun yang diketahui/ditebak → rating tersimpan dan email `PengirimEmail::selesai()` terkirim ke `ticketEmail` tiket tersebut, tanpa penyerang pernah memegang kunci terenkripsi tiket itu.
- Dikombinasikan dengan K1 (SQLi) dan T1 (tidak ada CSRF) → payload SQLi pada `nomorTiket` sekaligus memicu email arbitrer tanpa CSRF token.
- Edge case (setelah fix): kunci tidak valid/tidak dapat didecode → SHALL menghentikan pemrosesan (tidak insert/update `d_rating`, tidak kirim email) dan mengembalikan error, bukan diam-diam melanjutkan dengan `nomorTiket` kosong.

### M1 — Header security browser tidak lengkap

**Bug Condition:**

Bug termanifestasi karena `$CSPEnabled = false` (`app/Config/App.php:192`) dan filter `secureheaders` tidak aktif (bagian dari T1) — kombinasi keduanya membuat header `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` seluruhnya absen dari response.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type HttpResponse
  OUTPUT: boolean

  RETURN NOT input.headers.contains('Content-Security-Policy')
         AND NOT input.headers.contains('X-Frame-Options')
END FUNCTION
```

**Examples:**
- Response halaman apa pun (publik/admin) tidak mengandung header CSP/X-Frame-Options/dst.
- Edge case: setelah T1 mengaktifkan `secureheaders`, tiga header non-CSP otomatis muncul sebagai efek samping — M1 hanya perlu menambahkan aktivasi `$CSPEnabled` + whitelist eksplisit.

### M2 — IDOR serupa di admin `loadpdf`/`loadattach`

**Bug Condition:**

Bug termanifestasi karena `Ticketing::loadpdf()`/`Ticketing::loadattach()` (di balik filter `auth`, jadi butuh sesi staf valid grup apa pun) menyajikan berkas berdasarkan nama file mentah TANPA memeriksa apakah staf tersebut memiliki hak disposisi/unit atas tiket yang berkas tersebut terasosiasi — mirip K2 namun dengan prasyarat autentikasi staf (severity lebih rendah, bukan anonim).

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type HttpRequest
  OUTPUT: boolean

  RETURN input.endpoint IN ['Ticketing::loadpdf', 'Ticketing::loadattach']
         AND input.staffAuthenticated == true
         AND NOT staffHasOwnershipOfAssociatedTicket(input.staffGroup, input.fileOwnerTicket)
END FUNCTION
```

**Examples:**
- Staf unit A (grup non-ADMIN/OPERATOR) menebak nama file `TIKET_{arsipId}_{ts}.pdf` milik tiket unit B → tersaji meski staf A tidak pernah menerima disposisi ke tiket tersebut.
- Edge case (preservation): staf grup `ADMIN`/`OPERATOR` → SHALL tetap bisa mengakses berkas tiket manapun (pola otorisasi existing: admin/operator = akses penuh).

### M3 — Enumerasi nomor tiket tanpa rate limit

**Bug Condition:**

Bug termanifestasi karena `Login::cektiket()` mengembalikan pesan berbeda untuk nomor tiket valid vs tidak valid TANPA rate limit apa pun, memungkinkan enumerasi masif nomor tiket valid. Ini adalah turunan langsung dari T2 (kekurangan rate limiting infrastruktur yang sama).

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type HttpRequest
  OUTPUT: boolean

  RETURN input.route == 'login/cektiket'
         AND NOT rateLimitEnforced(input.ip)
         AND distinguishableResponseMessage(input.result)  // "ditemukan" vs "tidak ditemukan" tetap dipertahankan per keputusan user
END FUNCTION
```

**Examples:**
- 10.000 request nomor tiket tebakan berurutan dari IP sama tanpa hambatan → dapat membedakan valid/tidak valid secara masif.
- Edge case (setelah fix, konsisten dengan T2): request ke-(N+1) dalam window rate limit yang sama dari IP yang sama → 429, menggunakan mekanisme rate limit yang SAMA seperti T2 (bukan mekanisme baru terpisah).

### R1 — Stored XSS potensial pada halaman detail tiket

**Catatan Verifikasi (PENTING — dibaca sebelum root cause):**

Investigasi langsung terhadap `app/Views/pages/ticketing/detail_user.php` (kode saat ini) menunjukkan bahwa ketiga binding yang disebut requirement SUDAH terbungkus `esc()`:
- `repliesMessage` → dirender via `nl2br(esc($pesan))` (variabel `$pesan` di-assign dari `(string) ($value['repliesMessage'] ?? '')`).
- `detailHistory` → dirender via `esc($value['detailHistory'] ?? '')`.
- `ticketTrackingId` → dipakai sebagai variabel PHP `$trackingId` (bukan echo langsung), dan ketika dirender ke atribut HTML dibungkus `esc($trackingId, 'attr')`.

Baris 141/152/203/246/257 yang dikutip requirement, pada kode saat ini, berisi CSS (bukan output dinamis) — kemungkinan requirement disusun dari versi file yang berbeda atau nomor baris sudah shift karena perubahan file sebelumnya. Root cause di bawah TIDAK mengasumsikan bug pada tiga binding tersebut (karena tidak ditemukan), melainkan memformalkan bug condition sebagai **predikat audit umum** (binding manapun yang benar-benar tidak diescape) sehingga jika audit menemukan binding lain yang masih rentan, formalisasi ini tetap berlaku tanpa perlu didesain ulang.

**Bug Condition:**

Bug termanifestasi (JIKA ditemukan pada audit) ketika binding view yang berasal — langsung atau tidak langsung — dari input pengguna publik (`repliesMessage` dari `Cektiket::saveReplies()`, `detailHistory` dari `eult_save_history()` yang memakai `$nama`/`ticketName` dari `Login::savetiket()`) dirender ke output HTML tanpa fungsi `esc()`.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type ViewBinding
  OUTPUT: boolean

  RETURN input.originatesFromPublicUserInput == true
         AND input.renderedAsRawHtml == true  // TIDAK dibungkus esc()
END FUNCTION
```

**Examples:**
- (Hipotetis, belum ditemukan pada audit saat ini) Balasan chat berisi `<script>alert(1)</script>` dirender tanpa `esc()` → payload dieksekusi browser pengguna lain yang melihat halaman yang sama.
- (Terverifikasi TIDAK terjadi saat ini) `repliesMessage`, `detailHistory`, `ticketTrackingId` — ketiganya sudah `esc()`.
- Edge case: karakter HTML wajar dalam teks normal (`<`, `>`, `&`) pada balasan chat sah → SHALL tetap tampil benar secara visual setelah `esc()` (entity-encoded, bukan hilang/merusak tampilan) — ini sudah terverifikasi berfungsi pada kode saat ini.


## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors (lintas semua 12 bug, sesuai prinsip preservasi global `bugfix.md`):**
- Alur lacak tiket publik (`Cektiket::index()`, `Login::cektiket()`) tetap berfungsi untuk pemegang kunci/nomor tiket sah.
- Alur buat tiket publik (`Login::savetiket()`) tetap berfungsi end-to-end: insert `d_ticketing`/`d_archive`, pembuatan PDF arsip, email `PengirimEmail::buat()`.
- Alur login staf/admin (`AuthFilter`, `GuestFilter`, `Otentifikasi::cekDatabase()`) tetap berfungsi tanpa perubahan mekanisme auth itu sendiri (OSM dulu → fallback `password_verify` lokal).
- Email notifikasi (`PengirimEmail::buat()`, `PengirimEmail::selesai()`) tetap terpicu pada kondisi yang sama seperti sebelumnya.
- Upload lampiran chat (`Cektiket::saveReplies()` via `eult_upload_custom()`, validasi ekstensi `pdf|jpg|png`, ukuran `15 * 1024`) tetap berfungsi tanpa perubahan validasi yang sudah benar.
- Struktur respons JSON endpoint AJAX existing (`status`, `message`, `redirect_url`, `new_captcha`) tetap sama persis kontraknya — fix hanya menambah validasi/lapisan keamanan di depan, tidak mengubah bentuk respons sukses.
- Tidak ada redesain arsitektur `Enkripsi` (algoritma AES-256-CBC + HKDF-SHA512 + HMAC, format `safeB64Encode`/`safeB64Decode`, mekanisme multi-kandidat kunci) — hanya isi kandidat kunci production yang berubah (K3).
- Tidak ada penggantian mekanisme auth admin — `AuthFilter`/`GuestFilter`/alur `Otentifikasi` tetap sama; hanya filter tambahan (CSRF, rate-limit) dipasang di depannya (T1, T2).

**Per-bug preservation (ringkas, detail lengkap sudah final di `bugfix.md` bagian Unchanged Behavior 3.1–3.30):**

| Bug | Yang WAJIB tidak berubah |
|---|---|
| K1 | Query dengan `nomorTiket`/`idTiket` valid tetap mengembalikan hasil benar; method model lain yang tidak menerima input publik (`getDisposisiById()`, dll, sudah pakai `escapeString()`) tidak disentuh. |
| K2 | Pemegang kunci sah tetap bisa download PDF/lampiran/validitas; header `Content-Type`/`Content-Disposition` sama seperti sebelumnya. |
| K3 | Development/testing tetap pakai fallback hardcode tanpa gangguan; kunci non-hardcode yang sudah diisi operator tetap dipakai tanpa warning; tidak ada periode migrasi bertahap yang disediakan sistem setelah rotasi. |
| K4 | Developer lokal (`ENVIRONMENT=development`) tetap dapat toolbar penuh; filter `pagecache`/`performance` tidak berubah. |
| T1 | Request AJAX dengan token CSRF valid tetap diproses & respons JSON sama; halaman GET biasa tidak berubah tampilan (hanya tambah header). |
| T2 | Captcha benar (dari gambar) tetap diterima; refresh captcha tetap berfungsi (beda format saja); rate limit tidak memblokir pengguna wajar. |
| T3 | Captcha benar sesuai session tetap lolos (case-insensitive); captcha salah tetap gagal, tidak terpengaruh cookie apa pun. |
| T4 | Pemegang kunci sah tetap bisa rating tiketnya sendiri (insert/update `d_rating`, email `selesai()`, pesan sukses) persis seperti sebelumnya. |
| M1 | Seluruh aset statis (CSS/JS/gambar/font) existing tetap termuat setelah CSP aktif; fitur JS (AJAX, refresh captcha, chat) tetap jalan. |
| M2 | Staf admin/operator/staf dengan disposisi sah tetap akses berkas tanpa gangguan; filter `auth` itu sendiri tidak berubah. |
| M3 | Pengguna sah cek tiket sendiri dalam frekuensi wajar tetap dapat pesan "ditemukan"+redirect; salah ketik sesekali tidak langsung terblokir. |
| R1 | Karakter HTML wajar dalam teks balasan tetap tampil benar visual; link lampiran chat tidak rusak oleh `esc()` pada `repliesMessage`. |

**Scope:**
Seluruh input yang TIDAK memicu C(X) manapun di atas — termasuk seluruh mouse click, form submission dengan data valid, dan traffic pengguna wajar — SHALL sepenuhnya tidak terdampak oleh fix ini.


## Hypothesized Root Cause

Root cause dikelompokkan per klaster (bukan per-bug individual) karena beberapa bug berbagi akar penyebab yang sama pada porting CI3→CI4.

1. **Porting pola query CI3 tanpa migrasi ke Query Builder binding (K1)**: Kode CI3 asli membangun kondisi WHERE sebagai string manual (kebiasaan umum CI3 lama). Saat porting ke CI4, sebagian besar model (`ModelTicketing::byId()` di `index()`/`cetakterima()`) SUDAH dimigrasikan ke `array` binding, namun `Cektiket::rating()` dan jalur `Login::savetiket()` → `eult_auto_increment()` → `ModelMaster::getByLastId()` terlewat — migrasi tidak lengkap/konsisten di seluruh codebase, bukan kesalahan desain baru.

2. **Endpoint download dirancang untuk nama file, bukan identitas terenkripsi (K2, M2)**: `loadpdf()`/`loadattach()` kemungkinan awalnya dirancang sebagai "file server" generik (menerima nama file, serve jika ada) tanpa mempertimbangkan bahwa nama file predictable dari pola penamaan (`TIKET_{arsipId}_{timestamp}.pdf`, `CHAT_{idTiket}_{timestamp}.{ext}`). Endpoint lain di controller yang sama (`index()`, `cetakterima()`) SUDAH memakai pola kunci→decode dengan benar — inkonsistensi desain antar-method dalam controller yang sama.

3. **Fallback development yang tidak pernah dipisahkan dari jalur production (K3)**: Komentar kode (`Enkripsi.php:13-15`) mengindikasikan fallback hardcode sengaja dipertahankan untuk kompatibilitas "kunci legacy CI3" — namun tidak ada percabangan `ENVIRONMENT` yang memisahkan kapan fallback ini boleh aktif (dev/testing) vs harus mati total (production). Operator kemungkinan belum pernah merotasi `.env` production karena tidak ada sinyal apa pun yang memberitahu bahwa kunci production == fallback publik.

4. **Filter registrasi tidak mempertimbangkan environment untuk toolbar (K4)**: `toolbar` didaftarkan di kategori `required.after` yang secara desain framework CI4 dijalankan tanpa terkecuali untuk memastikan fungsi inti (page cache, performance metrics) selalu jalan — namun `DebugToolbar` filter idealnya TIDAK seharusnya di kategori "always run" tanpa syarat environment; kemungkinan disalin dari skeleton CI4 default tanpa penyesuaian untuk deployment publik.

5. **Filter global dikomentari selama development, tidak pernah diaktifkan kembali sebelum production (T1)**: Baris komentar (`Filters.php:76-83`) berpola khas "dimatikan untuk debugging/testing form" yang lupa dikembalikan. Tidak ada `csrf_field()` di form manapun mengkonfirmasi filter ini sudah lama nonaktif (form tidak pernah didesain untuk menyertakan token).

6. **Captcha di-porting sebagai tampilan teks, belum sempat diganti ke image rendering; rate limiting belum pernah diimplementasikan (T2, M3)**: `eult_captcha_helper.php` komentarnya menyebut "porting dari CI3" — kemungkinan captcha CI3 asli juga sederhana dan porting ini belum menambah lapisan image-rendering maupun `Config\Throttle` yang memang belum ada satu file konfigurasi pun di codebase (dikonfirmasi tidak ditemukan file `Config/Throttle.php`).

7. **Warisan mekanisme cookie CI3 yang tidak dihapus saat migrasi ke session CI4 (T3)**: Komentar `eult_captcha_check()` ("Kompatibel dengan cookie lama 'captcha_code' bila masih ada") mengonfirmasi ini adalah sisa kompatibilitas mundur yang sengaja dipertahankan namun urutan pengecekannya salah (cookie duluan, seharusnya session yang dipercaya, bukan cookie klien).

8. **`rating()` tidak mengikuti pola konsisten milik method lain di controller yang sama (T4)**: Sama seperti akar #2, method ini kemungkinan ditambahkan terpisah dari `index()`/`cetakterima()` tanpa mengikuti pola kunci-terenkripsi yang sudah mapan di method-method tersebut dalam file yang sama.

9. **`$CSPEnabled = false` adalah default framework CI4 yang belum pernah diaktifkan (M1)**: Nilai default skeleton CI4 adalah `false` — kemungkinan belum pernah disentuh sejak scaffolding awal project, bukan dinonaktifkan secara sengaja.

10. **R1 kemungkinan SUDAH diperbaiki pada iterasi sebelumnya tanpa requirement diperbarui**: Ditemukan bahwa `esc()` SUDAH terpasang pada ketiga binding utama yang disebut requirement. Root cause paling mungkin: audit yang menghasilkan requirement ini dilakukan pada snapshot kode yang lebih lama, atau perbaikan parsial sudah masuk melalui perubahan lain (git history) tanpa dokumentasi eksplisit. Tindak lanjut: audit ulang menyeluruh untuk binding LAIN yang belum diverifikasi (bukan tiga binding yang sudah dikonfirmasi aman).


## Correctness Properties

Property 1: Bug Condition - K1 SQL Injection Dinetralkan via Kondisi Array

_For any_ nilai string `nomorTiket`/`idTiket` (termasuk yang mengandung metacharacter SQL seperti `'`, `OR`, `--`, `;`), ketika dipakai sebagai kondisi pencarian pada `Cektiket::rating()`, `Login::savetiket()` (termasuk jalur `eult_auto_increment()`/`ModelMaster::getByLastId()`), dan readback `byId()` pasca-insert, fungsi yang telah diperbaiki SHALL memperlakukan nilai tersebut sebagai literal string binding (bukan fragmen SQL), sehingga query HANYA mengembalikan baris yang benar-benar cocok secara exact-match (0 baris untuk nilai yang tidak ada di database, tidak pernah mengembalikan baris tambahan akibat injeksi) dan TIDAK PERNAH menghasilkan SQL error akibat syntax yang rusak oleh metacharacter.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**

Property 2: Preservation - K1 Query Valid Tetap Berfungsi Normal

_For any_ `nomorTiket`/`idTiket` yang valid (benar-benar ada di `d_ticketing`/`d_archive`) dan TIDAK mengandung payload SQLi, fungsi yang telah diperbaiki SHALL mengembalikan hasil identik dengan fungsi asli sebelum fix — termasuk kemampuan `Login::savetiket()` menghasilkan `archiveId` berurutan yang benar dan `Cektiket::rating()` menyimpan/mengupdate `d_rating` serta memicu email seperti sebelumnya.

**Validates: Requirements 3.1, 3.2, 3.3**

Property 3: Bug Condition - K2/M2 Endpoint Download Memvalidasi Kepemilikan via Kunci

_For any_ request GET ke endpoint download (`Cektiket::loadpdf`, `Cektiket::loadattach`, `Validitas::loadpdf`, dan `Ticketing::loadpdf`/`Ticketing::loadattach` untuk staf tanpa ownership), fungsi yang telah diperbaiki SHALL HANYA menyajikan konten berkas ketika kunci terenkripsi (publik) atau sesi staf (admin) dapat didecode/divalidasi DAN hasilnya benar-benar berasosiasi dengan berkas yang direquest (via query pencocokan `archiveTrackingId`/`repliesTicketId`/ownership unit-disposisi) — untuk kunci tidak valid/tidak match, SHALL mengembalikan 403 atau 404 dan TIDAK menyajikan konten apa pun.

**Validates: Requirements 2.6, 2.7, 2.8, 2.9, 2.10, 2.40, 2.41**

Property 4: Preservation - K2/M2 Pemilik Sah Tetap Dapat Mengunduh

_For any_ kunci terenkripsi milik pemegang sah (publik) atau staf dengan ownership/disposisi/grup ADMIN-OPERATOR yang sah (admin), ketika berkas yang diminta benar-benar berasosiasi dengannya, fungsi yang telah diperbaiki SHALL menyajikan berkas yang sama persis (mime-type, `Content-Disposition` header) seperti perilaku sebelum fix.

**Validates: Requirements 3.4, 3.5, 3.6, 3.7, 3.25, 3.26**

Property 5: Bug Condition - K3 Fallback Hardcode Tidak Aktif di Production

_For any_ kondisi runtime di mana `ENVIRONMENT === 'production'`, fungsi enkripsi/dekripsi yang telah diperbaiki SHALL TIDAK PERNAH menggunakan string fallback hardcode `'SuPer_Enc-Key2010'` sebagai kunci efektif untuk decode/encode — baik ketika `EULT_ENCRYPTION_LEGACY_KEY` kosong maupun ketika bernilai sama dengan fallback tersebut, operasi kriptografi pada jalur production SHALL gagal secara terkontrol (bukan diam-diam berhasil dengan kunci publik).

**Validates: Requirements 2.14, 2.15**

Property 6: Preservation - K3 Development Tetap Memakai Fallback, Kunci Kuat Tetap Diterima

_For any_ kondisi runtime di mana `ENVIRONMENT` adalah `development` atau `testing`, fallback hardcode SHALL tetap berfungsi tanpa gangguan; dan _for any_ `EULT_ENCRYPTION_LEGACY_KEY` production yang sudah diisi nilai kuat non-hardcode, fungsi enkripsi SHALL menggunakan nilai tersebut tanpa peringatan apa pun.

**Validates: Requirements 3.8, 3.9, 3.10**

Property 7: Bug Condition - K4 Toolbar Debug Hanya Aktif pada Development

_For any_ nilai `ENVIRONMENT` (`production`, `testing`, atau `development`) dan _for any_ request ke endpoint toolbar (`index.php?debugbar_time=...`), sistem yang telah diperbaiki SHALL mengaktifkan toolbar debug JIKA DAN HANYA JIKA `ENVIRONMENT === 'development'` — untuk nilai lainnya, endpoint tersebut SHALL tidak mengembalikan data debug apa pun (setara toolbar nonaktif total, bukan hanya UI tersembunyi di klien).

**Validates: Requirements 2.17, 2.18**

Property 8: Preservation - K4 Development Tetap Mendapat Toolbar Penuh

_For any_ request pada `ENVIRONMENT === 'development'`, sistem yang telah diperbaiki SHALL CONTINUE TO mengaktifkan toolbar dan menyimpan file debug di `writable/debugbar/` persis seperti perilaku sebelum fix.

**Validates: Requirements 3.11, 3.12**

Property 9: Bug Condition - T1 CSRF Ditegakkan pada Endpoint Sensitif

_For any_ request POST ke endpoint yang dilindungi (`login/savetiket`, `login/cektiket`, `cektiket/save_replies`, `cektiket/rating`, `otentifikasi`/`otentifikasi/index`) yang TIDAK menyertakan token CSRF valid, sistem yang telah diperbaiki SHALL menolak request tersebut (tidak memproses insert/update/side-effect apa pun) dan mengembalikan respons yang dapat ditangani secara graceful oleh JavaScript existing.

**Validates: Requirements 2.21, 2.22, 2.23, 2.24, 2.25, 2.26**

Property 10: Preservation - T1 Request Sah dengan Token Tetap Diproses

_For any_ request POST ke endpoint yang sama dengan token CSRF valid (sesuai `Config\Security::$headerName`/`$regenerate`), sistem yang telah diperbaiki SHALL memproses request tersebut dan mengembalikan struktur respons JSON (`status`, `message`, `redirect_url`, `new_captcha`) yang identik dengan sebelum fix.

**Validates: Requirements 3.13, 3.14, 3.15**

Property 11: Bug Condition - T2/M3 Rate Limiting Menegakkan Batas per-IP

_For any_ IP address yang mengirim request ke route yang diberi rate limit (`otentifikasi`, `login/savetiket`, `login/cektiket`) melebihi ambang batas dalam window waktu yang dikonfigurasi, sistem yang telah diperbaiki SHALL mengembalikan HTTP 429 (atau respons JSON setara) untuk request ke-(N+1) dan seterusnya dalam window tersebut, dan TIDAK memproses request tersebut seperti biasa.

**Validates: Requirements 2.29, 2.30, 2.42**

Property 12: Preservation - T2/M3 Pengguna Wajar Tidak Terblokir

_For any_ IP address yang mengirim request dalam frekuensi wajar (di bawah ambang batas rate limit), sistem yang telah diperbaiki SHALL memproses seluruh request tersebut tanpa terhalang rate limit, termasuk kasus salah ketik nomor tiket sesekali dan captcha benar dari gambar.

**Validates: Requirements 3.16, 3.17, 3.18, 3.27, 3.28**

Property 13: Bug Condition - T2 Nilai Captcha Tidak Pernah Terkirim Plaintext ke Klien

_For any_ response yang dikirim ke klien (halaman render maupun AJAX `new_captcha`), sistem yang telah diperbaiki SHALL TIDAK PERNAH menyertakan nilai captcha session dalam bentuk teks polos apa pun — hanya URL/endpoint gambar captcha yang dikirim.

**Validates: Requirements 2.27, 2.28**

Property 14: Bug Condition - T3 Cookie Captcha Tidak Pernah Mempengaruhi Hasil Validasi

_For any_ nilai cookie `captcha_code` (termasuk yang sengaja diset penyerang untuk cocok dengan input yang dikirim, dan termasuk nilai yang tidak cocok dengan session), hasil `eult_captcha_check(input)` pada fungsi yang telah diperbaiki SHALL identik dengan hasil yang dihitung HANYA berdasarkan `session()->get('captcha')` — nilai cookie SHALL sama sekali tidak dibaca atau mempengaruhi hasil.

**Validates: Requirements 2.31, 2.32, 2.33**

Property 15: Preservation - T3 Validasi Session Tetap Berfungsi Identik

_For any_ input captcha dan nilai session captcha (case-insensitive), hasil `eult_captcha_check()` yang telah diperbaiki SHALL sama persis dengan hasil `strtoupper(input) === strtoupper(session)` yang sudah ada — captcha benar tetap lolos, captcha salah tetap gagal, independen dari keberadaan cookie apa pun.

**Validates: Requirements 3.19, 3.20**

Property 16: Bug Condition - T4 Rating Hanya Beroperasi atas Hasil Decode Kunci

_For any_ kunci terenkripsi yang dikirim ke `Cektiket::rating()`, seluruh operasi berikutnya dalam method tersebut (`byId()`, `ambilSatu('d_rating', ...)`, `tambah()`/`ubah()` pada `d_rating`, `ambilSatu('d_archive', ...)`, pengiriman email) SHALL menggunakan HANYA `nomorTiket` hasil decode kunci tersebut sebagai identitas — untuk kunci tidak valid/tidak dapat didecode, SHALL menghentikan pemrosesan tanpa insert/update/email apa pun.

**Validates: Requirements 2.34, 2.35, 2.36, 2.37**

Property 17: Preservation - T4 Pemilik Sah Tetap Bisa Rating

_For any_ kunci terenkripsi milik pemegang sah yang decode ke `nomorTiket` valid, sistem yang telah diperbaiki SHALL menyimpan rating (insert jika belum ada, update jika sudah ada) dan mengirim email `selesai()` beserta lampiran, persis seperti perilaku sebelum fix.

**Validates: Requirements 3.21, 3.22**

Property 18: Bug Condition - M1 Header Keamanan Selalu Hadir

_For any_ response HTTP yang dikirim setelah fix (halaman publik maupun admin), sistem SHALL menyertakan header `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, dan `Referrer-Policy`.

**Validates: Requirements 2.38, 2.39**

Property 19: Preservation - M1 Aset dan Fitur JS Tidak Terblokir CSP

_For any_ aset statis (CSS/JS/gambar/font) dan fitur JavaScript (AJAX submit, refresh captcha, chat lampiran) yang sudah dipakai halaman existing, whitelist CSP yang dikonfigurasi SHALL mengizinkan seluruhnya termuat/berjalan tanpa error pemblokiran.

**Validates: Requirements 3.23, 3.24**

Property 20: Bug Condition - R1 Audit Binding View Tanpa Escaping (jika ditemukan)

_For any_ binding pada `detail_user.php` yang nilainya berasal — langsung atau tidak langsung — dari input pengguna publik dan ditemukan dirender tanpa `esc()` pada audit ulang, fix SHALL membungkusnya dengan `esc()` sesuai konteks (HTML/attr) sehingga karakter HTML pada payload apa pun dirender sebagai teks, bukan dieksekusi sebagai markup/script.

**Validates: Requirements 2.44, 2.45, 2.46**

Property 21: Preservation - R1 Tampilan Teks Wajar dan Link Lampiran Tidak Rusak

_For any_ konten balasan chat yang berisi karakter HTML biasa dalam teks wajar (`<`, `>`, `&`), hasil `esc()` SHALL menampilkannya sebagai teks yang benar secara visual (entity-encoded, bukan hilang/merusak tampilan); dan penambahan `esc()` pada `repliesMessage` SHALL TIDAK merusak link lampiran (`repliesFile`) yang dirender terpisah pada baris yang sama.

**Validates: Requirements 3.29, 3.30**


## Fix Implementation

### K1 — Query Aman (Array Binding)

**File**: `app/Controllers/Cektiket.php`

**Function**: `rating()`

**Specific Changes**:
1. Ganti `$this->tiket->byId("ticketTrackingId = '" . $nomorTiket . "'")` menjadi `$this->tiket->byId(['ticketTrackingId' => $nomorTiket])`.
2. Ganti `$this->tiket->ambilSatu('d_archive', "archiveTrackingId = '" . $nomorTiket . "' AND (archiveJenis = 'OUTPUT' or archiveJenis = 'TTD')")` menjadi kondisi array dengan `whereIn`/`groupStart` setara, contoh: `$this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $nomorTiket])` dikombinasikan dengan filter `archiveJenis` via `whereIn('archiveJenis', ['OUTPUT', 'TTD'])` pada level query builder jika `ambilSatu()` perlu diperluas untuk menerima kondisi OR terstruktur — atau, jika `ambilSatu()` tetap menerima `array` sederhana saja, pecah pemanggilan menjadi query builder langsung di dalam model untuk mendukung kondisi majemuk secara aman.

**File**: `app/Controllers/Login.php`

**Function**: `savetiket()`

**Specific Changes**:
3. Ganti `$this->tiket->byId("ticketTrackingId = '" . $idTiket . "'")` (readback pasca-insert) menjadi `$this->tiket->byId(['ticketTrackingId' => $idTiket])`.

**File**: `app/Helpers/eult_kode_helper.php`

**Function**: `eult_auto_increment()`

**Specific Changes**:
4. Ubah signature agar menerima kondisi sebagai `array` alih-alih `string` (`function eult_auto_increment(string $tabel, string $kolom, string $nip, array $kondisi): string`), dan update seluruh caller (`Login::savetiket()`: `eult_auto_increment('d_archive', 'archiveId', $arsipId, ['archiveTrackingId' => $idTiket])`).

**File**: `app/Models/ModelMaster.php`

**Function**: `getByLastId()`

**Specific Changes**:
5. Signature sudah menerima `array|string $kondisi` — pastikan seluruh caller baru memakai `array`; pertahankan dukungan `string` HANYA untuk caller internal lain yang sudah memvalidasi/tidak menerima input publik (agar tidak melanggar Preservation 3.3), namun tandai parameter `string` sebagai deprecated-for-public-input pada docblock.

### K2/M2 — Kepemilikan Berkas via Kunci Terenkripsi

**File**: `app/Controllers/Cektiket.php`

**Function**: `loadpdf(string $namaFile)` → `loadpdf(string $kunci)`

**Specific Changes**:
1. Ubah signature dari menerima nama file mentah menjadi menerima `$kunci` terenkripsi.
2. Decode `$kunci` via `Enkripsi::decode()` untuk memperoleh `archiveTrackingId`.
3. Query `d_archive` dengan kondisi array (`['archiveTrackingId' => $id, 'archiveJenis' => 'OUTPUT']`) untuk memperoleh `archiveFile` yang benar-benar terasosiasi.
4. HANYA serve file jika hasil query ditemukan DAN `basename($hasil['archiveFile'])` sama dengan file fisik yang akan disajikan (bukan file arbitrer manapun).
5. Kunci tidak valid/decode gagal/tidak ada file cocok → response 403/404, jangan panggil `file_get_contents()` sama sekali.
6. Update caller di `Cektiket::index()` (`$output_url`) agar mengirim `$kunci` (bukan `$output['archiveFile']`) sebagai path segment — kunci dapat berupa `Enkripsi::encode($nomorTiket)` yang sama dengan kunci halaman, karena decode target sama.

**Function**: `loadattach(string $namaFile)` → `loadattach(string $kunci)`

**Specific Changes**:
7. Pola identik poin 1-5, namun query ke `d_replies` dengan kondisi (`['repliesTicketId' => $id]`) dan validasi `repliesFile` yang cocok dengan file diminta. Karena satu tiket bisa punya banyak lampiran, `$kunci` perlu membawa identitas kombinasi tiket+nama file — opsi teknis: encode payload `"{nomorTiket}|{namaFile}"` sebagai satu kunci, lalu decode dan pisah, ATAU tetap terima `$namaFile` sebagai path segment TAMBAHAN di samping `$kunci` tiket, lalu validasi bahwa `$namaFile` benar-benar muncul di `repliesFile` pada baris `d_replies` milik `$id` hasil decode `$kunci` (pendekatan kedua lebih sederhana dan konsisten dengan pola URL existing `load_attach/{namaFile}` yang sudah dipakai view — akan didetailkan sebagai route `cektiket/loadattach/(:kunci)/(:namaFile)` dengan dua segment).
8. Update caller di view (`$load_attach` pada `detail_user.php`) agar menyertakan `$kunci` halaman sebagai segment tambahan sebelum nama file.

**File**: `app/Controllers/Validitas.php`

**Function**: `loadpdf(string $namaFile)` → `loadpdf(string $kunci)`

**Specific Changes**:
9. Pola identik poin 1-5, query ke `d_archive` dengan `archiveTrackingId` hasil decode dan `archiveJenis` yang relevan untuk surat validitas.
10. Update route `validitas/loadpdf/(:any)` tetap menerima satu segment (kunci), dan caller pemanggil link (jika ada di view `pages/validitas/index.php`) diarahkan mengirim kunci yang sama dengan halaman validitas.

**File**: `app/Controllers/Ticketing.php`

**Function**: `loadpdf()`, `loadattach()` (versi admin, di balik filter `auth`)

**Specific Changes**:
11. Tambahkan pengecekan ownership SEBELUM serve: ambil `logged_in` session (`susrSgroupNama`), jika grup `ADMIN`/`OPERATOR*` → lanjutkan tanpa batasan tambahan (konsisten pola existing di `Ticketing.php:89-91`); jika grup lain → decode/cari tiket pemilik file terkait (via lookup `archiveTrackingId`/`repliesTicketId` dari nama file, lalu cek disposisi/unit staf terhadap tiket tersebut menggunakan `s_user_group_unit`/`d_disposisi` yang sudah ada), jika tidak match → 403.
12. Perubahan ini TIDAK mengubah filter `auth` itu sendiri — hanya menambah validasi di dalam body controller setelah filter lolos.

### K3 — Kunci Enkripsi Production Diketatkan

**File**: `app/Libraries/Enkripsi.php`

**Function**: `kunciLegacy()`

**Specific Changes**:
1. Tambahkan pengecekan `ENVIRONMENT` di awal fungsi: jika `ENVIRONMENT === 'production'`, JANGAN kembalikan fallback hardcode — jika `EULT_ENCRYPTION_LEGACY_KEY` kosong pada production, kembalikan nilai yang menyebabkan operasi kriptografi gagal terkontrol (misal string kosong yang membuat `encode()`/`decode()` mengembalikan `false` melalui jalur validasi yang sudah ada, BUKAN exception yang crash aplikasi — konsisten dengan Acceptance Criteria 2.13 "tidak menghentikan aplikasi secara paksa").
2. Jika `EULT_ENCRYPTION_LEGACY_KEY` production terisi dan SAMA dengan fallback hardcode, log critical (`log_message('critical', 'Kunci enkripsi production masih sama dengan nilai default/hardcode — harus dirotasi.')`) sebagai defense-in-depth untuk operator yang belum sadar, TETAP lanjutkan memakai nilai tersebut untuk sesi berjalan ini (karena keputusan final adalah fallback hardcode dihapus dari KODE, bukan dari kemungkinan operator mengisi `.env` dengan nilai yang identik secara tidak sengaja — kasus ini tetap harus di-log, bukan otomatis diblokir, agar operator punya sinyal untuk mengganti nilainya).
3. Untuk `ENVIRONMENT !== 'production'` (development/testing), fallback hardcode TETAP dikembalikan tanpa log/warning (preservation).
4. Hapus baris komentar docblock kelas yang menyatakan "Jangan ganti kunci ini sampai seluruh URL lama kedaluwarsa" dan ganti dengan catatan bahwa fallback production sudah dihapus per keputusan K3.

### K4 — Toolbar Hanya Development

**File**: `app/Config/Filters.php`

**Specific Changes**:
1. Pindahkan `'toolbar'` dari `$required['after']` ke `$globals['after']` dengan kondisi eksplisit, ATAU pertahankan di `$required['after']` namun override behavior aktivasinya via kelas filter custom (`App\Filters\EnvironmentAwareToolbar` yang extends/wrap `DebugToolbar`) yang memeriksa `ENVIRONMENT === 'development'` sebelum memanggil parent `after()` — pendekatan kedua lebih aman karena tidak mengubah kategori `required` (Preservation 3.12 terkait filter `pagecache`/`performance` lain di kategori yang sama tidak ikut terganggu).
2. Kelas filter custom SHALL mengembalikan response tanpa modifikasi apa pun (pass-through) ketika `ENVIRONMENT !== 'development'`, sehingga endpoint `debugbar_time` tidak pernah mendapat data (setara 404/kosong sesuai Acceptance Criteria 2.18).
3. Tidak mengubah mekanisme penyimpanan `writable/debugbar/*.json` — hanya syarat aktivasi (Preservation 3.11, 2.20).
4. Tambahkan catatan dokumentasi (bukan kode) merekomendasikan `CI_ENVIRONMENT=production` wajib di server publik (Acceptance Criteria 2.19).

### T1 — Filter Keamanan Global Diaktifkan

**File**: `app/Config/Filters.php`

**Specific Changes**:
1. Uncomment `'csrf'`, `'invalidchars'` di `$globals['before']`; uncomment `'secureheaders'` di `$globals['after']`.
2. Tambahkan handling `except` pada entri global untuk `'csrf'` HANYA jika benar-benar diperlukan dan dikonfirmasi user terlebih dahulu (default: tidak ada exclude URI apa pun ditambahkan, sesuai Acceptance Criteria 2.24).

**File**: `app/Views/layouts/login.php`

**Specific Changes**:
3. Tambahkan `<?= csrf_field() ?>` di dalam setiap elemen `<form>` yang mengirim POST (form buat tiket, form lacak tiket, form login admin jika di file yang sama).

**File**: JavaScript existing (inline di `login.php` atau file JS terpisah yang menangani AJAX submit)

**Specific Changes**:
4. Sebelum setiap `$.ajax()`/`fetch()` POST ke `login/savetiket`, `login/cektiket`, `cektiket/save_replies`, `cektiket/rating`, tambahkan header `X-CSRF-TOKEN` dengan nilai token CSRF terkini (dibaca dari meta tag/hidden input yang di-render server, atau dari respons `new_captcha` jika token disertakan di sana — didetailkan saat implementasi).
5. Setelah setiap submit sukses, update nilai token dari respons server (mengikuti `$regenerate = true`) agar submit berikutnya memakai token baru.

**File**: `app/Controllers/BaseController.php` atau exception handler CSRF khusus

**Specific Changes**:
6. Tambahkan handler untuk `CSRFException`/kegagalan validasi CSRF pada request AJAX (deteksi via header `X-Requested-With`/`Accept: application/json`) agar mengembalikan JSON graceful (`{'status': 'danger', 'message': 'Sesi form telah berakhir, silakan muat ulang halaman.'}`) alih-alih HTML error page mentah.

**File**: `app/Views/layouts/login.php` (form terkait honeypot)

**Specific Changes**:
7. Verifikasi kompatibilitas field honeypot otomatis CI4 dengan struktur form existing setelah `'honeypot'` diaktifkan kembali di `$globals`.

### T2 — Captcha Gambar + Rate Limiting

**File**: `app/Helpers/eult_captcha_helper.php`

**Function**: `eult_captcha_generate()` (tidak berubah logikanya, tetap simpan ke session) + fungsi baru untuk render gambar

**Specific Changes**:
1. Tambahkan fungsi baru (misal `eult_captcha_image(string $teks): string` yang mengembalikan binary PNG) menggunakan GD (`imagecreate`, `imagestring`/`imagettftext` dengan distorsi garis/noise) — deteksi ekstensi tersedia (`extension_loaded('gd')`) dengan fallback Imagick jika GD tidak tersedia.
2. `eult_captcha_generate()` TETAP menyimpan nilai ke `session()->set('captcha', ...)` — tidak berubah (Preservation 2.28).

**File**: `app/Controllers/Login.php`

**Function**: `refreshCaptcha()`, dan endpoint baru untuk serve gambar captcha

**Specific Changes**:
3. Tambahkan method baru `captchaImage()` yang generate teks captcha baru (atau baca dari session jika belum expired), render via `eult_captcha_image()`, dan return response dengan `Content-Type: image/png`.
4. `refreshCaptcha()` (dipanggil AJAX existing) diubah agar responsnya berisi URL gambar (`captcha_image_url`) alih-alih string captcha polos.
5. `Otentifikasi::index()`, `Login::savetiket()` — respons `new_captcha` diubah dari string captcha menjadi URL endpoint gambar.

**File**: `app/Views/layouts/login.php`

**Specific Changes**:
6. Ganti `<span class="captcha-display">` menjadi `<img src="{captcha_image_url}">`.

**File**: `app/Config/Throttle.php` (file baru)

**Specific Changes**:
7. Buat file konfigurasi `Config\Throttle` bawaan CI4 (belum ada di codebase — dikonfirmasi via pencarian) dengan definisi rate `Services::throttler()`.

**File**: `app/Config/Filters.php`

**Specific Changes**:
8. Buat filter custom (`App\Filters\ThrottleFilter`) yang memanggil `Services::throttler()->check($ip, $limit, $seconds)`, kembalikan 429 jika `false`. Terapkan pada route `otentifikasi`, `login/savetiket`, `login/cektiket` via `$filters` (bukan `$globals`, agar hanya route ini yang dibatasi).

### T3 — Hapus Fallback Cookie Captcha

**File**: `app/Helpers/eult_captcha_helper.php`

**Function**: `eult_captcha_check()`

**Specific Changes**:
1. Hapus baris `$tersimpan = get_cookie('captcha_code'); if (! $tersimpan) { ... }` sepenuhnya.
2. Ganti langsung dengan `$tersimpan = session()->get('captcha');`.
3. Sisa logika (`is_string`/`strtoupper` comparison) TIDAK berubah.
4. Update docblock fungsi, hapus kalimat "Kompatibel dengan cookie lama 'captcha_code' bila masih ada".

### T4 — Rating Memakai Kunci Terenkripsi

**File**: `app/Controllers/Cektiket.php`

**Function**: `rating()` → `rating(string $kunci = '')`

**Specific Changes**:
1. Ubah signature untuk menerima `$kunci` (route `cektiket/rating/(:any)` POST, atau tetap POST tanpa segment URL namun `$kunci` dikirim sebagai field body — mengikuti konvensi form AJAX existing; disarankan sebagai field POST `kunci` agar tidak mengubah pola routing POST lain).
2. Decode `$kunci` via `Enkripsi::decode()` di awal method → `$nomorTiket`.
3. Jika decode gagal (`empty($nomorTiket)`), hentikan dan return response error tanpa insert/update/email apa pun.
4. Ganti SELURUH pemakaian variabel `nomorTiket` (yang sebelumnya dari `$this->request->getPost('nomorTiket')`) dengan hasil decode ini — untuk `byId()`, `ambilSatu('d_rating', ...)`, `tambah()`/`ubah()`, `ambilSatu('d_archive', ...)`.
5. Terapkan fix K1 (array binding) bersamaan pada method ini karena berada di file/fungsi yang sama.

**File**: `app/Views/pages/ticketing/detail_user.php`

**Specific Changes**:
6. Update form/AJAX submit rating agar menyertakan `$kunci` halaman (variabel yang sudah tersedia di context, sama seperti dipakai `cetakterima`) sebagai pengganti `nomorTiket` mentah.

### M1 — CSP dan Header Lengkap

**File**: `app/Config/App.php`

**Specific Changes**:
1. Ubah `$CSPEnabled = false` menjadi `true`.

**File**: `app/Config/ContentSecurityPolicy.php`

**Specific Changes**:
2. Konfigurasi whitelist minimal mencakup sumber daya yang benar-benar dipakai (`self`, CDN font/JS yang dipakai tema `login.php`/`detail_user.php` — diaudit saat implementasi untuk daftar domain pasti), dengan `styleSrc`/`scriptSrc` mengizinkan `unsafe-inline` HANYA jika inline script/style memang dipakai existing dan tidak direfaktor sebagai bagian scope M1 (Preservation 3.24).

### M3 — Rate Limit Enumerasi (turunan T2)

**Specific Changes**:
1. Tidak ada perubahan kode terpisah — route `login/cektiket` SUDAH termasuk dalam daftar route yang diberi `ThrottleFilter` pada T2 poin 8. Pesan "ditemukan"/"tidak ditemukan" TIDAK diubah (keputusan user, Acceptance Criteria 2.43).

### R1 — Audit Ulang esc()

**File**: `app/Views/pages/ticketing/detail_user.php`

**Specific Changes**:
1. Audit ulang MENYELURUH seluruh interpolasi `<?= $variabel ?>`/`<?php echo $variabel ?>` di file ini untuk binding yang berasal dari data pengguna (bukan hanya 3 binding yang disebut requirement, yang sudah dikonfirmasi aman) — termasuk field lain pada array `$datas`/`$value` yang mungkin berasal dari input publik tidak langsung (`ticketName`, `ticketSubject`, `ticketMessage`, dll jika dirender di halaman ini).
2. Untuk binding yang ditemukan TANPA `esc()`, bungkus dengan `esc()` sesuai konteks (`'html'` default untuk teks, `'attr'` untuk atribut HTML, `'js'` jika dirender ke dalam blok `<script>`).
3. Dokumentasikan hasil audit (binding mana yang sudah aman vs yang diperbaiki) sebagai bagian dari catatan implementasi, karena requirement mengutip baris yang sudah tidak relevan dengan kode saat ini.


## Testing Strategy

### Validation Approach

Testing mengikuti pola dua-fase untuk setiap bug: (1) surfacing counterexample pada kode BELUM diperbaiki untuk mengonfirmasi/menyanggah root cause per-bug, (2) verifikasi fix checking + preservation checking pada kode SUDAH diperbaiki. Karena spec ini mencakup 12 bug dengan karakteristik berbeda (beberapa cocok PBT domain besar seperti K1/T3, beberapa cocok example-based karena domain diskrit/config seperti K3/K4/T1), strategi testing per-bug ditentukan berdasarkan hasil prework testability yang sudah dianalisis (properti murni vs example vs audit).

### Exploratory Bug Condition Checking

**Goal**: Surface counterexample yang mendemonstrasikan tiap bug SEBELUM fix diimplementasikan, guna mengonfirmasi/menyanggah root cause di atas.

**Test Plan**: Untuk tiap bug, tulis test yang mereproduksi kondisi persis seperti yang sudah diverifikasi manual (`curl`, pembacaan kode) pada bagian Bug Analysis `bugfix.md`, jalankan pada kode BELUM diperbaiki.

**Test Cases**:
1. **K1**: Kirim `nomorTiket = "X' OR '1'='1"` ke `rating()` pada kode asli → assert query mengembalikan >1 baris atau seluruh tabel (gagal pada kode asli, membuktikan SQLi).
2. **K2**: `GET cektiket/loadpdf/{namaFileTebakan}` tanpa kunci apa pun pada kode asli → assert 200 dengan konten file (gagal pada kode asli, membuktikan IDOR).
3. **K3**: Set `ENVIRONMENT=production`, hapus `EULT_ENCRYPTION_LEGACY_KEY` dari `.env` test → assert `kunciLegacy()` pada kode asli tetap mengembalikan `'SuPer_Enc-Key2010'` (gagal karena tidak ada penolakan, membuktikan fallback aktif tanpa syarat).
4. **K4**: Set `ENVIRONMENT=testing`, request `?debugbar_time=...` pada kode asli → assert response mengandung data debug (gagal, membuktikan toolbar aktif tanpa syarat environment).
5. **T1**: POST ke `login/savetiket` tanpa header/field CSRF apa pun pada kode asli → assert request diproses penuh (gagal, membuktikan tidak ada validasi CSRF).
6. **T2**: Assert HTML response mengandung nilai captcha polos dalam DOM (`captcha-display`); kirim 100 request beruntun ke `otentifikasi` → assert seluruhnya diproses tanpa 429 (gagal, membuktikan tidak ada rate limit).
7. **T3**: Set cookie `captcha_code=ABCD` (berbeda dari session), kirim `captcha=ABCD` pada kode asli → assert `eult_captcha_check()` mengembalikan `true` (gagal terhadap ekspektasi aman, membuktikan cookie bisa bypass).
8. **T4**: POST ke `rating()` dengan `nomorTiket` tebakan (tanpa kunci) pada kode asli → assert email `selesai()` terpicu (gagal, membuktikan tidak ada validasi kepemilikan).
9. **M1**: Assert response tidak mengandung header `Content-Security-Policy`/`X-Frame-Options` (mengonfirmasi M1 pada kode asli).
10. **M2**: Sebagai staf non-admin unit A, `GET ticketing/loadpdf/{namaFileUnitB}` pada kode asli → assert 200 (gagal, membuktikan tidak ada ownership check).
11. **M3**: 100 request enumerasi nomor tiket beruntun pada kode asli → assert seluruhnya diproses tanpa hambatan.
12. **R1 (audit)**: Render `detail_user.php` dengan `repliesMessage = '<script>alert(1)</script>'` pada kode SAAT INI → assert output SUDAH ter-escape (test ini diharapkan LULUS pada kode asli, mengonfirmasi temuan bahwa R1 untuk binding utama sudah tidak ada — berbeda dari 11 bug lain yang test eksploratifnya diharapkan GAGAL pada kode asli).

**Expected Counterexamples**:
- K1/K2/K4/T1/T2/T3/T4/M2/M3: seluruhnya diharapkan mendemonstrasikan bug persis seperti described di `bugfix.md` (sudah diverifikasi manual sebelumnya via curl/pembacaan kode).
- K3: kondisi produksi dengan kunci kosong/hardcode tidak menyebabkan kegagalan apa pun pada kode asli (mengonfirmasi tidak ada guard).
- R1: tidak ada counterexample yang berhasil untuk 3 binding utama pada kode saat ini — ini BUKAN kegagalan analisis, melainkan temuan valid yang harus dilaporkan (lihat Catatan Verifikasi R1).

### Fix Checking

**Goal**: Verifikasi bahwa untuk semua input di mana bug condition masing-masing bug bernilai true, fungsi yang telah diperbaiki menghasilkan expected behavior.

**Pseudocode:**
```
FOR EACH bug IN [K1, K2, K3, K4, T1, T2, T3, T4, M1, M2, M3, R1] DO
  FOR ALL input WHERE bug.isBugCondition(input) DO
    result := bug.fixedFunction(input)
    ASSERT bug.expectedBehavior(result)  // sesuai Property N terkait di atas
  END FOR
END FOR
```

### Preservation Checking

**Goal**: Verifikasi bahwa untuk semua input di mana bug condition TIDAK bernilai true, fungsi yang telah diperbaiki menghasilkan hasil yang sama dengan fungsi asli.

**Pseudocode:**
```
FOR EACH bug IN [K1, K2, K3, K4, T1, T2, T3, T4, M1, M2, M3, R1] DO
  FOR ALL input WHERE NOT bug.isBugCondition(input) DO
    ASSERT bug.originalFunction(input) = bug.fixedFunction(input)
  END FOR
END FOR
```

**Testing Approach**: Property-based testing digunakan untuk bug dengan domain input besar dan bersifat pure-function (K1: string SQLi arbitrer; T3: string cookie arbitrer; K2/T4: kunci terenkripsi arbitrer vs tidak match). Example-based/integration testing digunakan untuk bug dengan domain diskrit atau bergantung pada konfigurasi/environment/filter pipeline (K3, K4: enum 3 environment; T1: filter pipeline CI4; T2 rate-limit: threshold waktu; M1: header presence; M2: kombinasi staf-file terbatas; R1: audit statis).

**Test Plan**: Untuk setiap bug, observasi dulu perilaku pada kode UNFIXED untuk input non-buggy (mouse click, form valid, dsb — sudah tercakup pada test suite existing project jika ada, atau ditulis baru mengikuti perilaku yang diverifikasi manual), lalu tulis test yang menangkap perilaku tersebut agar tetap identik setelah fix.

**Test Cases**:
1. **K1 Preservation**: `nomorTiket` valid existing → hasil `rating()`/`savetiket()` identik sebelum-sesudah fix.
2. **K2/M2 Preservation**: Kunci sah/staf admin → file tetap terunduh, header tetap sama.
3. **K3 Preservation**: `ENVIRONMENT=development` dengan `.env` kosong → fallback tetap aktif tanpa warning.
4. **K4 Preservation**: `ENVIRONMENT=development` → toolbar tetap aktif penuh.
5. **T1 Preservation**: Request dengan token CSRF valid → respons JSON identik.
6. **T2/M3 Preservation**: Request dalam batas rate limit wajar → tidak terblokir; captcha benar dari gambar → tetap diterima.
7. **T3 Preservation**: Captcha benar sesuai session (tanpa cookie apa pun) → tetap lolos.
8. **T4 Preservation**: Kunci sah pemegang tiket sendiri → rating tersimpan, email terkirim.
9. **M1 Preservation**: Aset statis existing → tetap termuat setelah CSP aktif.
10. **R1 Preservation**: Teks balasan chat berisi karakter HTML wajar → tetap tampil benar visual (sudah terverifikasi berfungsi pada kode saat ini via `esc()` yang sudah terpasang).

### Unit Tests

- Test setiap fungsi model (`byId()`, `ambilSatu()`, `getByLastId()`) dengan kondisi array vs string untuk memastikan binding aman (K1).
- Test `Enkripsi::kunciLegacy()`/`decode()`/`encode()` per kombinasi `ENVIRONMENT` × isi `.env` (K3).
- Test `eult_captcha_check()` dengan kombinasi cookie ada/tidak ada × session ada/tidak ada × match/tidak match (T3).
- Test filter custom toolbar/throttle secara terisolasi (K4, T2, M3) dengan mock `ENVIRONMENT`/waktu.
- Test method `loadpdf()`/`loadattach()` dengan kunci valid/tidak valid/valid-tapi-tidak-match (K2, T4, M2).

### Property-Based Tests

- **K1**: Generate string arbitrer (termasuk metacharacter SQL, unicode, string kosong) sebagai `nomorTiket`/`idTiket`, assert tidak pernah mengembalikan >1 baris untuk pencarian exact-match dan tidak pernah SQL error.
- **T3**: Generate string arbitrer sebagai nilai cookie `captcha_code` (independen dari session), assert hasil `eult_captcha_check()` selalu identik dengan hasil yang hanya mempertimbangkan session.
- **K2/T4**: Generate kunci terenkripsi hasil `encode()` dari nomor tiket acak (baik yang valid ada di DB maupun tidak), serta kunci acak yang tidak valid sama sekali, assert akses file/rating hanya berhasil pada kombinasi kunci-valid-dan-match, gagal (403/404/no-op) untuk seluruh kombinasi lainnya.

### Integration Tests

- **T1**: Full flow submit form buat tiket dengan token CSRF valid → sukses; tanpa token → ditolak graceful.
- **T2**: Full flow captcha gambar (generate → tampil → submit benar → sukses; submit salah → gagal) + rate limit (N request sukses, N+1 kena 429).
- **K4**: Deploy simulasi 3 environment (`development`/`testing`/`production`), assert endpoint toolbar hanya merespons data debug pada `development`.
- **M2**: Full flow staf login sebagai grup non-admin unit A, coba akses file unit B → 403; staf admin → 200.
- **R1**: Render halaman penuh dengan payload XSS pada seluruh binding yang teridentifikasi saat audit → assert tidak ada tag `<script>` yang lolos tanpa escaping ke output final.
