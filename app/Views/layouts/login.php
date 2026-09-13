<!DOCTYPE html>
<html lang="id">

<!-- begin::Head -->
<head>
    <meta charset="utf-8" />
    <title>E-ULT | Unit Layanan Terpadu Universitas Mulawarman</title>
    <meta name="description" content="Portal Elektronik Unit Layanan Terpadu (E-ULT) Universitas Mulawarman - Layanan pengajuan tiket, pelacakan dokumen mandiri, dan verifikasi keabsahan surat resmi.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">

    <!--begin::Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap">
    <!--end::Fonts -->

    <!--begin::Global Theme Styles(used by all pages) -->
    <link href="<?= base_url(); ?>assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <!--end::Global Theme Styles -->

    <!--begin::Layout Skins(used by all pages) -->
    <link href="<?= base_url(); ?>assets/css/skins/header/base/light.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/skins/header/menu/light.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/skins/brand/dark.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/skins/aside/dark.css" rel="stylesheet" type="text/css" />
    <!--end::Layout Skins -->

    <link rel="shortcut icon" href="<?= base_url(); ?>assets/media/logos/favicon_unmul.ico" />

    <style>
        /* ==========================================================================
           E-ULT v2 Civic Academic Registry - Surface Styling & Adaptive Layout
           ========================================================================== */
        
        /* Tema seleksi, kursor, dan scrollbar browser */
        ::selection {
            background: rgba(93, 120, 255, 0.25);
            color: #1e1e2d;
        }

        /* Custom Scrollbars Bertema Civic Cobalt */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f2f3f8;
        }
        ::-webkit-scrollbar-thumb {
            background: #e2e5ec;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #74788d;
        }
        * {
            scrollbar-width: thin;
            scrollbar-color: #e2e5ec #f2f3f8;
        }

        /* Focus rings yang aksesibel & jelas */
        a:focus-visible,
        button:focus-visible,
        .eult-nav-btn:focus-visible,
        .btn:focus-visible {
            outline: 2px solid #5d78ff;
            outline-offset: 2px;
        }

        ::selection {
            background-color: rgba(93, 120, 255, 0.18);
            color: #1e1e2d;
        }

        html {
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        *, *::before, *::after {
            box-sizing: inherit;
        }

        body {
            background-color: #f4f6fa;
            background-image: url('<?= base_url(); ?>assets/media/bg/bg-3.jpg');
            background-repeat: no-repeat;
            background-position: center top;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            color: #646c9a;
            caret-color: #5d78ff;
            overflow-x: hidden;
            width: 100%;
        }

        /* Top Navbar Bersih & Adaptif */
        .eult-navbar {
            background: #ffffff;
            padding: 1rem 0;
            border-bottom: 1px solid #ebedf2;
            box-shadow: 0 2px 10px 0 rgba(82, 63, 105, 0.05);
            position: relative;
            z-index: 100;
        }

        .eult-brand-logo {
            height: 46px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,0.08));
        }

        .eult-brand-title {
            color: #1e1e2d;
            font-size: 1.2rem;
            font-weight: 600;
            line-height: 1.25;
            margin: 0;
            letter-spacing: 0.01em;
        }

        .eult-brand-subtitle {
            color: #74788d;
            font-size: 12px;
            font-weight: 400;
            margin: 0;
        }

        /* Hero Section Adaptif & Terukur */
        .eult-hero-section {
            padding: 2.75rem 0 1.25rem 0;
            text-align: center;
        }

        .eult-hero-title {
            color: #1e1e2d;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.65rem;
            letter-spacing: -0.02em;
            line-height: 1.25;
        }

        .eult-hero-desc {
            color: #646c9a;
            font-size: 13px;
            font-weight: 400;
            max-width: 620px;
            margin: 0 auto;
            line-height: 1.6;
            text-wrap: balance;
        }

        /* Navigasi Tab Segmented Terang & Responsif */
        .eult-nav-tabs-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .eult-nav-tabs {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            padding: 6px;
            background: #ffffff;
            border: 1px solid #ebedf2;
            border-radius: 2rem;
            box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);
            flex-wrap: wrap;
        }

        .eult-nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.65rem 1.4rem;
            background: transparent;
            color: #646c9a;
            border: 1px solid transparent;
            border-radius: 2rem;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none !important;
            min-height: 42px;
        }

        .eult-nav-btn i {
            margin-right: 8px;
            font-size: 13px;
            color: #74788d;
            transition: transform 0.2s, color 0.2s;
        }

        .eult-nav-btn:hover {
            background: #f4f5f8;
            color: #5d78ff;
        }

        .eult-nav-btn:hover i {
            color: #5d78ff;
        }

        .eult-nav-btn.active {
            background: #5d78ff;
            color: #ffffff;
            border-color: #5d78ff;
            box-shadow: 0 4px 14px rgba(93, 120, 255, 0.35);
        }

        .eult-nav-btn.active i {
            color: #ffffff;
            transform: scale(1.08);
        }

        /* Kontainer Utama & Portlet Card */
        .eult-card-container {
            width: 100%;
            max-width: 1040px;
            margin: 1.5rem auto 3rem auto;
            padding: 0 15px;
            position: relative;
            z-index: 10;
        }

        .eult-portlet {
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);
            border: 1px solid #ebedf2;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
            width: 100%;
        }

        .eult-portlet:hover {
            box-shadow: 0px 0px 28px 0px rgba(82, 63, 105, 0.08);
        }

        .eult-portlet-head {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #ebedf2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafbfe;
        }

        .eult-portlet-head-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e1e2d;
            display: flex;
            align-items: center;
            letter-spacing: -0.015em;
            line-height: 1.3;
        }

        .eult-portlet-head-title i {
            font-size: 1.2rem;
            margin-right: 12px;
            color: #5d78ff;
        }

        .eult-portlet-body {
            padding: 2.2rem 2rem;
        }

        .eult-portlet-foot {
            padding: 1.25rem 2rem;
            background: #fafbfe;
            border-top: 1px solid #ebedf2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Form Controls, Labels, dan Inputs */
        .eult-label {
            font-size: 12px;
            font-weight: 500;
            color: #48465b;
            margin-bottom: 0.45rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .eult-label .req {
            color: #fd397a;
            margin-left: 3px;
        }

        .eult-input {
            border: 1px solid #e2e5ec;
            border-radius: 4px;
            height: calc(1.5em + 1.4rem + 2px);
            padding: 0.65rem 1rem;
            font-size: 13px;
            font-weight: 400;
            color: #48465b;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            width: 100%;
        }

        .eult-input::placeholder,
        .form-control::placeholder {
            color: #74788d;
            opacity: 1;
        }

        /* Komponen Unggah Dokumen Berkas Persyaratan (Delight Dropzone) */
        .eult-dropzone {
            position: relative;
            border: 2px dashed #e2e5ec;
            border-radius: 8px;
            background: #fbfcfe;
            padding: 1.5rem 1.25rem;
            text-align: center;
            transition: border-color 0.2s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            overflow: hidden;
        }

        .eult-dropzone:hover {
            border-color: #5d78ff;
            background: #f8faff;
        }

        .eult-dropzone--dragover {
            border-color: #5d78ff !important;
            background-color: rgba(93, 120, 255, 0.05) !important;
            box-shadow: 0 0 0 4px rgba(93, 120, 255, 0.12);
        }

        .eult-dropzone:focus-within {
            border-color: #5d78ff;
            box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.18);
        }

        /* Input file asli di-overlay transparan secara accessible */
        .eult-file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .eult-dropzone.has-file .eult-file-input {
            pointer-events: none;
            display: none;
        }

        /* Empty State */
        .eult-dropzone__empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .eult-dropzone__icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(93, 120, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            color: #5d78ff;
            font-size: 1.5rem;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .eult-dropzone:hover .eult-dropzone__icon-wrapper,
        .eult-dropzone--dragover .eult-dropzone__icon-wrapper {
            transform: translateY(-2px);
            background: rgba(93, 120, 255, 0.15);
        }

        .eult-dropzone__prompt {
            margin-bottom: 0.75rem;
        }

        .eult-dropzone__primary-text {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #48465b;
            margin-bottom: 0.25rem;
        }

        .eult-dropzone__primary-text strong {
            color: #5d78ff;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .eult-dropzone__secondary-text {
            display: block;
            font-size: 11px;
            color: #74788d;
        }

        .eult-dropzone__browse-btn {
            font-size: 12px;
            padding: 0.35rem 1rem;
            border-radius: 4px;
            font-weight: 500;
        }

        /* Selected State / File Preview Card */
        .eult-dropzone__selected {
            position: relative;
            z-index: 5;
            text-align: left;
        }

        .eult-file-card {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #ebedf2;
            border-radius: 8px;
            padding: 0.85rem 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .eult-file-card__icon-box {
            position: relative;
            width: 44px;
            height: 48px;
            background: rgba(253, 57, 122, 0.08);
            border: 1px solid rgba(253, 57, 122, 0.2);
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 1rem;
        }

        .eult-file-card__icon {
            font-size: 1.5rem;
            color: #fd397a;
            line-height: 1;
        }

        .eult-file-card__ext-badge {
            font-size: 10px;
            font-weight: 700;
            color: #fd397a;
            letter-spacing: 0.5px;
            line-height: 1;
            margin-top: 2px;
        }

        .eult-file-card__details {
            flex-grow: 1;
            min-width: 0;
            margin-right: 1rem;
        }

        .eult-file-card__header {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 0.25rem;
            gap: 0.5rem;
        }

        .eult-file-card__name {
            font-size: 13px;
            font-weight: 600;
            color: #48465b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .eult-file-card__status-badge {
            font-size: 10px;
            padding: 0.2rem 0.5rem;
            font-weight: 600;
            border-radius: 4px;
            background-color: #0abb87;
            color: #ffffff;
            white-space: nowrap;
        }

        .eult-file-card__meta {
            font-size: 11px;
            color: #74788d;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .eult-file-card__size {
            font-variant-numeric: tabular-nums;
            font-weight: 500;
            color: #5d78ff;
        }

        .eult-file-card__actions {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-shrink: 0;
        }

        .eult-file-card__change-btn {
            font-size: 12px;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
        }

        .eult-file-card__remove-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .eult-file-card__remove-btn:hover {
            background: rgba(253, 57, 122, 0.08);
            color: #fd397a;
        }

        /* Feedback Pesan Kesalahan / Validasi */
        .eult-upload-feedback {
            margin-top: 0.6rem;
            padding: 0.6rem 0.85rem;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            color: #fd397a;
            background: rgba(253, 57, 122, 0.08);
            border: 1px solid rgba(253, 57, 122, 0.2);
            display: flex;
            align-items: center;
        }

        .eult-input:focus {
            border-color: #5d78ff;
            box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.18);
        }

        /* Format angka tabular untuk konsistensi pembacaan data */
        .captcha-display,
        #ticketIdentitas,
        [name="nomorTiket"],
        [name="captcha"],
        [name="ticketNoHp"] {
            font-variant-numeric: tabular-nums;
        }

        /* Indikator Langkah Bertahap Circular */
        .eult-step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #5d78ff;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            margin-right: 8px;
            vertical-align: middle;
            flex-shrink: 0;
            box-shadow: 0 2px 5px rgba(93, 120, 255, 0.3);
        }

        /* Judul Seksi Bertahap dalam Formulir */
        .eult-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1e1e2d;
            letter-spacing: -0.01em;
            line-height: 1.35;
            margin-bottom: 1.1rem;
            display: flex;
            align-items: center;
        }

        /* Tipografi Teks Panduan Form */
        .eult-guide-text {
            color: #646c9a;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 1.25rem;
        }

        .eult-guide-text-sm {
            color: #74788d;
            font-size: 12px;
            line-height: 1.55;
            margin-bottom: 1.25rem;
        }

        /* Penstabilan Animasi Flip Card */
        .kt-login__create,
        .kt-login__track,
        .kt-login__signin {
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        /* Micro-interactions pada Tombol Bersihkan & Navigasi Footer Card */
        .eult-portlet-foot a.btn-clean {
            transition: color 0.2s ease;
        }

        .eult-portlet-foot a.btn-clean:hover {
            color: #3758ff !important;
            text-decoration: none;
        }

        .eult-portlet-foot a.btn-clean i {
            transition: transform 0.2s ease;
        }

        .eult-portlet-foot a.btn-clean:hover i {
            transform: translateX(-3px);
        }

        .input-group-text {
            border: 1px solid #e2e5ec;
            background-color: #f7f8fc;
            color: #74788d;
            min-width: 44px;
            justify-content: center;
        }

        .eult-badge-live {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 2rem;
            font-size: 12px;
            font-weight: 500;
            background: rgba(10, 187, 135, 0.15);
            color: #0abb87;
            border: 1px solid rgba(10, 187, 135, 0.25);
            white-space: nowrap;
        }

        .eult-badge-live::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #0abb87;
            margin-right: 6px;
            box-shadow: 0 0 6px #0abb87;
            animation: pulse-dot 1.8s infinite;
        }

        @keyframes pulse-dot {
            0% { transform: scale(0.9); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.8; }
        }

        /* Kotak Captcha Keamanan */
        .eult-captcha-box {
            background: #f7f8fc;
            border: 1px dashed #e2e5ec;
            border-radius: 4px;
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .captcha-display {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 6px;
            color: #5d78ff;
            background: #ffffff;
            padding: 4px 14px;
            border-radius: 4px;
            border: 1px solid #ebedf2;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }

        .refresh-captcha {
            font-size: 12px;
            color: #5d78ff;
            font-weight: 500;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            transition: color 0.2s;
        }

        .refresh-captcha i {
            margin-right: 5px;
            transition: transform 0.4s ease;
        }

        .refresh-captcha:hover i {
            transform: rotate(180deg);
        }

        /* Kartu Syarat Berkas Dinamis */
        .eult-syarat-card {
            background: rgba(255, 184, 34, 0.08);
            border: 1px solid rgba(255, 184, 34, 0.3);
            border-radius: 4px;
            padding: 1.2rem 1.4rem;
            margin-top: 0.75rem;
        }

        .eult-syarat-title {
            color: #734c00;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .eult-syarat-title i {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        /* Banner Bantuan WhatsApp Helpdesk */
        .eult-help-card {
            background: #ffffff;
            border: 1px solid rgba(10, 187, 135, 0.25);
            border-radius: 8px;
            padding: 1.2rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
            box-shadow: 0 2px 12px rgba(10, 187, 135, 0.1);
            flex-wrap: wrap;
            gap: 12px;
        }

        .eult-help-content {
            display: flex;
            align-items: center;
        }

        .eult-help-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(10, 187, 135, 0.12);
            color: #0abb87;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 14px;
            flex-shrink: 0;
        }

        .eult-help-text h5 {
            margin: 0 0 3px 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: #282a3c;
        }

        .eult-help-text p {
            margin: 0;
            font-size: 12px;
            color: #74788d;
        }

        /* Footer dengan dukungan safe-area */
        .eult-footer {
            padding: 1.75rem 0;
            text-align: center;
            color: #74788d;
            font-size: 12px;
            border-top: 1px solid rgba(235, 237, 242, 0.85);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            margin-top: 3.5rem;
            padding-bottom: max(1.75rem, env(safe-area-inset-bottom));
        }

        /* ==========================================================================
           Penyempurnaan Komprehensif Komponen Select2 (Civic Academic Registry)
           ========================================================================== */
        
        /* 1. Kotak Pilihan Utama (Selection Box) */
        .select2-container--default .select2-selection--single {
            border: 1px solid #e2e5ec !important;
            min-height: calc(1.5em + 1.4rem + 2px);
            height: calc(1.5em + 1.4rem + 2px) !important;
            border-radius: 4px !important;
            background-color: #ffffff;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            position: relative;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        /* Status Fokus & Terbuka */
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #5d78ff !important;
            box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.18) !important;
            outline: none;
        }

        /* Status Disabled yang Bersih & Elegan */
        .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: #f7f8fa !important;
            border-color: #ebedf2 !important;
            cursor: not-allowed !important;
            opacity: 0.75;
        }

        .select2-container--default.select2-container--disabled .select2-selection--single .select2-selection__rendered {
            color: #74788d !important;
            cursor: not-allowed !important;
        }

        .select2-container--default.select2-container--disabled .select2-selection--single .select2-selection__arrow::after {
            border-color: #e2e5ec !important;
        }

        /* Status Error / Invalid Validasi Form */
        .is-invalid + .select2-container--default .select2-selection--single,
        .select2-container--default.is-invalid .select2-selection--single {
            border-color: #fd397a !important;
            box-shadow: 0 0 0 0.2rem rgba(253, 57, 122, 0.18) !important;
        }

        /* Teks Opsi Terpilih */
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(1.5em + 1.4rem) !important;
            color: #48465b !important;
            font-size: 13px;
            font-weight: 400;
            padding-left: 0 !important;
            padding-right: 24px !important;
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #74788d !important;
        }

        /* Ikon Panah Dropdown (Modern Chevron) */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            width: 28px !important;
            right: 10px !important;
            top: 0 !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b,
        .select2-container--default .select2-selection--single .select2-selection__arrow::before {
            display: none !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow::after {
            content: '';
            display: block;
            width: 7px;
            height: 7px;
            border-right: 2px solid #74788d;
            border-bottom: 2px solid #74788d;
            transform: rotate(45deg);
            transition: transform 0.25s ease, border-color 0.2s ease;
            margin-bottom: 2px;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow::after {
            transform: rotate(-135deg);
            border-color: #5d78ff;
            margin-top: 3px;
            margin-bottom: 0;
        }

        /* 2. Panel Dropdown Mengambang */
        .select2-dropdown {
            border: 1px solid #ebedf2 !important;
            border-radius: 4px !important;
            box-shadow: 0px 10px 30px 0px rgba(82, 63, 105, 0.15) !important;
            background-color: #ffffff !important;
            z-index: 1060;
            overflow: hidden;
            margin-top: 4px;
        }

        /* 3. Kotak Input Pencarian di Dropdown */
        .select2-search--dropdown {
            padding: 10px 12px !important;
            background-color: #fafbfe;
            border-bottom: 1px solid #ebedf2;
            position: relative;
        }

        .select2-search--dropdown .select2-search__field {
            height: 38px !important;
            padding: 0.5rem 0.75rem 0.5rem 2.2rem !important;
            border: 1px solid #e2e5ec !important;
            border-radius: 4px !important;
            font-size: 13px !important;
            color: #48465b;
            background-color: #ffffff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='14' height='14' fill='none' stroke='%2374788d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 10px center;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #5d78ff !important;
            box-shadow: 0 0 0 0.15rem rgba(93, 120, 255, 0.15) !important;
            outline: none;
        }

        /* 4. Daftar Opsi Hasil */
        .select2-results__options {
            max-height: 280px;
            padding: 4px 0;
            scrollbar-width: thin;
            scrollbar-color: #e2e5ec #f2f3f8;
        }

        .select2-results__options::-webkit-scrollbar {
            width: 6px;
        }
        .select2-results__options::-webkit-scrollbar-track {
            background: #f2f3f8;
        }
        .select2-results__options::-webkit-scrollbar-thumb {
            background: #e2e5ec;
            border-radius: 4px;
        }
        .select2-results__options::-webkit-scrollbar-thumb:hover {
            background: #74788d;
        }

        /* Opsi Satuan */
        .select2-results__option {
            padding: 9px 14px !important;
            font-size: 13px;
            line-height: 1.45;
            color: #48465b;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        /* Opsi Hover / Highlighted */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: rgba(93, 120, 255, 0.08) !important;
            color: #5d78ff !important;
            font-weight: 500;
        }

        /* Opsi yang Dipilih */
        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: rgba(93, 120, 255, 0.12) !important;
            color: #5d78ff !important;
            font-weight: 600;
        }

        /* Grup Kategori (Optgroup) */
        .select2-container--default .select2-results__group {
            padding: 8px 14px 4px 14px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #74788d !important;
            background-color: #f7f8fc;
            border-top: 1px solid #ebedf2;
            border-bottom: 1px solid #ebedf2;
            margin-top: 4px;
        }

        .select2-container--default .select2-results__group:first-child {
            border-top: none;
            margin-top: 0;
        }

        /* Pesan Kosong / Searching */
        .select2-results__message {
            padding: 1.25rem 1rem !important;
            text-align: center;
            color: #74788d;
            font-size: 13px;
            font-style: italic;
        }

        /* Sembunyikan elemen bawaan form yang di-override */
        .kt-login__options {
            display: none !important;
        }

        /* ==========================================================================
           Media Queries: Tablet (768px - 1023px)
           ========================================================================== */
        @media (min-width: 768px) and (max-width: 1023.98px) {
            .eult-portlet-head {
                padding: 1.25rem 1.5rem;
            }
            .eult-portlet-body {
                padding: 1.75rem 1.5rem;
            }
            .eult-portlet-foot {
                padding: 1.15rem 1.5rem;
            }
            .eult-brand-title {
                font-size: 1.2rem;
            }
            .eult-nav-btn {
                padding: 0.6rem 1.1rem;
                font-size: 12px;
            }
        }

        /* ==========================================================================
           Media Queries: Ponsel / Mobile (< 768px)
           ========================================================================== */
        @media (max-width: 767.98px) {
            .eult-navbar {
                padding: 0.75rem 0;
            }

            .eult-brand-logo {
                height: 38px;
            }

            .eult-brand-title {
                font-size: 1rem;
                line-height: 1.2;
            }

            .eult-brand-subtitle {
                font-size: 11px;
            }

            .eult-hero-section {
                padding: 1.5rem 0 0.75rem 0;
            }

            .eult-hero-title {
                font-size: 1.5rem;
            }

            .eult-hero-desc {
                font-size: 12px;
                line-height: 1.5;
                padding: 0 5px;
            }

            /* Navigasi Segmen Tab Penuh pada Mobile */
            .eult-nav-tabs-wrapper {
                padding: 0 5px;
            }

            .eult-nav-tabs {
                display: flex;
                width: 100%;
                max-width: 460px;
                margin-top: 1.25rem;
                margin-bottom: 0.75rem;
                padding: 4px;
                border-radius: 8px;
                gap: 4px;
                flex-wrap: nowrap;
            }

            .eult-nav-btn {
                flex: 1 1 0;
                min-width: 0;
                padding: 0.65rem 0.25rem;
                font-size: 12px;
                border-radius: 4px;
                justify-content: center;
                min-height: 44px;
                line-height: 1.2;
            }

            .eult-nav-btn i {
                margin-right: 5px;
                font-size: 12px;
            }

            .eult-nav-btn span {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Kontainer & Portlet */
            .eult-card-container {
                margin: 1rem auto 2rem auto;
                padding: 0 10px;
            }

            .eult-portlet-head {
                padding: 1rem 1.15rem;
                flex-wrap: wrap;
                gap: 8px;
            }

            .eult-portlet-head-title {
                font-size: 1.2rem;
                line-height: 1.3;
            }

            .eult-portlet-head-title i {
                font-size: 1.2rem;
                margin-right: 8px;
            }

            .eult-portlet-body {
                padding: 1.25rem 1.15rem;
            }

            .eult-portlet-foot {
                padding: 1rem 1.15rem;
                flex-direction: column-reverse;
                align-items: stretch;
                gap: 12px;
            }

            .eult-portlet-foot > div {
                width: 100%;
            }

            .eult-portlet-foot > div:last-child {
                display: flex;
                flex-direction: column-reverse;
                gap: 8px;
            }

            .eult-portlet-foot > div:last-child .btn {
                width: 100%;
                margin-right: 0 !important;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .eult-portlet-foot > div:first-child {
                text-align: center;
            }

            .eult-portlet-foot a.btn-clean {
                width: 100%;
                text-align: center;
                min-height: 42px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Font 16px pada input mobile untuk mencegah zoom otomatis iOS Safari & presisi tinggi 1:1 */
            .eult-input,
            .form-control,
            .custom-file-input,
            .select2-container--default .select2-selection--single,
            .select2-container--default .select2-selection--single .select2-selection__rendered,
            .select2-search--dropdown .select2-search__field {
                font-size: 16px !important;
            }

            .select2-search--dropdown .select2-search__field {
                height: 42px !important;
            }

            .select2-results__option {
                padding: 12px 14px !important;
                font-size: 13px;
            }

            .eult-label {
                font-size: 12px;
            }

            .form-text {
                font-size: 11px;
            }

            /* Penyesuaian Captcha Mobile */
            .eult-captcha-box {
                padding: 0.75rem 1rem;
            }

            .captcha-display {
                font-size: 1.5rem;
                letter-spacing: 4px;
                padding: 3px 10px;
            }

            .refresh-captcha {
                min-height: 44px;
                padding: 4px 8px;
            }

            /* Penyesuaian Card WhatsApp Helpdesk */
            .eult-help-card {
                padding: 1.15rem;
                flex-direction: column;
                align-items: stretch;
            }

            .eult-help-card .btn {
                width: 100%;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-top: 6px;
            }

            /* Penyesuaian Dropzone & Kartu Berkas pada Layar Mobile */
            .eult-dropzone {
                padding: 1.25rem 0.75rem;
            }

            .eult-file-card {
                flex-wrap: wrap;
                padding: 0.75rem;
            }

            .eult-file-card__details {
                margin-right: 0;
                width: calc(100% - 56px);
            }

            .eult-file-card__name {
                max-width: 100%;
            }

            .eult-file-card__actions {
                width: 100%;
                justify-content: flex-end;
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px dashed #ebedf2;
            }
        }

        /* ==========================================================================
           Media Queries: Ponsel Layar Ekstra Sempit (<= 380px)
           ========================================================================== */
        @media (max-width: 380px) {
            .eult-brand-logo {
                height: 32px;
            }

            .eult-brand-title {
                font-size: 1rem;
            }

            .eult-brand-subtitle {
                font-size: 10px;
            }

            .eult-nav-btn {
                font-size: 11px;
                padding: 0.55rem 2px;
            }

            .eult-nav-btn i {
                margin-right: 3px;
                font-size: 11px;
            }
        }
    </style>
</head>
<!-- end::Head -->

<!-- begin::Body -->
<body>

    <!-- begin:: Top Header -->
    <header class="eult-navbar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Logo & Brand -->
                <div class="d-flex align-items-center">
                    <img src="<?= base_url(); ?>assets/media/logos/logo-ult12.png" alt="Logo E-ULT UNMUL" class="eult-brand-logo mr-2 mr-sm-3">
                    <div>
                        <h1 class="eult-brand-title">E-ULT UNIVERSITAS MULAWARMAN</h1>
                        <p class="eult-brand-subtitle">Elektronik Unit Layanan Terpadu • Satu Pintu Layanan Kampus</p>
                    </div>
                </div>

                <!-- Status & Quick Staff Access (Desktop & Tablet) -->
                <div class="d-none d-md-flex align-items-center">
                    <div class="eult-badge-live mr-3">
                        Layanan Daring Aktif
                    </div>
                    <button type="button" class="btn btn-outline-brand btn-sm btn-pill eult-nav-trigger" data-target="signin" style="white-space: nowrap;">
                        <i class="flaticon-lock mr-1"></i> Akses Petugas
                    </button>
                </div>

                <!-- Quick Staff Access (Mobile) -->
                <div class="d-flex d-md-none align-items-center">
                    <button type="button" class="btn btn-outline-brand btn-sm btn-pill eult-nav-trigger px-2 py-1" data-target="signin" title="Akses Petugas" style="font-size: 11px; white-space: nowrap;">
                        <i class="flaticon-lock mr-1"></i> Petugas
                    </button>
                </div>
            </div>
        </div>
    </header>
    <!-- end:: Top Header -->

    <!-- begin:: Hero Section -->
    <section class="eult-hero-section">
        <div class="container">
            <h2 class="eult-hero-title">Layanan Terpadu Universitas Mulawarman</h2>
            <p class="eult-hero-desc">
                Sistem satu pintu pengajuan surat dinas, legalisir, perbaikan data akademik, dan pemantauan disposisi tiket layanan kampus.
            </p>

            <!-- begin:: Mode Navigation Pills -->
            <div class="eult-nav-tabs-wrapper">
                <div class="eult-nav-tabs" role="tablist" aria-label="Pilihan Layanan E-ULT">
                    <a href="#create" class="eult-nav-btn active" id="eult-tab-create" data-target="create" role="tab" aria-selected="true" aria-controls="kt-login--create">
                        <i class="flaticon-edit-1"></i> 
                        <span>Ajukan Tiket</span>
                    </a>
                    <a href="#track" class="eult-nav-btn" id="eult-tab-track" data-target="track" role="tab" aria-selected="false" aria-controls="kt-login--track">
                        <i class="flaticon-search-1"></i> 
                        <span>Lacak Tiket</span>
                    </a>
                    <a href="#signin" class="eult-nav-btn" id="eult-tab-signin" data-target="signin" role="tab" aria-selected="false" aria-controls="kt-login--signin">
                        <i class="flaticon-lock"></i> 
                        <span>Akses Petugas</span>
                    </a>
                </div>
            </div>
            <!-- end:: Mode Navigation Pills -->
        </div>
    </section>
    <!-- end:: Hero Section -->

    <!-- begin:: Main Content Stage -->
    <main class="eult-card-container">
        <div class="kt-login" id="kt_login">

            <!-- =================================================================
                 1. FORM BUAT TIKET PUBLIK (Mode Create - Default Aktif)
                 ================================================================= -->
            <div class="kt-login__create" id="kt-login--create" role="tabpanel" aria-labelledby="eult-tab-create">
                <div class="eult-portlet">
                    <div class="eult-portlet-head">
                        <h3 class="eult-portlet-head-title">
                            <i class="flaticon-file-2"></i> Formulir Pengajuan Permohonan Layanan
                        </h3>
                    </div>

                    <form class="kt-form" action="<?= base_url('login') . '/savetiket' ?>" method="POST" enctype="multipart/form-data" novalidate="novalidate" id="kt_create_form">
                        <div class="eult-portlet-body">

                            <!-- Section A: Identitas Pemohon -->
                            <div class="mb-4">
                                <h4 class="eult-section-title">
                                    <span class="eult-step-badge">1</span> Identitas Pemohon
                                </h4>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="eult-label">
                                            <span>Nomor Identitas (NIM / NIP / NIK) <span class="req">*</span></span>
                                            <span id="identitas-feedback" class="text-muted small"></span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="flaticon-user"></i></span>
                                            </div>
                                            <input type="text" class="form-control eult-input" id="ticketIdentitas" name="ticketIdentitas" placeholder="Masukkan 10 digit NIM, 18 digit NIP, atau 16 digit NIK..." value="<?= $datas != false ? $datas['ticketIdentitas'] : '' ?>" autocomplete="off">
                                        </div>
                                        <span class="form-text text-muted small mt-1">
                                            Ketik angka tanpa spasi/tanda baca. NIM/NIP diverifikasi otomatis ke data kampus; NIK untuk pemohon umum diisi nama manual.
                                        </span>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label class="eult-label">
                                            <span>Nama Lengkap Pemohon <span class="req">*</span></span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="flaticon-profile-1"></i></span>
                                            </div>
                                            <input type="text" class="form-control eult-input" name="ticketName" placeholder="Nama Mahasiswa / Pegawai / Pemohon" value="<?= $datas != false ? $datas['ticketName'] : '' ?>">
                                        </div>
                                        <span class="form-text text-muted small mt-1">
                                            Nama akan terisi otomatis saat nomor identitas terdaftar terdeteksi.
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4" style="border-top: 1px dashed #ebedf2;">

                            <!-- Section B: Layanan & Ketentuan Dokumen -->
                            <div class="mb-4">
                                <h4 class="eult-section-title">
                                    <span class="eult-step-badge">2</span> Pilihan Layanan & Persyaratan Berkas
                                </h4>
                                <div class="row">
                                    <div class="col-md-8 form-group">
                                        <label class="eult-label">
                                            <span>Kategori Layanan <span class="req">*</span></span>
                                        </label>
                                        <select class="form-control m-select2" name="ticketCategories" id="ticketCategories" data-placeholder="Masukkan nomor identitas terlebih dahulu..." disabled>
                                            <option value="">Masukkan nomor identitas terlebih dahulu...</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label class="eult-label">
                                            <span>Tingkat Prioritas <span class="req">*</span></span>
                                        </label>
                                        <select class="form-control m-select2" name="ticketPriority" data-placeholder="Pilih Tingkat Prioritas">
                                            <option value="">Pilih Tingkat Prioritas</option>
                                            <?php
                                            if ($r_priority != false) :
                                                foreach ($r_priority as $row) :
                                                    echo '<option value="' . $row['priorityId'] . '" ' . ($datas != false ? ($row['priorityName'] == $datas['ticketPriority'] ? 'selected' : '') : '') . '>' . $row['priorityName'] . '</option>';
                                                endforeach;
                                            endif;
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Checklist Syarat Berkas Dinamis -->
                                <div class="eult-syarat-card">
                                    <div class="eult-syarat-title">
                                        <i class="flaticon2-information"></i> Ketentuan & Dokumen Persyaratan
                                    </div>
                                    <div id="syarat" class="text-dark small mb-2">
                                        <p class="text-muted mb-0 font-italic">Pilih kategori layanan di atas untuk melihat rincian dokumen persyaratan yang diperlukan.</p>
                                    </div>
                                    <div class="pt-2 mt-2 border-top border-warning-light text-muted small d-flex align-items-center">
                                        <i class="flaticon-doc text-warning mr-2"></i>
                                        <span>Ketentuan Berkas: <strong>Format PDF</strong> • Ukuran maksimal <strong>10MB</strong> • Seluruh berkas pendukung wajib digabung menjadi <strong>1 file PDF</strong>.</span>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4" style="border-top: 1px dashed #ebedf2;">

                            <!-- Section C: Rincian Kontak & Isi Permohonan -->
                            <div class="mb-4">
                                <h4 class="eult-section-title">
                                    <span class="eult-step-badge">3</span> Kontak & Detail Permohonan
                                </h4>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="eult-label">
                                            <span>Alamat Email Aktif <span class="req">*</span></span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="flaticon-email"></i></span>
                                            </div>
                                            <input type="email" class="form-control eult-input" name="ticketEmail" placeholder="contoh: nama@unmul.ac.id" value="<?= $datas != false ? $datas['ticketEmail'] : '' ?>" autocomplete="off">
                                        </div>
                                        <span class="form-text text-muted small mt-1">Nomor tiket dan bukti pelacakan akan dikirimkan ke email ini.</span>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label class="eult-label">
                                            <span>Nomor WhatsApp / Handphone <span class="req">*</span></span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light"><i class="flaticon-whatsapp"></i></span>
                                            </div>
                                            <input type="text" class="form-control eult-input" name="ticketNoHp" placeholder="contoh: 081234567890" value="<?= $datas != false ? $datas['ticketNoHp'] : '' ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="eult-label">
                                        <span>Subjek / Perihal Permohonan <span class="req">*</span></span>
                                    </label>
                                    <input type="text" class="form-control eult-input" name="ticketSubject" placeholder="Contoh: Permohonan Surat Keterangan Pengganti Ijazah / Legalisir Ijazah" value="<?= $datas != false ? $datas['ticketSubject'] : '' ?>">
                                </div>

                                <div class="form-group">
                                    <label class="eult-label">
                                        <span>Uraian / Pesan Tambahan <span class="req">*</span></span>
                                    </label>
                                    <textarea class="form-control eult-input" name="ticketMessage" placeholder="Tuliskan keterangan tambahan atau keperluan permohonan Anda..." rows="3" style="height: auto; resize: vertical;"><?= $datas != false ? $datas['ticketMessage'] : '' ?></textarea>
                                </div>

                                <div class="form-group mb-4" id="eult-upload-group">
                                    <label class="eult-label" for="ticketArchiveId">
                                        <span>Unggah Dokumen Berkas Persyaratan (PDF Gabungan) <span class="req">*</span></span>
                                    </label>
                                    <div class="eult-dropzone" id="eult-dropzone" role="region" aria-label="Area unggah dokumen berkas persyaratan">
                                        <!-- Input file asli yang fungsional untuk submit dan keyboard accessibility -->
                                        <input type="file" 
                                               class="eult-file-input" 
                                               name="ticketArchiveId" 
                                               id="ticketArchiveId" 
                                               accept=".pdf,application/pdf"
                                               aria-describedby="upload-hint">

                                        <!-- State A: Zona Tarik & Lepas (Empty State) -->
                                        <div class="eult-dropzone__empty" id="eult-dropzone-empty">
                                            <div class="eult-dropzone__icon-wrapper">
                                                <i class="flaticon-upload eult-dropzone__icon"></i>
                                            </div>
                                            <div class="eult-dropzone__prompt">
                                                <span class="eult-dropzone__primary-text">
                                                    <strong>Pilih dokumen</strong> atau seret berkas PDF ke sini
                                                </span>
                                                <span class="eult-dropzone__secondary-text">
                                                    Format resmi berkas tunggal (.PDF) dengan kapasitas maksimum 10MB
                                                </span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-brand eult-dropzone__browse-btn" tabindex="-1">
                                                Jelajahi Berkas
                                            </button>
                                        </div>

                                        <!-- State B: Kartu Pratinjau Berkas Terpilih (Selected State) -->
                                        <div class="eult-dropzone__selected" id="eult-dropzone-selected" style="display: none;">
                                            <div class="eult-file-card">
                                                <div class="eult-file-card__icon-box">
                                                    <i class="flaticon-doc eult-file-card__icon"></i>
                                                    <span class="eult-file-card__ext-badge">PDF</span>
                                                </div>
                                                <div class="eult-file-card__details">
                                                    <div class="eult-file-card__header">
                                                        <span class="eult-file-card__name" id="eult-file-name" title="">nama_dokumen.pdf</span>
                                                        <span class="badge badge-success eult-file-card__status-badge">
                                                            <i class="flaticon2-check-mark mr-1"></i> Siap Diunggah
                                                        </span>
                                                    </div>
                                                    <div class="eult-file-card__meta">
                                                        <span class="eult-file-card__size" id="eult-file-size">0 KB</span>
                                                        <span class="eult-file-card__dot">•</span>
                                                        <span class="eult-file-card__type">Dokumen PDF Terverifikasi</span>
                                                    </div>
                                                </div>
                                                <div class="eult-file-card__actions">
                                                    <button type="button" class="btn btn-sm btn-label-brand eult-file-card__change-btn" id="eult-change-file-btn" title="Ganti berkas dengan file PDF lain">
                                                        Ganti
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-icon btn-clean btn-icon-md text-danger eult-file-card__remove-btn" id="eult-remove-file-btn" title="Batalkan berkas ini" aria-label="Batalkan berkas ini">
                                                        <i class="flaticon2-cross"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pesan Validasi / Galat Instan -->
                                    <div class="eult-upload-feedback" id="eult-upload-feedback" style="display: none;" role="alert">
                                        <i class="flaticon2-warning mr-2"></i>
                                        <span id="eult-upload-feedback-text"></span>
                                    </div>

                                    <span class="form-text text-muted small mt-2" id="upload-hint">
                                        <i class="flaticon2-information mr-1"></i> Seluruh lembar kelengkapan (identitas, surat permohonan, lampiran) disatukan dalam 1 berkas PDF yang terbaca jelas.
                                    </span>
                                </div>
                            </div>

                            <hr class="my-4" style="border-top: 1px dashed #ebedf2;">

                            <!-- Section D: Validasi Keamanan Captcha -->
                            <div>
                                <h4 class="eult-section-title">
                                    <span class="eult-step-badge">4</span> Konfirmasi Keamanan
                                </h4>
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <div class="eult-captcha-box">
                                            <div>
                                                <span class="captcha-display"><?= $captcha ?></span>
                                            </div>
                                            <a href="#" class="refresh-captcha">
                                                <i class="flaticon-refresh"></i> Acak Ulang
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="form-group mb-0">
                                            <input class="form-control eult-input" type="text" placeholder="Masukkan 4 karakter di samping..." name="captcha" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="eult-portlet-foot">
                            <div class="text-muted small">
                                <i class="flaticon2-safe text-success mr-1"></i> Data Anda dilindungi oleh sistem keamanan Universitas Mulawarman.
                            </div>
                            <div>
                                <button type="reset" class="btn btn-secondary mr-2">
                                    <i class="flaticon2-delete mr-1"></i> Bersihkan
                                </button>
                                <button id="kt_create_submit" class="btn btn-brand btn-elevate font-weight-bold px-4">
                                    <i class="flaticon2-checkmark mr-1"></i> Kirim Permohonan Tiket
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- =================================================================
                 2. FORM LACAK TIKET PUBLIK (Mode Track)
                 ================================================================= -->
            <div class="kt-login__track" id="kt-login--track" role="tabpanel" aria-labelledby="eult-tab-track" style="display: none;">
                <div class="eult-portlet" style="max-width: 680px; margin: 0 auto;">
                    <div class="eult-portlet-head">
                        <h3 class="eult-portlet-head-title">
                            <i class="flaticon-search-1"></i> Lacak Status Permohonan Tiket
                        </h3>
                    </div>

                    <form class="kt-form" action="<?= base_url('login') . '/cektiket' ?>" method="POST" novalidate="novalidate" id="kt_track_form">
                        <div class="eult-portlet-body">
                            <p class="eult-guide-text">
                                Masukkan nomor tiket pengajuan untuk memeriksa riwayat disposisi, progres verifikasi berkas, dan mengunduh surat resmi yang telah diterbitkan.
                            </p>

                            <div class="form-group">
                                <label class="eult-label font-weight-bold">
                                    <span>Nomor Tiket Permohonan <span class="req">*</span></span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light"><i class="fa fa-ticket-alt text-primary"></i></span>
                                    </div>
                                    <input class="form-control eult-input" type="text" placeholder="Contoh: ULT-2026-XXXX atau kode tiket Anda..." name="nomorTiket" autocomplete="off">
                                </div>
                                <span class="form-text text-muted small mt-2">
                                    <i class="flaticon2-information mr-1"></i> Nomor tiket bersifat unik dan telah dikirimkan ke email terdaftar saat tiket pertama kali diajukan.
                                </span>
                            </div>
                        </div>

                        <div class="eult-portlet-foot">
                            <div>
                                <a href="#create" class="btn btn-clean text-primary eult-nav-trigger" data-target="create">
                                    <i class="flaticon2-left-arrow-1 mr-1"></i> Kembali ke Form Pengajuan
                                </a>
                            </div>
                            <div>
                                <button id="kt_track_submit" class="btn btn-brand btn-elevate font-weight-bold px-4">
                                    <i class="flaticon-search mr-1"></i> Lacak Tiket Sekarang
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- =================================================================
                 3. FORM LOGIN PETUGAS / PEGAWAI (Mode Sign In)
                 ================================================================= -->
            <div class="kt-login__signin" id="kt-login--signin" role="tabpanel" aria-labelledby="eult-tab-signin" style="display: none;">
                <div class="eult-portlet" style="max-width: 520px; margin: 0 auto;">
                    <div class="eult-portlet-head">
                        <h3 class="eult-portlet-head-title">
                            <i class="flaticon-lock"></i> Masuk Area Petugas & Verifikator
                        </h3>
                    </div>

                    <form class="kt-form" action="<?= base_url() . 'otentifikasi' ?>" method="post" novalidate="novalidate" id="kt_login_form">
                        <div class="eult-portlet-body">
                            <p class="eult-guide-text-sm">
                                Masuk untuk petugas loket ULT, verifikator unit kerja, pejabat penandatangan dokumen, dan administrator sistem.
                            </p>

                            <div class="form-group">
                                <label class="eult-label">Username / Akun Pegawai <span class="req">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light"><i class="flaticon-user"></i></span>
                                    </div>
                                    <input class="form-control eult-input" type="text" placeholder="Masukkan username pegawai..." name="username" autocomplete="off">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="eult-label">Password <span class="req">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light"><i class="fa fa-key"></i></span>
                                    </div>
                                    <input class="form-control eult-input" type="password" placeholder="Masukkan password..." name="password" id="login_password" autocomplete="off">
                                    <div class="input-group-append">
                                        <span id="toggle_password" class="input-group-text bg-light cursor-pointer" style="cursor: pointer;" title="Tampilkan/Sembunyikan Password">
                                            <i class="fa fa-eye-slash text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label class="eult-label">Konfirmasi Keamanan (Captcha) <span class="req">*</span></label>
                                <div class="eult-captcha-box">
                                    <div>
                                        <span class="captcha-display"><?= $captcha ?></span>
                                    </div>
                                    <a href="#" class="refresh-captcha">
                                        <i class="flaticon-refresh"></i> Refresh
                                    </a>
                                </div>
                                <input class="form-control eult-input mt-2" type="text" placeholder="Ketik 4 karakter captcha..." name="captcha" autocomplete="off">
                            </div>
                        </div>

                        <div class="eult-portlet-foot">
                            <div>
                                <a href="#create" class="btn btn-clean text-primary eult-nav-trigger" data-target="create">
                                    <i class="flaticon2-left-arrow-1 mr-1"></i> Layanan Publik
                                </a>
                            </div>
                            <div>
                                <button id="kt_signin_submit" class="btn btn-brand btn-elevate font-weight-bold px-4">
                                    <i class="flaticon2-check-mark mr-1"></i> Masuk Sistem
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <!-- Tombol hidden pendukung handler login.js Metronic -->
        <div style="display: none;">
            <button id="kt_create" type="button"></button>
            <button id="kt_tracking" type="button"></button>
            <button id="kt_signin" type="button"></button>
        </div>

        <!-- begin:: WhatsApp Helpdesk Card -->
        <div class="eult-help-card">
            <div class="eult-help-content">
                <div class="eult-help-icon">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="eult-help-text">
                    <h5>Butuh Bantuan atau Informasi Layanan Terpadu?</h5>
                    <p>Layanan konsultasi dan helpdesk ULT Universitas Mulawarman siap membantu Anda pada jam kerja operasional.</p>
                </div>
            </div>
            <a href="https://wa.me/628115809970" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-elevate font-weight-bold px-4 py-2" style="background-color: #0abb87; border-color: #0abb87;">
                <i class="fab fa-whatsapp mr-1"></i> Chat WhatsApp ULT
            </a>
        </div>
        <!-- end:: WhatsApp Helpdesk Card -->
    </main>
    <!-- end:: Main Content Stage -->

    <!-- begin:: Footer -->
    <footer class="eult-footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-left mb-2 mb-md-0">
                    <strong>&copy; <?= date('Y'); ?> Universitas Mulawarman</strong> — Unit Layanan Terpadu (E-ULT v2).
                </div>
                <div class="col-md-6 text-md-right small">
                    <span class="text-muted">Kampus Gunung Kelua, Samarinda, Kalimantan Timur • Akuntabel, Cepat, & Terintegrasi</span>
                </div>
            </div>
        </div>
    </footer>
    <!-- end:: Footer -->

    <!-- begin::Global Config(global config for global JS scripts) -->
    <script>
        var KTAppOptions = {
            "colors": {
                "state": {
                    "brand": "#5d78ff",
                    "dark": "#282a3c",
                    "light": "#ffffff",
                    "primary": "#5867dd",
                    "success": "#0abb87",
                    "info": "#5578eb",
                    "warning": "#ffb822",
                    "danger": "#fd397a"
                },
                "base": {
                    "label": ["#c5cbe3", "#a1a8c3", "#3d4465", "#3e4466"],
                    "shape": ["#f0f3ff", "#d9dffa", "#afb4d4", "#646c9a"]
                }
            }
        };
    </script>
    <!-- end::Global Config -->

    <!--begin::Global Theme Bundle(used by all pages) -->
    <script src="<?= base_url(); ?>assets/plugins/global/plugins.bundle.js" type="text/javascript"></script>
    <script src="<?= base_url(); ?>assets/js/scripts.bundle.js" type="text/javascript"></script>
    <!--end::Global Theme Bundle -->

    <!--begin::Page Scripts(used by this page) -->
    <script src="<?= base_url(); ?>assets/js/pages/custom/pages/user/login.js?v=20260913i" type="text/javascript"></script>
    <!--end::Page Scripts -->

    <!--begin::Delight Navigation Controller & Feedback Scripts -->
    <script>
        jQuery(document).ready(function($) {
            // Sinkronisasi navigasi mode (create / track / signin)
            function switchEultMode(targetMode) {
                $('.eult-nav-btn').attr('aria-selected', 'false').removeClass('active');
                $('#eult-tab-' + targetMode).attr('aria-selected', 'true').addClass('active');

                if (targetMode === 'create') {
                    $('#kt_create').trigger('click');
                } else if (targetMode === 'track') {
                    $('#kt_tracking').trigger('click');
                } else if (targetMode === 'signin') {
                    $('#kt_signin').trigger('click');
                }
            }

            $('.eult-nav-btn, .eult-nav-trigger').on('click', function(e) {
                e.preventDefault();
                var target = $(this).data('target');
                if (target) {
                    switchEultMode(target);
                }
            });

            // Default saat halaman terbuka: tampilkan mode buat tiket
            setTimeout(function() {
                switchEultMode('create');
            }, 100);

            // Indikator visual real-time saat nomor identitas diketik
            $('#ticketIdentitas').on('input', function() {
                var len = $(this).val().length;
                var feedback = $('#identitas-feedback');
                if (len === 10 || len === 16 || len === 18 || len === 14) {
                    feedback.html('<span class="text-primary"><i class="flaticon2-refresh kt-spinner kt-spinner--sm kt-spinner--primary"></i> Memeriksa data...</span>');
                } else if (len > 0) {
                    feedback.html('<span class="text-muted">(' + len + ' digit)</span>');
                } else {
                    feedback.html('');
                }
            });

            // Sinkronisasi status pengecekan data identitas dari respons AJAX getIdentitas
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings && settings.url && settings.url.indexOf('getIdentitas') !== -1) {
                    try {
                        var res = typeof xhr.responseJSON === 'object' ? xhr.responseJSON : JSON.parse(xhr.responseText);
                        if (res && res.status === true && res.umum === true) {
                            $('#identitas-feedback').html('<span class="text-info font-weight-bold"><i class="flaticon2-information"></i> Pemohon Umum — isi nama manual</span>');
                        } else if (res && res.status === true) {
                            $('#identitas-feedback').html('<span class="text-success font-weight-bold"><i class="flaticon2-check-mark"></i> Data Ditemukan</span>');
                        } else {
                            $('#identitas-feedback').html('<span class="text-danger font-weight-bold"><i class="flaticon2-cross"></i> Data Tidak Ditemukan</span>');
                        }
                    } catch (e) {
                        // Respon bukan json valid
                    }
                }
            });

            $(document).ajaxError(function(event, xhr, settings) {
                if (settings && settings.url && settings.url.indexOf('getIdentitas') !== -1) {
                    $('#identitas-feedback').html('<span class="text-danger small">Gagal memeriksa data</span>');
                }
            });

            // Observasi perubahan nama saat nilai nama terisi
            $("[name='ticketName']").on('change', function() {
                if ($(this).val() !== '') {
                    $('#identitas-feedback').html('<span class="text-success font-weight-bold"><i class="flaticon2-check-mark"></i> Data Ditemukan</span>');
                }
            });

            // =========================================================================
            // Controller Delight Unggah Dokumen Berkas Persyaratan
            // =========================================================================
            var $dropzone = $('#eult-dropzone');
            var $fileInput = $('#ticketArchiveId');
            var $emptyState = $('#eult-dropzone-empty');
            var $selectedState = $('#eult-dropzone-selected');
            var $fileName = $('#eult-file-name');
            var $fileSize = $('#eult-file-size');
            var $feedback = $('#eult-upload-feedback');
            var $feedbackText = $('#eult-upload-feedback-text');
            var maxSizeBytes = 10 * 1024 * 1024; // 10 Megabytes

            function formatBytes(bytes) {
                if (bytes === 0) return '0 Bytes';
                var k = 1024;
                var sizes = ['Bytes', 'KB', 'MB', 'GB'];
                var i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function showUploadError(msg) {
                $feedbackText.text(msg);
                $feedback.slideDown(150);
                $dropzone.removeClass('has-file');
                $selectedState.hide();
                $emptyState.show();
            }

            function clearUploadError() {
                $feedback.hide();
                $feedbackText.text('');
            }

            function handleFileSelect(file) {
                if (!file) {
                    resetFileUpload();
                    return;
                }

                // Validasi format file PDF
                var isPdf = file.name.toLowerCase().endsWith('.pdf') || file.type === 'application/pdf';
                if (!isPdf) {
                    $fileInput.val('');
                    showUploadError('Format berkas tidak didukung (' + file.name + '). Harap unggah dokumen berformat .PDF');
                    return;
                }

                // Validasi kapasitas ukuran maksimum 10MB
                if (file.size > maxSizeBytes) {
                    $fileInput.val('');
                    showUploadError('Ukuran berkas (' + formatBytes(file.size) + ') melebihi batas ketentuan maksimum 10MB.');
                    return;
                }

                // Berkas valid: tampilkan kartu pratinjau terpilih
                clearUploadError();
                $fileName.text(file.name).attr('title', file.name);
                $fileSize.text(formatBytes(file.size));
                $emptyState.hide();
                $selectedState.fadeIn(150);
                $dropzone.addClass('has-file');
            }

            function resetFileUpload() {
                $fileInput.val('');
                clearUploadError();
                $dropzone.removeClass('has-file');
                $selectedState.hide();
                $emptyState.show();
            }

            // Drag & drop event feedback
            $dropzone.on('dragenter dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!$dropzone.hasClass('has-file')) {
                    $dropzone.addClass('eult-dropzone--dragover');
                }
            });

            $dropzone.on('dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $dropzone.removeClass('eult-dropzone--dragover');
            });

            $dropzone.on('drop', function(e) {
                if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files.length) {
                    var droppedFile = e.originalEvent.dataTransfer.files[0];
                    try {
                        var dt = new DataTransfer();
                        dt.items.add(droppedFile);
                        $fileInput[0].files = dt.files;
                    } catch (err) {
                        // Fallback browser
                    }
                    handleFileSelect(droppedFile);
                }
            });

            // Pemilihan berkas melalui input file
            $fileInput.on('change', function() {
                if (this.files && this.files.length) {
                    handleFileSelect(this.files[0]);
                } else {
                    resetFileUpload();
                }
            });

            // Tombol ganti berkas
            $('#eult-change-file-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $fileInput.trigger('click');
            });

            // Tombol batalkan/hapus berkas
            $('#eult-remove-file-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                resetFileUpload();
            });

            // Klik pada area kosong memicu pemilihan berkas
            $('#eult-dropzone-empty, .eult-dropzone__browse-btn').on('click', function(e) {
                if (e.target !== $fileInput[0]) {
                    $fileInput.trigger('click');
                }
            });

            // Bersihkan upload berkas saat tombol bersihkan form ditekan
            $('#kt_create_form').on('reset', function() {
                resetFileUpload();
            });
        });
    </script>
    <!--end::Delight Navigation Controller & Feedback Scripts -->

</body>
<!-- end::Body -->
</html>