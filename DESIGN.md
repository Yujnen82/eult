---
name: E-ULT v2 Metronic Design System
description: Sistem desain antarmuka birokrasi kampus modern, tertib, dan transparan untuk Elektronik Unit Layanan Terpadu Universitas Mulawarman.
colors:
  primary: "#5d78ff"
  primary-hover: "#3758ff"
  primary-active: "#2a4eff"
  secondary: "#e2e5ec"
  secondary-hover: "#f4f5f8"
  brand-dark: "#1a1a27"
  header-dark: "#1e1e2d"
  success: "#0abb87"
  info: "#5578eb"
  warning: "#ffb822"
  danger: "#fd397a"
  text-primary: "#48465b"
  text-body: "#646c9a"
  text-muted: "#74788d"
  border-base: "#ebedf2"
  border-input: "#e2e5ec"
  bg-body: "#f2f3f8"
  bg-card: "#ffffff"
typography:
  display:
    fontFamily: "Poppins, sans-serif"
    fontSize: "2rem"
    fontWeight: 600
    lineHeight: 1.2
  headline:
    fontFamily: "Poppins, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 500
    lineHeight: 1.3
  title:
    fontFamily: "Poppins, sans-serif"
    fontSize: "1.2rem"
    fontWeight: 500
    lineHeight: 1.4
  subtitle:
    fontFamily: "Poppins, sans-serif"
    fontSize: "1rem"
    fontWeight: 500
    lineHeight: 1.3
  input-mobile:
    fontFamily: "Poppins, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.5
  body:
    fontFamily: "Poppins, sans-serif"
    fontSize: "13px"
    fontWeight: 300
    lineHeight: 1.5
  label:
    fontFamily: "Poppins, sans-serif"
    fontSize: "12px"
    fontWeight: 500
    lineHeight: 1.2
  caption:
    fontFamily: "Poppins, sans-serif"
    fontSize: "11px"
    fontWeight: 400
    lineHeight: 1.3
  micro:
    fontFamily: "Poppins, sans-serif"
    fontSize: "10px"
    fontWeight: 400
    lineHeight: 1.2
rounded:
  sm: "2px"
  md: "4px"
  lg: "8px"
  xl: "12px"
  pill: "2rem"
spacing:
  xs: "5px"
  sm: "10px"
  md: "15px"
  lg: "20px"
  xl: "25px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "#ffffff"
    rounded: "{rounded.md}"
    padding: "0.65rem 1.25rem"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
    textColor: "#ffffff"
    rounded: "{rounded.md}"
    padding: "0.65rem 1.25rem"
  button-secondary:
    backgroundColor: "{colors.secondary-hover}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.md}"
    padding: "0.65rem 1.25rem"
  card-portlet:
    backgroundColor: "{colors.bg-card}"
    textColor: "{colors.text-body}"
    rounded: "{rounded.md}"
    padding: "25px"
  input-base:
    backgroundColor: "#ffffff"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.md}"
    padding: "0.65rem 1rem"
---

# Design System: E-ULT v2 Metronic

## Overview

**Creative North Star: "The Civic Academic Registry"**

Sistem desain E-ULT v2 memadukan ketertiban birokrasi universitas dengan kejelasan interaksi digital modern. Setiap bidang visual dirancang untuk memancarkan kepastian, kewibawaan institusional, dan transparansi bagi seluruh sivitas akademika Universitas Mulawarman serta masyarakat umum. Antarmuka tidak mengalihkan perhatian dengan ornamen dekoratif, melainkan mengarahkan fokus pengguna pada status berkas, instruksi syarat yang jelas, dan alur permohonan yang terukur.

Fondasi visual dibangun di atas tema Metronic v6 (Bootstrap 4 & KT Framework) dengan tata kelola panel portlet yang rapi, garis sekat 1px yang presisi, dan skema kontras bertingkat antara kanvas abu-abu lembut (#f2f3f8) dan permukaan portlet putih bersih (#ffffff). Setiap komponen antarmuka dirancang untuk operasional data yang intensif tanpa menimbulkan kelelahan kognitif bagi petugas operator maupun pemohon publik.

Anti-referensi visual: Hindari gradien warna neon mencolok, bayangan melayang (dramatic 3D drop-shadows), animasi berlebih yang memperlambat pemrosesan berkas, dan sudut tumpul ekstrem yang mengaburkan kesan formal institusi kampus.

**Key Characteristics:**
- **Struktur Portlet Bersekat Presisi**: Konten dikelompokkan ke dalam portlet mandiri dengan header dan footer bergaris batas halus.
- **Keterbacaan Tipografi Ringan (Light & Crisp)**: Teks bodi Poppins bobot 300 pada 13px memberikan kerapatan data yang nyaman dibaca.
- **Semantik Status Fungsional**: Warna aksen digunakan secara selektif untuk mengindikasikan status tiket, verifikasi berkas, dan aksi utama.
- **Elevasi Ambien Bersih**: Mengandalkan kontras permukaan datar bertingkat dengan bayangan ambien sangat halus.

## Colors

Palet warna menggabungkan rona biru formal institusional dengan warna status semantik tegas yang bersumber dari tema aktif Metronic.

### Primary
- **Royal Civic Cobalt** (#5d78ff): Aksen utama untuk tombol aksi prioritas tinggi, tautan navigasi aktif, dan penanda fokus interaktif.
- **Midnight Governance Navy** (#1a1a27 / #1e1e2d): Warna struktural untuk bilah brand atas, aside menu navigasi gelap, dan panel penunjang institusional.

### Secondary
- **Cadet Slate Border** (#e2e5ec / #ebedf2): Garis sekat header portlet, batas kolom tabel, dan garis tepi elemen form kontrol.

### Functional Roles
- **Mint Civic Success** (#0abb87): Menandakan berkas tervalidasi, tiket selesai, atau operasi sistem berhasil.
- **Sky Clear Info** (#5578eb): Digunakan untuk tiket dalam proses, status disposisi, dan informasi petunjuk layanan.
- **Institutional Amber** (#ffb822): Mengindikasikan berkas menunggu verifikasi, butuh tindakan pengguna, atau peringatan tenggat waktu.
- **Ruby Civic Alert** (#fd397a): Status berkas ditolak, pembatalan, berkas dikarantina, atau validasi gagal.

### Neutral
- **Pristine White Surface** (#ffffff): Latar belakang utama portlet, kartu formulir, dan modal pop-up.
- **Canvas Muted Soft** (#f2f3f8): Latar kanvas dasar seluruh aplikasi yang memberikan kontras nyaman terhadap portlet putih.
- **Cadet Steel Text** (#646c9a): Warna teks bodi standar yang lembut dan tidak melelahkan mata saat membaca rincian berkas.
- **Charcoal Heading** (#48465b): Warna judul portlet, heading tabel, dan label data yang memerlukan ketegasan visual.
- **Ash Subdued** (#74788d): Warna keterangan sekunder, tanggal riwayat, dan placeholder input.

### Named Rules
**The High-Contrast Civic Action Rule.** Warna Royal Civic Cobalt (#5d78ff) diprioritaskan hanya pada titik interaksi yang membutuhkan keputusan pengguna (tombol submit, unduh berkas, tautan tiket aktif). Warna ini tidak boleh digunakan sebagai latar blok konten berukuran besar.

**The Functional Status Rule.** Warna mint success (#0abb87), amber warning (#ffb822), dan ruby danger (#fd397a) adalah warna fungsional eksklusif untuk siklus hidup tiket dan integritas berkas. Jangan menggunakan warna status ini untuk keperluan dekoratif murni.

## Typography

**Display Font:** Poppins (dengan cadangan Helvetica, Arial, sans-serif)  
**Body Font:** Poppins (dengan cadangan sans-serif)  
**Data/Monospace Font:** Roboto / monospace (untuk nomor tiket, kode enkripsi berkas, dan nomor surat)

**Character:** Tipografi formal-modern dengan keseimbangan antara keanggunan geometris Poppins dan keterbacaan data yang tinggi dalam layout tabular.

### Hierarchy
- **Display** (weight: 600, size: 2rem (32px), line-height: 1.2): Judul halaman landing publik dan ringkasan besar layanan.
- **Headline** (weight: 500, size: 1.5rem (24px), line-height: 1.3): Judul modul utama pada bilah subheader.
- **Title** (weight: 500, size: 1.2rem (19px), line-height: 1.4): Judul portlet card (`.kt-portlet__head-title`) dan judul modal dialog.
- **Body** (weight: 300, size: 13px (0.8125rem), line-height: 1.5): Teks konten, baris data tabel, dan instruksi formulir.
- **Label** (weight: 500, size: 12px (0.75rem), line-height: 1.2): Label formulir, badge status, dan header kolom tabel.

### Named Rules
**The Weight For Hierarchy Rule.** Pemisahan hirarki teks dilakukan terutama melalui perbedaan bobot (font-weight 300 untuk bodi, 500 untuk judul dan label) daripada lonjakan ukuran font yang ekstrem, menjaga kerapian dokumen administratif.

## Layout

Sistem tata letak mengadopsi model grid 12-kolom Bootstrap 4 yang dibungkus dalam arsitektur layout Metronic:
- **Aside Navigation Sidebar**: Lebar standar 255px pada desktop (dapat diciutkan menjadi 70px ikon-only melalui toggler).
- **Header Topbar**: Ketinggian tetap 65px pada desktop (50px pada mobile) dengan skema dark (`#1e1e2d`).
- **Subheader Bar**: Ketinggian fleksibel 54px dengan penempatan judul halaman di kiri dan grup tombol aksi cepat di kanan.
- **Kontainer Konten**: Padding horizontal 25px dengan batas kanvas terpusat atau fluid (`container-fluid`).
- **Rhythm Spasi Vertikal**: Jarak antar baris portlet card terkunci pada 20px (`margin-bottom: 20px`), menciptakan ritme visual yang konsisten.

## Elevation & Depth

Sistem desain menerapkan pendekatan "Subtle Ambient Layering" (kedalaman ambien halus) yang menjaga antarmuka tetap bersih, profesional, dan berorientasi data tanpa distraksi elemen 3D dramatis.

### Shadow Vocabulary
- **Ambient Card** (`box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05)`): Bayangan standar untuk seluruh portlet card dan panel konten putih.
- **Elevated Aside** (`box-shadow: 0px 0px 28px 0px rgba(82, 63, 105, 0.08)`): Batas elevasi aside menu samping terhadap kanvas konten.
- **Floating Dialog** (`box-shadow: 0px 0px 50px 0px rgba(82, 63, 105, 0.15)`): Bayangan untuk modal formulir, dropdown navigasi, dan popover detail berkas.

### Named Rules
**The Flat-Surface Priority Rule.** Seluruh permukaan konten pada keadaan diam bersifat datar dengan garis batas halus. Bayangan hanya hadir sebagai penanda pemisahan bidang fungsional bertingkat.

## Shapes

- **Radius Standar (4px)**: Diterapkan secara konsisten pada kartu portlet (`border-radius: 4px`), kotak input (`.form-control`), tombol standar (`.btn`), dan dropdown container.
- **Radius Pill (2rem)**: Diterapkan pada badge status (`.kt-badge--pill`), filter tag pencarian, dan tombol pill khusus.
- **Radius Lingkaran (50%)**: Diterapkan khusus untuk avatar pengguna (`.kt-badge` avatar) dan indikator status titik (`.kt-badge--dot`).
- **Borders**: Garis solid 1px dengan rona netral lembut (`#ebedf2` untuk sekat header/footer portlet dan `#e2e5ec` untuk batas input).

## Components

### Buttons
- **Shape**: Sudut melengkung halus (radius 4px, atau radius pill 2rem untuk tombol opsi).
- **Primary Action** (`.btn-brand`): Latar Royal Civic Cobalt (#5d78ff), teks putih, padding 0.65rem 1.25rem.
- **Hover / Focus**: Latar menggelap menjadi #3758ff dengan bayangan fokus tipis `rgba(117, 140, 255, 0.5)`.
- **Secondary / Light** (`.btn-secondary`): Latar netral #f4f5f8 dengan garis batas #e2e5ec dan teks #595d6e.
- **Unified Style** (`.btn-label-*`): Latar transparan berwarna dengan opasitas 10% dan teks berwarna solid fungsional.

### Cards / Portlets
- **Corner Style**: Sudut presisi (radius 4px).
- **Background**: Putih bersih (#ffffff).
- **Shadow Strategy**: Menggunakan Ambient Card shadow (`0px 0px 13px 0px rgba(82, 63, 105, 0.05)`).
- **Header & Footer**: Header dengan tinggi minimum 60px dipisahkan oleh garis batas 1px (#ebedf2). Footer dengan padding 25px dipisahkan garis atas 1px.
- **Internal Padding**: Padding standar 25px pada area bodi (`.kt-portlet__body`).

### Inputs / Fields
- **Style**: Garis tepi 1px solid #e2e5ec, latar belakang putih (#ffffff), teks #495057, radius 4px, tinggi calc(1.5em + 1.3rem + 2px).
- **Focus**: Garis tepi beralih menjadi #9aabff dengan bayangan fokus halus `rgba(88, 103, 221, 0.25)`.
- **Placeholder**: Teks netral abu-abu (#74788d).
- **Error State**: Garis tepi merah ruby (#fd397a) dengan teks keterangan validasi di bawah kolom input.

### Badges / Chips
- **Inline Badges** (`.kt-badge--inline`): Digunakan untuk status tiket (Baru, Diproses, Ditolak, Selesai). Padding 0.75rem, teks bobot 500.
- **Unified Badges**: Latar transparan rona 10% (misal `rgba(10, 187, 135, 0.1)` untuk success) dengan teks kontras tinggi untuk kenyamanan visual saat pemindaian data.

### Navigation
- **Aside Navigation**: Latar dark `#1a1a27` atau light `#ffffff` dengan item menu ber-padding nyaman. Item aktif ditandai latar pembeda `#282f48` dan ikon aksen `#5d78ff`.

### Signature Component: Ticket Status & Verification Card
Portlet khusus yang menampilkan nomor tiket besar ber-font monospace/roboto, lencana status dengan palet unified, alur verifikasi berkas, dan pratinjau stempel QR code keabsahan dokumen.

## Do's and Don'ts

### Do:
- **Do** gunakan portlet card standar Metronic (`.kt-portlet`) untuk membungkus setiap modul data, formulir, atau tabel riwayat.
- **Do** gunakan kelas badge unified (`.kt-badge--unified-success`, dll.) untuk label status tiket agar tabel data tetap bersih dan mudah dipindai.
- **Do** pertahankan padding 25px di dalam bodi portlet untuk memastikan kenyamanan ruang pembacaan dokumen.
- **Do** sertakan konfirmasi visual status aksi (alert toast atau pesan interaktif) setiap kali berkas diunggah, disimpan, atau didisposisikan.
- **Do** terapkan validasi formulir yang jelas pada kolom input dengan garis tepi merah `#fd397a` dan pesan kesalahan di bawah kolom terkait.

### Don't:
- **Don't** memasukkan library stylesheet tambahan di luar bundel Metronic yang dapat merusak kesinambungan styling komponen aktif.
- **Don't** menggunakan sudut melengkung ekstrem (>8px) pada kartu portlet atau kotak input utama.
- **Don't** mewarnai latar belakang tabel dengan warna-warna terang yang menyilaukan; gunakan baris belang netral (`table-striped`) jika diperlukan.
- **Don't** menghapus garis sekat kepala portlet (`.kt-portlet__head`) pada halaman formulir administrasi berjenjang.
- **Don't** menggunakan font dekoratif atau script playful yang bertentangan dengan kredibilitas institusi kampus.
