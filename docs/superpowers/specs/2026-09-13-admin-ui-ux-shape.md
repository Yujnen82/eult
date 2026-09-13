# Design Brief: Penataan UI/UX Seluruh Halaman Admin E-ULT v2

**Status:** Confirmed  
**Date:** 2026-09-13  
**Creative North Star:** "The Civic Academic Registry" (DESIGN.md)

---

## 1. Job & Audience (Tugas & Pengguna)

- **Visitor Mode:** **Operate** (Berorientasi pada kecepatan pemindaian data, efisiensi eksekusi tugas, kejelasan status berkas, dan minim distraksi kognitif).
- **Target Pengguna:**
  1. **Operator Loket / Admin ULT:** Menerima tiket permohonan masuk dari publik, memeriksa kelengkapan berkas awal, dan mendisposisikan tiket ke unit/fakultas yang dituju.
  2. **Verifikator Unit & Pejabat Penandatangan:** Memeriksa substansi persyaratan teknis, memvalidasi lampiran, menyetujui/menolak tiket, menyusun draf surat balasan resmi, serta menerbitkan surat akhir ber-QR code sah.
  3. **Administrator Sistem:** Memantau kesehatan operasional layanan, mengawasi modul keamanan/karantina berkas lampiran, serta mengonfigurasi master data layanan dan hak akses berjenjang (RBAC).

---

## 2. Outcome & Proof (Hasil & Bukti Keberhasilan)

- **Hasil Utama:** Alur kerja harian pemrosesan permohonan dari tiket masuk hingga penerbitan dokumen sah menjadi cepat, jelas, dan seragam di seluruh modul administrasi tanpa kebingungan navigasi.
- **Bukti Keberhasilan Spesifik:**
  - Halaman Dashboard (`/home`) yang sebelumnya kosong bertransformasi menjadi **Pusat Komando Operasional Hibrida** (KPI metrik tiket, peringatan tindakan mendesak, tren mingguan, dan antrean prioritas).
  - Antrean Tiketing (`/ticketing`) dilengkapi *quick-filter tab status* (Semua, Baru, Disposisi, Verifikasi, Selesai), filter tanggal & layanan yang lebih terintegrasi, serta tabel data yang padat dan mudah dipindai (*scannable*).
  - Halaman Detail Tiket (`/ticketing/detail`) memiliki tata letak kartu portlet terstruktur rapi: identitas pemohon, dokumen persyaratan dengan penanda status verifikasi, riwayat disposisi bertingkat, dan panel aksi terpadu.
  - Modul Validasi File (`/validasifile`) dan seluruh modul Master Data & Hak Akses (RBAC) menerapkan standar visual tabel, formulir modal, dan notifikasi yang konsisten.

---

## 3. Selected Direction (Arah Desain Terpilih)

- **Visual Authority:** *The Civic Academic Registry* — nuansa formal birokrasi kampus modern, tertib, dan transparan:
  - Palet warna institusional: Royal Civic Cobalt (`#5d78ff`), Midnight Governance Navy (`#1a1a27` / `#1e1e2d`), dan latar kanvas Canvas Muted Soft (`#f2f3f8`).
  - Semantik status fungsional: Mint Civic Success (`#0abb87`), Institutional Amber (`#ffb822`), dan Ruby Alert (`#fd397a`).
  - Tipografi: Poppins dengan hirarki berbasis bobot (300 untuk bodi/tabel, 500 untuk heading dan label) serta Roboto/monospace untuk nomor tiket dan kode dokumen.
- **Tesis Interaksi & Struktur:**
  - *Scannable Data Density*: Informasi disajikan padat namun lega dengan padding bodi portlet konsisten 25px dan sekat halus 1px (`#ebedf2`).
  - *Unified Status Badges*: Status tiket dan integritas berkas menggunakan lencana semantik berkontras tinggi (latar 10% opacity dengan teks solid berbobot 500) agar mudah dipindai sekilas.
  - *Focused Action Hierarchy*: Aksi utama (Disposisi, Verifikasi, Terbitkan Surat) diberi penekanan primer (`.btn-brand`), aksi sekunder menggunakan `.btn-secondary` atau `.btn-label-*`, dan aksi destruktif (Tolak, Hapus) menggunakan `.btn-danger` dengan konfirmasi aman.

---

## 4. Scope & Boundaries (Cakupan & Batasan)

- **Halaman yang Ditata:**
  - **Tier 1 (Inti Operasional):**
    - Dashboard Utama (`/home`)
    - Antrean & Filter Tiket (`/ticketing`)
    - Detail, Verifikasi & Disposisi Tiket (`/ticketing/detail/*`)
    - Form Pembuatan Tiket Internal (`/ticketing/create`)
  - **Tier 2 (Keamanan & Audit):**
    - Validasi & Karantina Berkas Digital (`/validasifile`)
    - Laporan & Rekapitulasi Kepuasan IKM (`/laporan`, `/laporanlayanan`)
  - **Tier 3 (Master Data & RBAC):**
    - Master Referensi Kategori/Layanan (`/refkategori`), Format Surat (`/refsurat`), Persyaratan Berkas (`/refsyarat`), dan Unit Kerja (`/unit`)
    - Manajemen Pengguna (`/pengguna`) dan Modul/Hak Akses Berjenjang (`/hakakses*`)
- **Batasan & Hal yang Tidak Berubah (Untouched):**
  - Arsitektur backend CodeIgniter 4, routing, logika enkripsi ID tiket, dan generator PDF (mPDF/DomPDF) tetap utuh.
  - Mengoptimalkan kelas dan komponen bundel Metronic v6 yang sudah terpasang di `public/assets/` tanpa menambahkan dependensi CSS/JS eksternal berat yang memicu konflik aset.
- **Anti-Goals:**
  - Tidak mengubah skema database atau struktur otorisasi hak akses yang ada.
  - Tidak mengubah antarmuka menjadi SPA (React/Vue), melainkan memaksimalkan rendering view server-side CodeIgniter 4 + AJAX yang responsif.

---

## 5. States & Ranges (Keadaan Antarmuka & Rentang Data)

- **Empty State:** Desain state kosong yang ramah dan informatif dengan ikon netral ketika tidak ada antrean tiket atau hasil pencarian tidak ditemukan.
- **Loading State:** Indikator pemuatan halus (*spinner* bawaan Metronic) pada tabel saat memuat filter atau data AJAX.
- **Feedback & Error State:** Validasi input formulir inline dengan garis batas merah `#fd397a` dan keterangan jelas; umpan balik aksi (sukses/gagal) menggunakan notifikasi *alert/toast* yang konsisten.
- **Rentang Data:** Optimal untuk volume 0 hingga ratusan tiket per hari dengan penomoran halaman (*pagination*) dinamis.

---

## 6. Interaction & Layout (Tata Letak & Interaksi Spesifik)

1. **Dashboard Utama (`/home`):**
   - Baris 1: 4 KPI Cards (Total Tiket Masuk, Perlu Tindakan Unit Ini, Tiket Dalam Proses, Tiket Selesai / Rata-rata IKM).
   - Baris 2: Portlet Grafik Tren Mingguan/Bulanan & Portlet Distribusi Layanan per Kategori.
   - Baris 3: Tabel Cepat "Tiket Memerlukan Tindakan Segera" dengan tautan langsung ke detail tiket.
2. **Halaman Tiketing (`/ticketing`):**
   - Quick-filter tab status di bagian atas portlet (Semua, Baru, Disposisi, Verifikasi, Selesai).
   - Toolbar filter terpadu: Rentang tanggal (daterangepicker), dropdown kategori layanan (select2), dan pencarian instan nomor tiket/nama pemohon.
   - Tabel interaktif dengan avatar pemohon, nomor tiket berfont monospace, badge status berwarna, dan dropdown aksi terpadu.
3. **Halaman Detail Tiket (`/ticketing/detail`):**
   - Tata letak dua kolom: Kolom kiri untuk rincian data pemohon & daftar berkas persyaratan (beserta tombol pratinjau cepat dan verifikasi berkas); Kolom kanan untuk linimasa riwayat disposisi/tracking, formulir balasan pesan pemohon, dan generator draf surat bertanda tangan QR code.
4. **Halaman Validasi File (`/validasifile`):**
   - Metrik status integritas (Total File Dipindai, Berkas Aman, Berkas Dikarantina).
   - Tabel manifest file interaktif dengan badge status keamanan dan modal pratinjau/restore.
5. **Halaman Master Data & RBAC:**
   - Tabel data seragam dengan tombol tambah di subheader/portlet-head, pencarian instan, modal form standar dengan validasi input yang rapi.

---

## 7. Constraints & Open Decisions

- **Kompatibilitas:** Dioptimalkan untuk desktop dan laptop (resolusi ≥ 1024px) dengan adaptasi responsif pada tablet dan mobile.
- **Bahasa Antarmuka:** Seluruh label formulir, status tiket, petunjuk pengisian, dan pesan notifikasi menggunakan Bahasa Indonesia yang baku dan formal.
- **Ikonografi:** Menggunakan pustaka ikon bawaan Metronic (SVG icons & Flaticon v2).
