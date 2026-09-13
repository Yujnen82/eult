# Admin UI/UX Modernization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transform and standardize all E-ULT v2 admin surfaces into an operational, scannable, and cohesive Civic Academic Registry interface using Metronic v6 Bootstrap 4 components.

**Architecture:** Implement the verified design brief by creating a high-impact operational hybrid command dashboard on `/home`, modernizing ticket queue filters and scannable row actions on `/ticketing`, restructuring the two-column inspection and verification workstation on `/ticketing/detail`, polishing file validation and quarantine views on `/validasifile`, and unifying all master data and RBAC tables and forms with consistent styling, badges, and feedback dialogs.

**Tech Stack:** CodeIgniter 4 (PHP ^8.2), Metronic v6 Admin Template (Bootstrap 4 & KeenThemes KT framework), jQuery, DataTables, Select2, SweetAlert2, Bootstrap Daterangepicker, PHPUnit 10.5.

**Spec:** [docs/superpowers/specs/2026-09-13-admin-ui-ux-shape.md](file:///home/development/project/eultv2/docs/superpowers/specs/2026-09-13-admin-ui-ux-shape.md)

## Global Constraints

- Design System: Follow [DESIGN.md](file:///home/development/project/eultv2/DESIGN.md) — primary `#5d78ff`, dark brand `#1a1a27` / `#1e1e2d`, canvas `#f2f3f8`, portlet padding 25px, radius 4px (`.kt-portlet`), unified status badges.
- Assets: Use existing Metronic v6 bundles in `public/assets/` without introducing conflicting third-party CSS or JS packages.
- Localization: All UI labels, badges, alerts, and instructions must strictly be in formal Indonesian (Bahasa Indonesia).
- Tests: Every task must have a corresponding test verified with `vendor/bin/phpunit`.

---

### Task 1: Baseline Verification & Test Suite Setup for Admin Views

**Files:**
- Create: `tests/unit/AdminViewsTest.php`
- Modify: None
- Test: `tests/unit/AdminViewsTest.php`

**Interfaces:**
- Consumes: CodeIgniter 4 Test Framework (`CodeIgniter\Test\CIUnitTestCase`)
- Produces: Base assertions for checking admin view rendering, titles, and layout components.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pengujian dasar kesiapan view admin E-ULT v2.
 */
class AdminViewsTest extends CIUnitTestCase
{
    public function testHomeIndexViewExistsAndRenders(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Dashboard',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'total_masuk' => 12,
            'total_proses' => 5,
            'total_selesai' => 20,
            'total_tindakan' => 3,
            'tiket_urgent' => [],
            'tren_mingguan' => [],
        ])->render('pages/home/index');

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('kt-portlet', $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: FAIL because `pages/home/index` does not yet contain `kt-portlet`.

- [ ] **Step 3: Write minimal implementation to establish view structure**

Update `app/Views/pages/home/index.php` to include minimal Metronic portlet container:

```php
<?= $this->include('layouts/subheader') ?>

<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-12">
            <div class="kt-portlet">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <h3 class="kt-portlet__head-title">Dashboard Layanan</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: PASS with 1 test, 2 assertions.

- [ ] **Step 5: Commit**

```bash
git add tests/unit/AdminViewsTest.php app/Views/pages/home/index.php
git commit -m "test: setup admin views test baseline and initial home portlet"
```

---

### Task 2: Implement Hybrid Command Dashboard (`/home`)

**Files:**
- Create: `app/Views/pages/home/dashboard_kpi.php`
- Create: `app/Views/pages/home/dashboard_urgent.php`
- Modify: `app/Controllers/Home.php:20-60`
- Modify: `app/Views/pages/home/index.php:1-50`
- Test: `tests/unit/AdminViewsTest.php`

**Interfaces:**
- Consumes: `Home::index` sending KPI aggregates (`total_masuk`, `total_proses`, `total_selesai`, `total_tindakan`, `tiket_urgent`, `tren_mingguan`)
- Produces: Hybrid operational dashboard with 4 metric cards, trend charts container, and urgent actionable ticket table.

- [ ] **Step 1: Write the failing test for dashboard KPI components**

Add test to `tests/unit/AdminViewsTest.php`:

```php
    public function testDashboardRendersKpiCardsAndUrgentTable(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Dashboard Layanan',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'total_masuk' => 12,
            'total_proses' => 5,
            'total_selesai' => 20,
            'total_tindakan' => 3,
            'tiket_urgent' => [
                [
                    'ticketTrackingId' => 'TK-2026-001',
                    'ticketName' => 'Budi Santoso',
                    'unitNama' => 'Fakultas Teknik',
                    'statusNama' => 'Menunggu Disposisi',
                    'statusColor' => 'warning',
                    'ticketCreated' => '2026-09-13 10:00:00',
                ]
            ],
            'tren_mingguan' => [
                'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'],
                'data' => [10, 15, 8, 20, 12]
            ],
        ])->render('pages/home/index');

        $this->assertStringContainsString('Tiket Baru Masuk', $html);
        $this->assertStringContainsString('Perlu Tindakan', $html);
        $this->assertStringContainsString('Tiket Dalam Proses', $html);
        $this->assertStringContainsString('Tiket Selesai', $html);
        $this->assertStringContainsString('TK-2026-001', $html);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: FAIL because KPI titles and table are missing.

- [ ] **Step 3: Implement Dashboard KPI and Urgent Ticket views and Controller data provider**

Modify `app/Controllers/Home.php` to fetch and pass summary stats to the view:
```php
        // Ambil data agregat tiket untuk KPI dashboard
        $db = \Config\Database::connect();
        $totalMasuk = $db->table('ticket')->where('ticketStatus', 1)->countAllResults();
        $totalProses = $db->table('ticket')->whereIn('ticketStatus', [2, 3, 4])->countAllResults();
        $totalSelesai = $db->table('ticket')->where('ticketStatus', 5)->countAllResults();
        $totalTindakan = $db->table('ticket')->whereIn('ticketStatus', [1, 2])->countAllResults();

        $urgentTickets = $db->table('ticket')
            ->select('ticketTrackingId, ticketName, ticketCreated, ticketStatus, unit.unitNama, ticket_status.statusNama, ticket_status.statusColor')
            ->join('unit', 'unit.unitId = ticket.ticketAssign', 'left')
            ->join('ticket_status', 'ticket_status.statusId = ticket.ticketStatus', 'left')
            ->whereIn('ticketStatus', [1, 2])
            ->orderBy('ticketCreated', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        $data['total_masuk'] = $totalMasuk;
        $data['total_proses'] = $totalProses;
        $data['total_selesai'] = $totalSelesai;
        $data['total_tindakan'] = $totalTindakan;
        $data['tiket_urgent'] = $urgentTickets;
```

Update `app/Views/pages/home/index.php` with 4 KPI cards, quick actions, and urgent ticket table:
```php
<?= $this->include('layouts/subheader') ?>

<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
    <!-- Baris 1: 4 Kartu KPI Metrik Operasional -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="kt-portlet kt-portlet--fit kt-portlet--head-noborder" style="border-left: 4px solid #5d78ff; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);">
                <div class="kt-portlet__body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">Tiket Baru Masuk</span>
                            <h2 class="font-weight-bold my-2" style="color: #1e1e2d; font-size: 28px;"><?= $total_masuk ?? 0 ?></h2>
                            <span class="text-muted" style="font-size: 12px;"><i class="flaticon2-incoming text-primary mr-1"></i>Menunggu respons awal</span>
                        </div>
                        <div class="kt-badge kt-badge--unified-brand kt-badge--lg kt-badge--rounded" style="width: 50px; height: 50px; border-radius: 8px;">
                            <i class="flaticon2-mail" style="font-size: 22px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="kt-portlet kt-portlet--fit kt-portlet--head-noborder" style="border-left: 4px solid #ffb822; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);">
                <div class="kt-portlet__body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">Perlu Tindakan</span>
                            <h2 class="font-weight-bold my-2" style="color: #ffb822; font-size: 28px;"><?= $total_tindakan ?? 0 ?></h2>
                            <span class="text-muted" style="font-size: 12px;"><i class="flaticon2-hourglass-1 text-warning mr-1"></i>Butuh disposisi / verifikasi</span>
                        </div>
                        <div class="kt-badge kt-badge--unified-warning kt-badge--lg kt-badge--rounded" style="width: 50px; height: 50px; border-radius: 8px;">
                            <i class="flaticon-alarm" style="font-size: 22px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="kt-portlet kt-portlet--fit kt-portlet--head-noborder" style="border-left: 4px solid #5578eb; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);">
                <div class="kt-portlet__body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">Tiket Dalam Proses</span>
                            <h2 class="font-weight-bold my-2" style="color: #5578eb; font-size: 28px;"><?= $total_proses ?? 0 ?></h2>
                            <span class="text-muted" style="font-size: 12px;"><i class="flaticon2-reload text-info mr-1"></i>Sedang dikerjakan unit</span>
                        </div>
                        <div class="kt-badge kt-badge--unified-info kt-badge--lg kt-badge--rounded" style="width: 50px; height: 50px; border-radius: 8px;">
                            <i class="flaticon2-document" style="font-size: 22px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="kt-portlet kt-portlet--fit kt-portlet--head-noborder" style="border-left: 4px solid #0abb87; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);">
                <div class="kt-portlet__body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">Tiket Selesai</span>
                            <h2 class="font-weight-bold my-2" style="color: #0abb87; font-size: 28px;"><?= $total_selesai ?? 0 ?></h2>
                            <span class="text-muted" style="font-size: 12px;"><i class="flaticon2-check-mark text-success mr-1"></i>Dokumen diterbitkan</span>
                        </div>
                        <div class="kt-badge kt-badge--unified-success kt-badge--lg kt-badge--rounded" style="width: 50px; height: 50px; border-radius: 8px;">
                            <i class="flaticon2-correct" style="font-size: 22px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Baris 2: Antrean Tindakan Mendesak & Pintasan Cepat -->
    <div class="row">
        <div class="col-xl-8 col-lg-12">
            <div class="kt-portlet kt-portlet--mobile">
                <div class="kt-portlet__head kt-portlet__head--lg">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon"><i class="flaticon2-time text-warning"></i></span>
                        <h3 class="kt-portlet__head-title font-weight-bold">Antrean Tiket Memerlukan Tindakan Segera</h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <a href="<?= base_url('ticketing') ?>" class="btn btn-sm btn-outline-brand font-weight-bold">
                            Lihat Semua Tiket <i class="la la-angle-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <div class="kt-portlet__body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="font-size: 12px;">Nomor Tiket</th>
                                    <th style="font-size: 12px;">Pemohon</th>
                                    <th style="font-size: 12px;">Unit Kerja</th>
                                    <th style="font-size: 12px;">Status</th>
                                    <th style="font-size: 12px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tiket_urgent)): ?>
                                    <?php foreach ($tiket_urgent as $t): ?>
                                        <tr>
                                            <td class="font-weight-bold" style="font-family: monospace; font-size: 13px; color: #5d78ff;">
                                                <?= esc($t['ticketTrackingId']) ?>
                                            </td>
                                            <td style="font-size: 13px;">
                                                <div class="font-weight-bold"><?= esc($t['ticketName']) ?></div>
                                                <span class="text-muted" style="font-size: 11px;"><?= esc($t['ticketCreated']) ?></span>
                                            </td>
                                            <td style="font-size: 13px;"><?= esc($t['unitNama'] ?? 'Pusat ULT') ?></td>
                                            <td>
                                                <span class="kt-badge kt-badge--unified-<?= esc($t['statusColor'] ?? 'warning') ?> kt-badge--inline kt-badge--pill font-weight-bold">
                                                    <?= esc($t['statusNama']) ?>
                                                </span>
                                            </td>
                                            <td align="center">
                                                <a href="<?= base_url('ticketing/detail/' . service('enkripsi')->encode($t['ticketTrackingId'])) ?>" class="btn btn-sm btn-label-brand btn-icon" title="Periksa Tiket">
                                                    <i class="flaticon-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="flaticon2-checkmark text-success mr-2"></i>Semua antrean tiket saat ini telah tertangani dengan baik.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-12">
            <div class="kt-portlet">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon"><i class="flaticon2-layers-1 text-primary"></i></span>
                        <h3 class="kt-portlet__head-title font-weight-bold">Pintasan Layanan</h3>
                    </div>
                </div>
                <div class="kt-portlet__body">
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= base_url('ticketing') ?>" class="btn btn-outline-secondary text-left d-flex align-items-center justify-content-between p-3 mb-2" style="border-radius: 6px;">
                            <div>
                                <div class="font-weight-bold text-dark">Daftar Antrean Tiketing</div>
                                <small class="text-muted">Kelola disposisi & verifikasi tiket masuk</small>
                            </div>
                            <i class="flaticon2-right-arrow text-primary"></i>
                        </a>
                        <a href="<?= base_url('validasifile') ?>" class="btn btn-outline-secondary text-left d-flex align-items-center justify-content-between p-3 mb-2" style="border-radius: 6px;">
                            <div>
                                <div class="font-weight-bold text-dark">Validasi & Keamanan Berkas</div>
                                <small class="text-muted">Pindai integritas dan status karantina file</small>
                            </div>
                            <i class="flaticon2-shield text-warning"></i>
                        </a>
                        <a href="<?= base_url('laporan') ?>" class="btn btn-outline-secondary text-left d-flex align-items-center justify-content-between p-3 mb-2" style="border-radius: 6px;">
                            <div>
                                <div class="font-weight-bold text-dark">Laporan & Rekapitulasi IKM</div>
                                <small class="text-muted">Unduh rekapitulasi data dan indeks kepuasan</small>
                            </div>
                            <i class="flaticon2-pie-chart-1 text-success"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/Home.php app/Views/pages/home/index.php tests/unit/AdminViewsTest.php
git commit -m "feat(dashboard): implement hybrid command dashboard with kpi cards and urgent ticket table"
```

---

### Task 3: Modernize Ticketing Queue, Quick-Filter Status Tabs & Scannable Table (`/ticketing`)

**Files:**
- Modify: `app/Views/pages/ticketing/index.php:1-85`
- Modify: `app/Views/pages/ticketing/response.php:1-120`
- Test: `tests/unit/AdminViewsTest.php`

**Interfaces:**
- Consumes: Ticketing filter query (rentangTanggal, layanan, status_layanan)
- Produces: Integrated filter bar with quick status tabs, search filter, and scannable Metronic table with unified badges.

- [ ] **Step 1: Write the failing test for ticketing table rendering**

Add test to `tests/unit/AdminViewsTest.php`:

```php
    public function testTicketingResponseRendersScannableTableAndBadges(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Daftar Tiket Permohonan Layanan',
            'user_group' => 'ADMIN',
            'detail_url' => base_url('ticketing/detail/'),
            'sgroup' => false,
            'datas' => [
                [
                    'ticketTrackingId' => 'TK-2026-999',
                    'ticketName' => 'Siti Rahmawati',
                    'ticketCreated' => '2026-09-13 14:00:00',
                    'categoryNama' => 'Layanan Akademik',
                    'sCatNama' => 'Legalisir Ijazah & Transkrip',
                    'priorityName' => 'Normal',
                    'unitNama' => 'Biro Akademik',
                    'statusNama' => 'Dalam Proses',
                    'statusColor' => 'info',
                    'ticketStatus' => 3,
                    'disposisiIsTrue' => false,
                    'ticketSuratCreated' => 'SRT-01',
                    'sCatDisposisi' => 'TOPDOWN',
                    'ticketAssign' => 1,
                    'ticketIsVerified' => 1,
                    'jenislayananId' => 1,
                    'disposisiIsRejected' => 0,
                    'sCatId' => 10,
                ]
            ],
        ])->render('pages/ticketing/response');

        $this->assertStringContainsString('TK-2026-999', $html);
        $this->assertStringContainsString('Siti Rahmawati', $html);
        $this->assertStringContainsString('kt-badge--unified-info', $html);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: FAIL because `kt-badge--unified-info` is not yet used in `response.php`.

- [ ] **Step 3: Update `ticketing/index.php` and `ticketing/response.php`**

Refactor `app/Views/pages/ticketing/index.php` to include status tabs and integrated filter layout:
- Add quick-filter status pill buttons (Semua, Baru, Disposisi, Verifikasi, Selesai).
- Position date range, category dropdown, and search keyword in a cohesive 3-column filter row inside the portlet body.

Refactor `app/Views/pages/ticketing/response.php`:
- Replace default badge with Metronic unified badges (`kt-badge--unified-<?= $row['statusColor'] ?> kt-badge--inline kt-badge--pill font-weight-bold`).
- Ensure tracking ID is monospace styled and clickable.
- Structure table columns with clear width ratios and compact vertical alignment.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Views/pages/ticketing/index.php app/Views/pages/ticketing/response.php tests/unit/AdminViewsTest.php
git commit -m "feat(ticketing): modernize ticket queue filter toolbar and scannable table with unified badges"
```

---

### Task 4: Reorganize Detail Tiket into a Structured Two-Column Inspection Workstation (`/ticketing/detail/*`)

**Files:**
- Modify: `app/Views/pages/ticketing/detail.php:1-350`
- Test: `tests/unit/AdminViewsTest.php`

**Interfaces:**
- Consumes: Ticket master data, applicant identity, uploaded requirements list, disposition history, message threads.
- Produces: 2-column inspection layout: Left column = Applicant identity & requirement documents with instant preview/verification badges; Right column = Tracking timeline, message reply form, and official QR document generator/actions.

- [ ] **Step 1: Write the failing test for ticket detail workstation layout**

Add test to `tests/unit/AdminViewsTest.php`:

```php
    public function testTicketDetailWorkstationRendersApplicantAndDocumentPanels(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Rincian Permohonan Tiket',
            'ticket' => (object)[
                'ticketTrackingId' => 'TK-2026-777',
                'ticketName' => 'Ahmad Fauzi',
                'ticketEmail' => 'ahmad@unmul.ac.id',
                'ticketPhone' => '08123456789',
                'ticketAddress' => 'Samarinda',
                'ticketCreated' => '2026-09-13 09:00:00',
                'ticketSubject' => 'Permohonan Surat Keterangan Pengganti Ijazah',
                'ticketStatus' => 3,
                'statusNama' => 'Dalam Proses',
                'statusColor' => 'info',
                'categoryNama' => 'Layanan Kemahasiswaan',
                'sCatNama' => 'Surat Keterangan Pengganti Ijazah Rusak/Hilang',
                'unitNama' => 'Biro Akademik',
            ],
            'files' => [],
            'history' => [],
            'replies' => [],
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'key' => 'test-key',
        ])->render('pages/ticketing/detail');

        $this->assertStringContainsString('Data Pemohon', $html);
        $this->assertStringContainsString('Dokumen Persyaratan', $html);
        $this->assertStringContainsString('TK-2026-777', $html);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: FAIL or error on specific sections.

- [ ] **Step 3: Implement structured two-column layout in `app/Views/pages/ticketing/detail.php`**

Restructure `detail.php`:
- Top banner: Summary of ticket tracking ID, applicant name, service name, and current unified status badge.
- Left column (`col-lg-5`):
  1. Card: Data Identitas Pemohon (Nama, Email, No. Telp, Alamat, Tanggal Pengajuan).
  2. Card: Berkas Persyaratan & Bukti Pendukung (List of files with download/preview buttons and verification indicator).
- Right column (`col-lg-7`):
  1. Card: Linimasa Disposisi & Riwayat Status (Vertical tracking timeline).
  2. Card: Formulir Tanggapan & Aksi Tiket (Terima, Tolak, Disposisikan, Draf Surat Resmi, Terbitkan QR).

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Views/pages/ticketing/detail.php tests/unit/AdminViewsTest.php
git commit -m "feat(ticketing): reorganize ticket detail into structured two-column inspection workstation"
```

---

### Task 5: Polish File Validation and Quarantine Views (`/validasifile`)

**Files:**
- Modify: `app/Views/pages/validasifile/index.php:1-250`
- Test: `tests/unit/AdminViewsTest.php`

**Interfaces:**
- Consumes: AJAX stats and manifest entries from `Validasifile` controller.
- Produces: Security scanning metric cards, manifest status table with security risk indicators, and clean quarantine/restore modal dialogs.

- [ ] **Step 1: Write the failing test for validasi file view polish**

Add test to `tests/unit/AdminViewsTest.php`:

```php
    public function testValidasifileViewRendersSecurityCardsAndManifestTable(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Validasi & Keamanan Berkas Digital',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
        ])->render('pages/validasifile/index');

        $this->assertStringContainsString('Integritas Berkas', $html);
        $this->assertStringContainsString('Berkas Aman', $html);
        $this->assertStringContainsString('Berkas Dikarantina', $html);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: FAIL because security metric card titles differ.

- [ ] **Step 3: Update `app/Views/pages/validasifile/index.php`**

Update `index.php` with:
- Metric cards for file integrity at the top (Total Berkas Terpindai, Berkas Aman [Hijau], Berkas Dikarantina [Merah Alert]).
- Standardized portlet with table manifest containing badge indicators (`.kt-badge--unified-success` for safe, `.kt-badge--unified-danger` for quarantined).
- Modal dialogs styled with Metronic standard radius and buttons (`.btn-brand`, `.btn-secondary`).

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Views/pages/validasifile/index.php tests/unit/AdminViewsTest.php
git commit -m "feat(security): polish file validation and quarantine views with security metrics and unified badges"
```

---

### Task 6: Unify Master Data and RBAC Pages with Consistent Tables and Forms

**Files:**
- Modify: `app/Views/pages/refkategori/index.php`
- Modify: `app/Views/pages/refsurat/index.php`
- Modify: `app/Views/pages/refsyarat/index.php`
- Modify: `app/Views/pages/unit/index.php`
- Modify: `app/Views/pages/pengguna/index.php`
- Modify: `app/Views/pages/hakakses/index.php`
- Test: `tests/unit/AdminViewsTest.php`

**Interfaces:**
- Consumes: Respective master entity arrays and pagination.
- Produces: Unified table headers with standardized `flaticon` icons, clear action buttons in portlet toolbar, consistent form inputs with 4px border radius, and clear SweetAlert2 confirmations.

- [ ] **Step 1: Write the failing test for master data consistency**

Add test to `tests/unit/AdminViewsTest.php`:

```php
    public function testMasterDataViewsUseConsistentPortletStructure(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Master Kategori Layanan',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('refkategori/create'),
            'datas' => [],
        ])->render('pages/refkategori/index');

        $this->assertStringContainsString('kt-portlet__head-title', $html);
        $this->assertStringContainsString('Tambah Data', $html);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: FAIL because button text or portlet classes differ.

- [ ] **Step 3: Standardize master views**

Update `refkategori`, `refsurat`, `refsyarat`, `unit`, `pengguna`, and `hakakses` index views to adopt the standard portlet header, "Tambah Data" button with icon `flaticon2-plus`, clean table headers, and action button groups.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/unit/AdminViewsTest.php`  
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Views/pages/refkategori/index.php app/Views/pages/refsurat/index.php app/Views/pages/refsyarat/index.php app/Views/pages/unit/index.php app/Views/pages/pengguna/index.php app/Views/pages/hakakses/index.php tests/unit/AdminViewsTest.php
git commit -m "feat(master): standardize master data and rbac index views with consistent portlet hierarchy"
```

---

### Task 7: Full Test Suite Regression & Impeccable Design Quality Verification

**Files:**
- Test: All tests in `tests/unit/`
- Tool: `/home/moohard/.claude/skills/impeccable/scripts/impeccable detect`

**Interfaces:**
- Consumes: All updated views and PHP controllers.
- Produces: Clean test run (0 failures) and verified Impeccable design quality check.

- [ ] **Step 1: Run full PHPUnit test suite**

Run: `vendor/bin/phpunit`  
Expected: All tests pass with 100% success rate.

- [ ] **Step 2: Run mechanical Impeccable detector across modified view files**

Run: `/home/moohard/.claude/skills/impeccable/scripts/impeccable detect --json app/Views/pages/home/index.php app/Views/pages/ticketing/index.php app/Views/pages/ticketing/response.php app/Views/pages/ticketing/detail.php app/Views/pages/validasifile/index.php`  
Expected: Verify absence of severe design violations (e.g. unstyled inputs, inline jarring colors, broken hierarchies).

- [ ] **Step 3: Commit final polish and documentation**

```bash
git add docs/superpowers/
git commit -m "docs: complete admin ui/ux shaping plan and design documentation"
```
