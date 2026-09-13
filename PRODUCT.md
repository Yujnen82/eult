# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Sivitas Akademika Universitas Mulawarman (UNMUL) & Publik Eksternal**: Mahasiswa, dosen, tenaga kependidikan, serta masyarakat umum yang memerlukan pengajuan permohonan layanan administratif, surat keterangan, legalisir, atau layanan resmi universitas secara daring tanpa antre fisik di loket.
- **Petugas Loket / Operator ULT**: Menerima pengajuan tiket masuk, memverifikasi kelengkapan berkas administratif awal, dan mendisposisikan tiket ke unit kerja/fakultas yang berwenang.
- **Verifikator Unit & Pejabat Berwenang**: Memeriksa substansi persyaratan, memvalidasi berkas, memproses draf surat balasan/dokumen resmi, melakukan persetujuan bertingkat, dan menerbitkan surat final ber-QR code.
- **Administrator Sistem**: Mengelola konfigurasi modul, master data layanan, referensi syarat dan jenis surat, hierarki unit kerja, akun pengguna, dan pembagian hak akses bertingkat.

## Product Purpose

E-ULT v2 (Elektronik Unit Layanan Terpadu v2) Universitas Mulawarman berfungsi sebagai portal layanan terpadu satu pintu (one-stop digital service) yang memfasilitasi seluruh siklus permohonan layanan publik di UNMUL secara transparan, akuntabel, dan terukur. 

Keberhasilan produk dicapai saat:
1. Pemohon dapat mengajukan layanan, memantau status secara real-time, dan menerima dokumen sah secara digital.
2. Petugas dan verifikator di berbagai unit kerja dapat berkolaborasi memproses tiket tanpa penumpukan berkas fisik dengan alur audit yang rapi.
3. Dokumen resmi yang dihasilkan dapat diverifikasi keasliannya secara publik melalui QR code.
4. Kualitas layanan dapat dievaluasi secara kontinu melalui survei kepuasan (rating & ulasan).

## Positioning

Portal layanan terpadu institusi pendidikan tinggi yang mengintegrasikan alur ticketing layanan publik, validasi integritas dan karantina berkas digital, verifikasi identitas sivitas akademika, penerbitan dokumen resmi ber-QR code validasi publik instan, serta survei kepuasan pemohon dalam satu platform web yang kohesif.

## Operating Context

- Dioperasikan melalui peramban web modern (desktop untuk petugas internal dan responsif mobile/desktop untuk pemohon publik).
- Alur kerja utama:
  1. Pengajuan tiket publik mandiri beserta unggah dokumen persyaratan dan validasi captcha.
  2. Pelacakan tiket melalui nomor tiket unik dan tautan terenkripsi yang dikirimkan ke email pemohon.
  3. Disposisi tiket oleh operator ULT ke unit kerja yang dituju.
  4. Pemeriksaan berkas dan pengelolaan keamanan dokumen melalui modul validasi/karantina file.
  5. Pembuatan dan penyusunan draf surat resmi menggunakan template dokumen dan generator PDF (mPDF/DomPDF).
  6. Penerbitan surat final dengan penyematan QR code keabsahan dokumen untuk verifikasi publik (`/validitas/(:any)`).
  7. Pengisian survei kepuasan pemohon (rating bintang dan umpan balik) setelah tiket terselesaikan.

## Capabilities and Constraints

- **Framework & Runtime**: CodeIgniter 4 (PHP ^8.2) dengan arsitektur MVC.
- **Sistem UI & Template**: Metronic Admin Template v6 (Twitter Bootstrap 4 & KeenThemes KT framework) yang telah terpasang di proyek (`public/assets/` dan layout view di `app/Views/layouts/`).
- **Generator Dokumen & QR**: mPDF 8.3, mPDF QRCode 1.2, dan DomPDF 3.1 untuk rendering dokumen dinamis dan tanda terima.
- **Keamanan & Karantina Berkas**: Modul `Validasifile` dengan kemampuan scan manifest, karantina berkas mencurigakan, dan restore file aman.
- **Otorisasi & Filter Akses**: Penjagaan sesi dengan filter `auth` serta pemisahan peran berdasarkan unit dan modul (`susrSgroupNama`).
- **Komitmen Desain**: Menggunakan template Metronic yang sudah terpasang di proyek tanpa perombakan struktur stylesheet inti.

## Brand Commitments

- Menggunakan identitas resmi Universitas Mulawarman (UNMUL), termasuk favicon (`favicon_unmul.ico`) dan penamaan instansi.
- Mempertahankan konsistensi penuh terhadap kelas, tata letak, dan komponen Metronic Bootstrap 4 (`kt-*`, layout skins header/aside/brand, plugin bundles).

## Evidence on Hand

- **Tata Letak & View Inti**: `app/Views/layouts/template.php`, `header.php`, `sidebar.php`, `subheader.php`, `footer.php`, `login.php`.
- **Aset Bundel Metronic**: `public/assets/css/style.bundle.css`, `public/assets/plugins/global/plugins.bundle.css`, `public/assets/media/logos/favicon_unmul.ico`.
- **Routing & Pengendali**: `app/Config/Routes.php`, `app/Controllers/Login.php`, `app/Controllers/Ticketing.php`, `app/Controllers/Validasifile.php`, `app/Controllers/Home.php`.
- **Pustaka Pendukung**: `app/Libraries/Enkripsi.php`, `app/Libraries/OsmClient.php`, `app/Libraries/PengirimEmail.php`.

## Product Principles

- **Transparansi & Kemudahan Akses**: Pemohon dapat melacak kemajuan berkas permohonannya secara mandiri kapan saja tanpa hambatan birokrasi loket fisik.
- **Keabsahan & Integritas Dokumen**: Setiap surat dan dokumen resmi yang diterbitkan memiliki validasi keaslian digital berbasis QR code publik yang aman.
- **Keamanan Berkas Terjamin**: Seluruh berkas lampiran yang diunggah harus dapat diperiksa dan diamankan melalui mekanisme karantina file untuk melindungi sistem.
- **Konsistensi Tampilan Terpadu**: Setiap modul dan halaman baru wajib mengikuti standar estetika dan komponen interaktif dari template Metronic yang sudah ada di proyek.

## Accessibility & Inclusion

- Antarmuka formulir publik dirancang jelas, informatif, dan responsif agar mudah digunakan oleh berbagai kalangan sivitas akademika maupun masyarakat umum di perangkat seluler dan desktop.
- Navigasi internal admin dilengkapi label status yang kontras dan struktur tabel data yang mudah dibaca oleh petugas operasional.
