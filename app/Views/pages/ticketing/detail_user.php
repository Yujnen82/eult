<?php
helper(['eult_tanggal', 'eult_waktu']);

$datas        = isset($datas) && is_array($datas) ? $datas : [];
$historyItems = isset($history) && is_array($history) ? $history : [];
$replyItems   = isset($replies) && is_array($replies) ? $replies : [];

$trackingId  = (string) ($datas['ticketTrackingId'] ?? '-');
$namaPemohon = (string) ($datas['ticketName'] ?? 'Pemohon');
$layananUtama = (string) ($datas['sCatNama'] ?? '');
$layananUnit  = (string) ($datas['categoryNama'] ?? '');
$tglDibuat    = (string) ($datas['ticketCreated'] ?? '');
$statusId     = (int) ($datas['ticketStatus'] ?? 0);
$statusNama   = (string) ($datas['statusNama'] ?? 'Baru');
$statusColor  = (string) ($datas['statusColor'] ?? 'brand');
$ratingNilai  = $datas['ratingNilai'] ?? '';
$sudahRating  = ! empty($datas['ratingTicketId']);
$outputUrl    = $output_url ?? false;
$saveUrl      = $save_url ?? site_url('cektiket/save_replies/');
$closeUrl     = $close_url ?? '#';
$loadAttach   = $load_attach ?? site_url('cektiket/loadattach');
$ratingUrl    = $rating_url ?? site_url('cektiket/rating');
$cetakTerima  = $cetakterima ?? '#';
// Cektiket::saveReplies/rating menentukan tiket dari kunci terenkripsi, bukan
// nomor tiket mentah. Kunci diambil dari segmen terakhir save_url agar form
// tetap sah walau view dirender dengan fallback URL tanpa kunci.
$kunciTiket   = (string) ($key ?? ($kunci ?? trim((string) parse_url((string) $saveUrl, PHP_URL_PATH), '/')));
$kunciTiket   = $kunciTiket !== '' ? basename($kunciTiket) : '';
$kunciTiket   = in_array($kunciTiket, ['save_replies', ''], true) ? '' : $kunciTiket;
$inisial      = strtoupper(substr(trim($namaPemohon) !== '' ? $namaPemohon : 'P', 0, 1));

$tglTiket = '';
if ($tglDibuat !== '') {
    $tglIndo  = function_exists('eult_tanggal_indo') ? eult_tanggal_indo(substr($tglDibuat, 0, 10)) : date('d-m-Y', strtotime($tglDibuat));
    $tglTiket = ($tglIndo !== false ? $tglIndo : date('d-m-Y', strtotime($tglDibuat))) . ' ' . date('H:i', strtotime($tglDibuat)) . ' WITA';
}

$waktuLalu = static function (string $waktu): string {
    if ($waktu === '' || ! function_exists('eult_waktu_lalu')) {
        return '';
    }
    $en = eult_waktu_lalu($waktu);
    $peta = [
        'less than a minute ago' => 'baru saja',
        '1 minute ago'           => '1 menit lalu',
        'about 1 hour ago'       => 'sekitar 1 jam lalu',
        '1 day ago'              => '1 hari lalu',
        'about 1 month ago'      => 'sekitar 1 bulan lalu',
        'about 1 year ago'       => 'sekitar 1 tahun lalu',
    ];
    if (isset($peta[$en])) {
        return $peta[$en];
    }

    return str_replace(
        [' minutes ago', ' hours ago', ' days ago', ' months ago', ' years ago', 'over '],
        [' menit lalu', ' jam lalu', ' hari lalu', ' bulan lalu', ' tahun lalu', 'lebih dari '],
        $en
    );
};

$selesai  = $statusId === 5;
$ditolak  = $statusId >= 8;
$berproses = ! $selesai && ! $ditolak;
$hintStatus = match (true) {
    $selesai  => 'Layanan telah selesai. Isi Indeks Kepuasan Masyarakat agar berkas resmi dikirim ke email Anda.',
    $ditolak  => 'Permohonan tidak dapat dilanjutkan. Baca pesan petugas pada percakapan di samping.',
    $statusId === 1 => 'Tiket tercatat di registri ULT. Petugas akan memeriksa kelengkapan berkas Anda.',
    default   => 'Berkas sedang diproses. Pantau riwayat dan percakapan untuk perkembangan terbaru.',
};
$labelStatusLive = $selesai ? 'Selesai' : ($ditolak ? 'Tidak dilanjutkan' : 'Dalam proses');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Lacak Tiket <?= esc($trackingId) ?> | E-ULT Universitas Mulawarman</title>
    <meta name="description" content="Pelacakan tiket layanan Unit Layanan Terpadu Universitas Mulawarman — status berkas, riwayat disposisi, dan percakapan dengan petugas.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
    <?= csrf_meta() ?>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700|Roboto:300,400,500,600,700&display=swap">
    <link href="<?= base_url(); ?>assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/skins/header/base/light.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/skins/header/menu/light.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/skins/brand/light.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/skins/aside/dark.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/star-rating.min.css" media="all" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/theme-rating.css" media="all" rel="stylesheet" type="text/css" />
    <link rel="shortcut icon" href="<?= base_url(); ?>assets/media/logos/favicon_unmul.ico" />

    <style {csp-style-nonce}>
        :root {
            --eult-brand: #5d78ff;
            --eult-ink: #1e1e2d;
            --eult-heading: #48465b;
            --eult-body: #646c9a;
            --eult-muted: #74788d;
            --eult-line: #ebedf2;
            --eult-input: #e2e5ec;
            --eult-canvas: #f2f3f8;
            --eult-card: #ffffff;
            --eult-success: #0abb87;
            --eult-warning: #ffb822;
            --eult-danger: #fd397a;
        }

        ::selection {
            background: rgba(93, 120, 255, 0.25);
            color: #1e1e2d;
        }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f2f3f8; }
        ::-webkit-scrollbar-thumb { background: #e2e5ec; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #74788d; }
        * { scrollbar-width: thin; scrollbar-color: #e2e5ec #f2f3f8; }

        a:focus-visible,
        button:focus-visible,
        .btn:focus-visible,
        textarea:focus-visible,
        input:focus-visible {
            outline: 2px solid #5d78ff;
            outline-offset: 2px;
        }

        html { box-sizing: border-box; }
        *, *::before, *::after { box-sizing: inherit; }

        body.eult-track {
            background-color: #f2f3f8;
            background-image: url('<?= base_url(); ?>assets/media/bg/bg-3.jpg');
            background-repeat: no-repeat;
            background-position: center top;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Poppins', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            color: #646c9a;
            caret-color: #5d78ff;
            overflow-x: hidden;
        }

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
        }

        .eult-brand-title {
            color: #1e1e2d;
            font-size: 1.2rem;
            font-weight: 600;
            line-height: 1.25;
            margin: 0;
        }

        .eult-brand-subtitle {
            color: var(--eult-muted);
            font-size: 12px;
            font-weight: 400;
            margin: 0;
        }

        .eult-stage {
            width: 100%;
            max-width: 1180px;
            margin: 1.5rem auto 2rem auto;
            padding: 0 15px;
        }

        .eult-portlet {
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);
            border: 1px solid #ebedf2;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .eult-portlet-head {
            padding: 1.15rem 1.5rem;
            border-bottom: 1px solid #ebedf2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafbfe;
            min-height: 60px;
            gap: 10px;
        }

        .eult-portlet-head-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
            color: #48465b;
            display: flex;
            align-items: center;
            line-height: 1.3;
        }

        .eult-portlet-head-title i {
            font-size: 1.2rem;
            margin-right: 10px;
            color: #5d78ff;
        }

        .eult-portlet-body { padding: 1.5rem; }
        .eult-portlet-foot {
            padding: 1rem 1.5rem;
            background: #fafbfe;
            border-top: 1px solid #ebedf2;
        }

        /* Bukti nomor tiket — momen utama halaman */
        .eult-credential {
            background: #ffffff;
            border: 1px solid #ebedf2;
            border-radius: 4px;
            box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);
            padding: 1.5rem 1.75rem;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.25rem 1.5rem;
            align-items: center;
        }

        .eult-credential__meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 14px;
            color: var(--eult-muted);
            font-size: 13px;
            font-weight: 400;
            margin-top: 0.65rem;
        }

        .eult-credential__meta i { color: #5d78ff; margin-right: 4px; }

        .eult-ticket-id {
            font-family: Roboto, ui-monospace, monospace;
            font-size: 2rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: #5d78ff;
            line-height: 1.2;
            margin: 0;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .eult-ticket-id.is-copied {
            color: #0abb87;
            text-shadow: 0 1px 0 rgba(10, 187, 135, 0.18);
        }

        @media (prefers-reduced-motion: no-preference) {
            .eult-ticket-id {
                animation: eult-stamp 640ms cubic-bezier(0.16, 1, 0.3, 1) 80ms both;
            }
        }

        @keyframes eult-stamp {
            0% { clip-path: inset(0 100% 0 0); filter: blur(4px); }
            70% { clip-path: inset(0 0 0 0); filter: blur(0); }
            100% { clip-path: inset(0 0 0 0); filter: none; }
        }

        .eult-copy-btn {
            border: 1px solid var(--eult-input);
            background: #f4f5f8;
            color: #48465b;
            border-radius: 4px;
            padding: 0.35rem 0.7rem;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: background 150ms ease, border-color 150ms ease, color 150ms ease;
        }

        .eult-copy-btn:hover {
            background: #ffffff;
            border-color: #5d78ff;
            color: #5d78ff;
        }

        .eult-copy-btn.is-copied {
            background: rgba(10, 187, 135, 0.12);
            border-color: rgba(10, 187, 135, 0.35);
            color: #0abb87;
        }

        .eult-hint {
            margin: 0.85rem 0 0;
            font-size: 13px;
            font-weight: 400;
            color: #646c9a;
            line-height: 1.5;
            max-width: 62ch;
        }

        .eult-credential__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .eult-pulse {
            position: relative;
        }

        .eult-pulse::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            display: inline-block;
            margin-right: 6px;
            box-shadow: 0 0 0 0 currentColor;
            animation: eult-pulse-dot 1.8s ease-out infinite;
            vertical-align: middle;
        }

        @keyframes eult-pulse-dot {
            0% { box-shadow: 0 0 0 0 currentColor; opacity: 0.85; }
            70% { box-shadow: 0 0 0 6px transparent; opacity: 1; }
            100% { box-shadow: 0 0 0 0 transparent; opacity: 0.85; }
        }

        .eult-field {
            margin-bottom: 1.1rem;
        }

        .eult-field:last-child { margin-bottom: 0; }

        .eult-label {
            font-size: 12px;
            font-weight: 500;
            color: #48465b;
            margin-bottom: 0.35rem;
            display: block;
        }

        .eult-value {
            font-size: 13px;
            font-weight: 400;
            color: #48465b;
            margin: 0;
            line-height: 1.5;
        }

        .eult-ikm-note {
            display: block;
            margin-top: 0.4rem;
            font-size: 12px;
            color: #734c00;
            background: rgba(255, 184, 34, 0.12);
            border: 1px solid rgba(255, 184, 34, 0.3);
            border-radius: 4px;
            padding: 0.65rem 0.85rem;
        }

        .eult-timeline {
            position: relative;
            padding-left: 22px;
        }

        .eult-timeline::before {
            content: '';
            position: absolute;
            left: 5px;
            top: 8px;
            bottom: 8px;
            width: 1px;
            background: #ebedf2;
        }

        .eult-timeline__item {
            position: relative;
            padding-bottom: 1.15rem;
        }

        .eult-timeline__item:last-child { padding-bottom: 0; }

        .eult-timeline__item::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 6px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #5d78ff;
            box-shadow: 0 1px 4px rgba(93, 120, 255, 0.25);
        }

        .eult-timeline__item:first-child::before {
            background: #5d78ff;
        }

        .eult-timeline__time {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #48465b;
            margin-bottom: 0.2rem;
        }

        .eult-timeline__body {
            font-size: 13px;
            font-weight: 300;
            color: #646c9a;
            line-height: 1.5;
        }

        .eult-empty {
            text-align: center;
            padding: 1.25rem 0.5rem;
            color: var(--eult-muted);
            font-size: 13px;
        }

        .eult-empty i {
            display: block;
            font-size: 1.5rem;
            color: #5d78ff;
            margin-bottom: 0.5rem;
        }

        .eult-chat-head {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .eult-chat-head__name {
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
            color: #48465b;
        }

        .eult-chat-messages {
            min-height: 250px;
            max-height: 380px;
            overflow-y: auto;
            padding-right: 6px;
        }

        .eult-bubble {
            display: flex;
            margin-bottom: 1rem;
            max-width: 88%;
        }

        .eult-bubble--user {
            margin-left: auto;
            flex-direction: row-reverse;
        }

        .eult-bubble__avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .eult-bubble__stack {
            margin: 0 10px;
        }

        .eult-bubble__meta {
            font-size: 11px;
            color: var(--eult-muted);
            margin-bottom: 4px;
            display: flex;
            gap: 8px;
            align-items: baseline;
        }

        .eult-bubble--user .eult-bubble__meta { justify-content: flex-end; }

        .eult-bubble__text {
            border-radius: 4px;
            padding: 0.75rem 1rem;
            font-size: 13px;
            font-weight: 300;
            line-height: 1.5;
            color: #48465b;
        }

        .eult-bubble--staff .eult-bubble__text {
            background: rgba(10, 187, 135, 0.1);
        }

        .eult-bubble--user .eult-bubble__text {
            background: rgba(93, 120, 255, 0.1);
        }

        .eult-attach {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .eult-composer textarea {
            width: 100%;
            min-height: 72px;
            resize: vertical;
            border: 1px solid var(--eult-input);
            border-radius: 4px;
            padding: 0.65rem 1rem;
            font-size: 13px;
            font-family: inherit;
            color: #48465b;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .eult-composer textarea:focus {
            border-color: #9aabff;
            box-shadow: 0 0 0 0.2rem rgba(88, 103, 221, 0.25);
            outline: none;
        }

        .eult-composer textarea::placeholder { color: var(--eult-muted); }

        .eult-filepick {
            margin-top: 10px;
            border: 1px dashed var(--eult-input);
            border-radius: 4px;
            background: #fbfcfe;
            padding: 0.55rem 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }

        .eult-filepick:hover,
        .eult-filepick:focus-within {
            border-color: #5d78ff;
            background: #f8faff;
        }

        .eult-filepick input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            overflow: hidden;
        }

        .eult-filepick__name {
            font-size: 12px;
            color: #646c9a;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .eult-filepick.has-file .eult-filepick__name { color: #48465b; font-weight: 500; }

        .eult-composer-bar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .eult-help-card {
            background: #ffffff;
            border: 1px solid rgba(10, 187, 135, 0.25);
            border-radius: 8px;
            padding: 1.2rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.5rem;
            box-shadow: 0 2px 12px rgba(10, 187, 135, 0.08);
            flex-wrap: wrap;
            gap: 12px;
        }

        .eult-help-content { display: flex; align-items: center; }
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
        .eult-help-text h2 {
            margin: 0 0 3px 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: #282a3c;
        }
        .eult-help-text p { margin: 0; font-size: 12px; color: var(--eult-muted); }

        .eult-footer {
            padding: 1.75rem 0;
            text-align: center;
            color: var(--eult-muted);
            font-size: 12px;
            border-top: 1px solid rgba(235, 237, 242, 0.85);
            background: #ffffff;
            margin-top: 2rem;
            padding-bottom: max(1.75rem, env(safe-area-inset-bottom));
        }

        .eult-toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(12px);
            background: #1e1e2d;
            color: #ffffff;
            font-size: 13px;
            font-weight: 500;
            padding: 0.7rem 1.1rem;
            border-radius: 4px;
            box-shadow: 0px 0px 50px 0px rgba(82, 63, 105, 0.15);
            opacity: 0;
            pointer-events: none;
            z-index: 40;
            transition: opacity 180ms ease, transform 180ms cubic-bezier(0.16, 1, 0.3, 1);
        }

        .eult-toast.is-on {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @media (max-width: 991px) {
            .eult-credential {
                grid-template-columns: 1fr;
            }
            .eult-credential__actions { justify-content: flex-start; }
            .eult-ticket-id { font-size: 1.5rem; }
        }

        @media (max-width: 767px) {
            .eult-brand-title { font-size: 1rem; }
            .eult-brand-logo { height: 36px; }
            .eult-portlet-body,
            .eult-portlet-head,
            .eult-credential { padding: 1.1rem; }
            .eult-composer textarea,
            .form-control { font-size: 16px !important; }
            .eult-help-card { flex-direction: column; align-items: stretch; }
            .eult-help-card .btn { width: 100%; min-height: 44px; }
            .eult-composer-bar .btn { min-height: 44px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .eult-ticket-id {
                animation: none;
                clip-path: none;
            }
            .eult-pulse::before { animation: none; }
            .eult-toast { transition: opacity 120ms ease; }
        }
    </style>
</head>

<body class="eult-track">

    <header class="eult-navbar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <a href="<?= base_url(); ?>" class="d-flex align-items-center text-decoration-none">
                    <img src="<?= base_url(); ?>assets/media/logos/logo-ult12.png" alt="Logo E-ULT UNMUL" class="eult-brand-logo mr-2 mr-sm-3">
                    <div>
                        <p class="eult-brand-title">E-ULT UNIVERSITAS MULAWARMAN</p>
                        <p class="eult-brand-subtitle">Elektronik Unit Layanan Terpadu • Pelacakan Tiket</p>
                    </div>
                </a>
                <a href="<?= base_url(); ?>" class="btn btn-outline-brand btn-sm btn-pill">
                    <i class="flaticon2-left-arrow-1 mr-1"></i> Portal
                </a>
            </div>
        </div>
    </header>

    <main class="eult-stage">
        <section class="eult-credential" aria-label="Bukti nomor tiket">
            <div>
                <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                    <h1 id="ticketId" class="eult-ticket-id"><?= esc($trackingId) ?></h1>
                    <button type="button" class="eult-copy-btn" id="eult-copy-ticket" data-ticket="<?= esc($trackingId, 'attr') ?>" aria-label="Salin nomor tiket">
                        <i class="la la-copy"></i> <span>Salin</span>
                    </button>
                    <span class="kt-badge kt-badge--unified-<?= esc($statusColor) ?> kt-badge--inline kt-badge--pill kt-badge--rounded font-weight-bold <?= $berproses ? 'eult-pulse' : '' ?>">
                        <?= esc($statusNama) ?>
                    </span>
                </div>
                <div class="eult-credential__meta">
                    <span><i class="la la-user"></i><?= esc($namaPemohon) ?></span>
                    <?php if ($layananUtama !== '' || $layananUnit !== ''): ?>
                        <span><i class="la la-bookmark"></i><?= esc(trim($layananUtama . ($layananUnit !== '' ? ' — ' . $layananUnit : ''))) ?></span>
                    <?php endif; ?>
                    <?php if ($tglTiket !== ''): ?>
                        <span><i class="la la-calendar"></i><?= esc($tglTiket) ?></span>
                    <?php endif; ?>
                </div>
                <p class="eult-hint"><?= esc($hintStatus) ?></p>
            </div>
            <div class="eult-credential__actions">
                <?php if (! empty($cetakTerima) && $cetakTerima !== '#'): ?>
                    <a href="<?= esc($cetakTerima) ?>" class="btn btn-secondary btn-sm font-weight-bold" target="_blank" rel="noopener">
                        <i class="la la-print mr-1"></i> Cetak Bukti
                    </a>
                <?php endif; ?>
                <?php if ($outputUrl !== false): ?>
                    <a href="<?= esc($outputUrl) ?>" class="btn btn-warning btn-sm font-weight-bold">
                        <i class="la la-file mr-1"></i> Unduh Output
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <div class="row">
            <div class="col-lg-5">
                <article class="eult-portlet">
                    <div class="eult-portlet-head">
                        <h2 class="eult-portlet-head-title"><i class="flaticon-file-2"></i> Detail Tiket</h2>
                    </div>
                    <div class="eult-portlet-body">
                        <div class="eult-field">
                            <span class="eult-label">Nomor Tiket</span>
                            <p class="eult-value" style="font-family: Roboto, ui-monospace, monospace; letter-spacing: 0.04em;"><?= esc($trackingId) ?></p>
                        </div>
                        <div class="eult-field">
                            <span class="eult-label">Layanan</span>
                            <p class="eult-value"><?= esc(trim($layananUtama . ($layananUnit !== '' ? ' — ' . $layananUnit : '')) ?: '—') ?></p>
                        </div>
                        <div class="eult-field">
                            <span class="eult-label">Tanggal Tiket</span>
                            <p class="eult-value"><?= esc($tglTiket !== '' ? $tglTiket : '—') ?></p>
                        </div>
                        <div class="eult-field">
                            <span class="eult-label">Status Pekerjaan</span>
                            <p class="mb-0">
                                <span class="kt-badge kt-badge--<?= esc($statusColor) ?> kt-badge--inline kt-badge--pill kt-badge--rounded"><?= esc($statusNama) ?></span>
                            </p>
                        </div>

                        <?php if ($selesai): ?>
                            <div class="eult-field mt-3 pt-3" style="border-top: 1px solid #ebedf2;">
                                <span class="eult-label">Indeks Kepuasan Masyarakat</span>
                                <input id="input-id" type="text" class="kv-uni-star rating-loading" value="<?= esc((string) $ratingNilai) ?>" <?= $sudahRating ? 'disabled' : '' ?>>
                                <?php if (! $sudahRating): ?>
                                    <span class="eult-ikm-note">Isi penilaian ini agar berkas resmi dikirim ke email Anda. Penilaian tidak dapat diubah setelah terkirim.</span>
                                <?php else: ?>
                                    <span class="form-text text-muted small mt-2">Terima kasih. Penilaian Anda telah tercatat.</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($outputUrl !== false): ?>
                        <div class="eult-portlet-foot">
                            <a href="<?= esc($outputUrl) ?>" class="btn btn-warning"><i class="la la-file"></i> Output</a>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="eult-portlet">
                    <div class="eult-portlet-head">
                        <h2 class="eult-portlet-head-title"><i class="flaticon-list-3"></i> Riwayat Tiket</h2>
                    </div>
                    <div class="eult-portlet-body">
                        <?php if (! empty($historyItems)): ?>
                            <ol class="eult-timeline list-unstyled mb-0">
                                <?php foreach ($historyItems as $value): ?>
                                    <?php
                                    $tglH = (string) ($value['tglHistory'] ?? '');
                                    $tglHIndo = $tglH !== '' && function_exists('eult_tanggal_indo')
                                        ? eult_tanggal_indo(substr($tglH, 0, 10))
                                        : '';
                                    $jamH = $tglH !== '' ? date('H:i', strtotime($tglH)) : '';
                                    ?>
                                    <li class="eult-timeline__item">
                                        <time class="eult-timeline__time" datetime="<?= esc($tglH, 'attr') ?>">
                                            <?= esc(($tglHIndo !== false && $tglHIndo !== '' ? $tglHIndo : substr($tglH, 0, 10)) . ($jamH !== '' ? ' ' . $jamH . ' WITA' : '')) ?>
                                        </time>
                                        <div class="eult-timeline__body"><?= esc($value['detailHistory'] ?? '') ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <div class="eult-empty">
                                <i class="flaticon-time-2"></i>
                                Riwayat belum tersedia. Status akan muncul di sini setelah petugas memproses tiket.
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            </div>

            <div class="col-lg-7">
                <section class="eult-portlet" aria-label="Percakapan dengan petugas">
                    <div class="eult-portlet-head">
                        <div class="eult-chat-head">
                            <span class="kt-badge kt-badge--username kt-badge--unified-success kt-badge--lg kt-badge--rounded kt-badge--bold"><?= esc($inisial) ?></span>
                            <div>
                                <h2 class="eult-chat-head__name"><?= esc($namaPemohon) ?></h2>
                                <span class="kt-badge kt-badge--dot kt-badge--<?= $berproses ? 'success' : ($selesai ? 'brand' : 'danger') ?>"></span>
                                <span class="text-muted" style="font-size: 12px;"><?= esc($labelStatusLive) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="eult-portlet-body">
                        <div class="eult-chat-messages" id="eult-chat-scroll">
                            <?php if (! empty($replyItems)): ?>
                                <?php foreach ($replyItems as $value): ?>
                                    <?php
                                    $dariUser = ($value['repliesStatus'] ?? '') === 'USER';
                                    $pesan    = (string) ($value['repliesMessage'] ?? '');
                                    $berkas   = (string) ($value['repliesFile'] ?? '');
                                    $kapan    = $waktuLalu((string) ($value['repliesDate'] ?? ''));
                                    $pengirim = $dariUser ? 'Anda' : (string) ($value['repliesBy'] ?? 'Petugas ULT');
                                    $avatar   = $dariUser ? base_url('assets/media/users/user.jpg') : base_url('assets/media/users/admin.jpg');
                                    ?>
                                    <div class="eult-bubble <?= $dariUser ? 'eult-bubble--user' : 'eult-bubble--staff' ?>">
                                        <img class="eult-bubble__avatar" src="<?= esc($avatar) ?>" alt="">
                                        <div class="eult-bubble__stack">
                                            <div class="eult-bubble__meta">
                                                <strong><?= esc($pengirim) ?></strong>
                                                <?php if ($kapan !== ''): ?><span><?= esc($kapan) ?></span><?php endif; ?>
                                            </div>
                                            <div class="eult-bubble__text">
                                                <?= nl2br(esc($pesan)) ?>
                                                <?php if ($berkas !== ''): ?>
                                                    <br>
                                                    <a class="eult-attach" href="<?= esc($loadAttach) ?>/<?= esc($berkas) ?>">
                                                        <i class="flaticon-attachment"></i> <?= esc($berkas) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="eult-empty">
                                    <i class="flaticon-chat-1"></i>
                                    Belum ada percakapan. Kirim pesan atau lampiran jika petugas memerlukan keterangan tambahan.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="eult-portlet-foot">
                        <form class="eult-composer" action="<?= esc($saveUrl) ?>" method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="kunci" value="<?= esc($kunciTiket, 'attr') ?>">
                            <input type="hidden" name="repliesTicketId" value="<?= esc($trackingId, 'attr') ?>">
                            <label class="sr-only" for="repliesMessage">Pesan balasan</label>
                            <textarea id="repliesMessage" name="repliesMessage" placeholder="Tulis pesan untuk petugas ULT..." required></textarea>
                            <label class="eult-filepick" id="eult-filepick">
                                <input type="file" name="chatFile" id="customFile" accept=".pdf,application/pdf">
                                <i class="flaticon-attachment text-primary"></i>
                                <span class="eult-filepick__name" id="eult-file-label">Lampirkan PDF (opsional, maks. 15 MB)</span>
                            </label>
                            <div class="eult-composer-bar">
                                <button type="submit" class="btn btn-brand btn-sm btn-bold">Kirim Pesan</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>

        <div class="eult-help-card">
            <div class="eult-help-content">
                <div class="eult-help-icon"><i class="fab fa-whatsapp"></i></div>
                <div class="eult-help-text">
                    <h2>Butuh bantuan terkait tiket ini?</h2>
                    <p>Helpdesk ULT Universitas Mulawarman siap membantu pada jam kerja operasional.</p>
                </div>
            </div>
            <a href="https://wa.me/628115809970" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-elevate font-weight-bold px-4 py-2" style="background-color: #0abb87; border-color: #0abb87;">
                <i class="fab fa-whatsapp mr-1"></i> Chat WhatsApp ULT
            </a>
        </div>
    </main>

    <footer class="eult-footer">
        <div class="container">
            <strong>&copy; <?= date('Y'); ?> Universitas Mulawarman</strong> — Unit Layanan Terpadu (E-ULT v2).
        </div>
    </footer>

    <div class="eult-toast" id="eult-toast" role="status" aria-live="polite"></div>

    <script {csp-script-nonce}>
        var KTAppOptions = {
            "colors": {
                "state": {
                    "brand": "#5d78ff",
                    "dark": "#282a3c",
                    "light": "#ffffff",
                    "primary": "#5867dd",
                    "success": "#34bfa3",
                    "info": "#36a3f7",
                    "warning": "#ffb822",
                    "danger": "#fd3995"
                },
                "base": {
                    "label": ["#c5cbe3", "#a1a8c3", "#3d4465", "#3e4466"],
                    "shape": ["#f0f3ff", "#d9dffa", "#afb4d4", "#646c9a"]
                }
            }
        };
    </script>
    <script src="<?= base_url(); ?>assets/plugins/global/plugins.bundle.js" type="text/javascript"></script>
    <script src="<?= base_url(); ?>assets/js/scripts.bundle.js" type="text/javascript"></script>
    <script src="<?= base_url(); ?>assets/js/star-rating.min.js" type="text/javascript"></script>
    <script src="<?= base_url(); ?>assets/js/themes-rating.js"></script>
    <script type="text/javascript" {csp-script-nonce}>
        const KTTicketing = function() {
            const tampilkanToast = (teks) => {
                const el = document.getElementById('eult-toast');
                if (!el) return;
                el.textContent = teks;
                el.classList.add('is-on');
                window.setTimeout(() => el.classList.remove('is-on'), 2200);
            };

            const initHandleWidgets = () => {
                $('.kv-uni-star').rating({
                    theme: 'krajee-uni',
                    filledStar: '&#x2605;',
                    emptyStar: '&#x2606;'
                });
            };

            const initHandleShow = () => {
                $('.kv-uni-star').on('change', function() {
                    $.ajax({
                        type: 'POST',
                        url: '<?= esc($ratingUrl, 'attr') ?>',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="<?= esc(csrf_header(), 'attr') ?>"]').attr('content')
                        },
                        data: {
                            rating: $(this).val()
                        },
                        success: (response, status, xhr) => {
                            // Token CSRF diregenerasi server setiap submit sukses
                            // ($regenerate = true) — perbarui meta tag agar
                            // percobaan submit berikutnya pada halaman yang
                            // sama (tanpa reload) tidak memakai token basi.
                            const tokenBaru = xhr.getResponseHeader('X-CSRF-TOKEN');
                            if (tokenBaru) {
                                $('meta[name="<?= esc(csrf_header(), 'attr') ?>"]').attr('content', tokenBaru);
                            }

                            if (typeof swal !== 'undefined' && swal.fire) {
                                swal.fire({
                                    title: "Indeks Kepuasan Masyarakat",
                                    text: 'Terima kasih telah mengisi IKM. Untuk layanan dengan permintaan berkas, berkas telah kami kirimkan via email. Mohon periksa email Anda.',
                                    type: 'success'
                                }).then(function() {
                                    location.reload();
                                });
                            } else {
                                tampilkanToast('Terima kasih. Penilaian Anda telah tercatat.');
                                window.setTimeout(() => location.reload(), 1200);
                            }
                        },
                        error: () => {
                            tampilkanToast('Gagal menyimpan penilaian. Silakan coba kembali.');
                        }
                    });
                });
            };

            const initCopyTicket = () => {
                const tombol = document.getElementById('eult-copy-ticket');
                const nomor = document.getElementById('ticketId');
                if (!tombol || !nomor) return;

                tombol.addEventListener('click', async () => {
                    const nilai = tombol.getAttribute('data-ticket') || nomor.textContent.trim();
                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(nilai);
                        } else {
                            const area = document.createElement('textarea');
                            area.value = nilai;
                            document.body.appendChild(area);
                            area.select();
                            document.execCommand('copy');
                            document.body.removeChild(area);
                        }
                        tombol.classList.add('is-copied');
                        tombol.querySelector('span').textContent = 'Tersalin';
                        nomor.classList.add('is-copied');
                        tampilkanToast('Nomor tiket ' + nilai + ' disalin — simpan sebagai bukti pelacakan.');
                        window.setTimeout(() => {
                            tombol.classList.remove('is-copied');
                            tombol.querySelector('span').textContent = 'Salin';
                            nomor.classList.remove('is-copied');
                        }, 1800);
                    } catch (e) {
                        tampilkanToast('Tidak dapat menyalin. Salin nomor tiket secara manual.');
                    }
                });
            };

            const initFilePick = () => {
                const input = document.getElementById('customFile');
                const label = document.getElementById('eult-file-label');
                const wrap = document.getElementById('eult-filepick');
                if (!input || !label || !wrap) return;
                input.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        label.textContent = this.files[0].name;
                        wrap.classList.add('has-file');
                    } else {
                        label.textContent = 'Lampirkan PDF (opsional, maks. 15 MB)';
                        wrap.classList.remove('has-file');
                    }
                });
            };

            const initChatScroll = () => {
                const kotak = document.getElementById('eult-chat-scroll');
                if (kotak) kotak.scrollTop = kotak.scrollHeight;
            };

            return {
                init: function() {
                    initHandleWidgets();
                    initHandleShow();
                    initCopyTicket();
                    initFilePick();
                    initChatScroll();
                }
            };
        }();

        KTUtil.ready(function() {
            KTTicketing.init();
        });
    </script>
</body>

</html>
