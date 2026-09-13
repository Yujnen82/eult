<!--Begin::Row-->
<!-- begin:: Content -->
<div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <div id="response"></div>
            <!--begin::Portlet-->
            <div class="kt-portlet">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <h3 class="kt-portlet__head-title">
                            <?= strtoupper($page_judul) ?>
                        </h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <div class="kt-portlet__head-actions">
                            <a href="javascript:void(0);" id="export" val_name="laporan" class="btn btn-outline-success">
                                <span><i class="flaticon2-print"></i><span>Cetak Excel</span> </span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="kt-portlet__body">

                    <!--begin::Section-->
                    <div class="kt-section">
                        <div class="kt-section__content">
                            <div class="table-responsive">
                                <table class="table table-hover" id="table_export">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="text-align: center">Jenis Layanan</th>
                                            <th style="text-align: center">Nama Layanan</th>
                                            <th style="text-align: center">Bidang</th>
                                            <th style="text-align: center">Tiket Masuk</th>
                                            <th style="text-align: center">Diterima</th>
                                            <th style="text-align: center">Ditolak</th>
                                            <th style="text-align: center">Diproses</th>
                                            <th style="text-align: center">Selesai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if($datas!=false)
                                        {
                                            $i = 1;
                                            $masuk=$terima=$tolak=$proses=$selesai=0;
                                            foreach($datas as $row)
                                            {
                                                $key = service('enkripsi')->encode($row['unitId']);
                                                ?>
                                                <tr>
                                                    <td><?=$row['jenislayananNama']?></td>
                                                    <td><?=$row['layananNama']?></td>
                                                    <td><?=$row['unitNama']?></td>
                                                    <td style="text-align: center"><?=$row['Jumlah']?></td>
                                                    <td style="text-align: center"><?=$row['TERIMA']?></td>
                                                    <td style="text-align: center"><?=$row['TOLAK']?></td>
                                                    <td style="text-align: center"><?=$row['PROSES']?></td>
                                                    <td style="text-align: center"><?=$row['SELESAI']?></td>
                                                    
                                                </tr>
                                                <?php
                                                $masuk+=$row['Jumlah'];
                                                $terima+=$row['TERIMA'];
                                                $tolak+=$row['TOLAK'];
                                                $proses+=$row['PROSES'];
                                                $selesai+=$row['SELESAI'];
                                            }
                                        } 
                                        ?>
                                        <?php
                                        if($datas!=false and isset($datas))
                                        {
                                            ?>
                                        <tr style="font-weight: bold;">
                                            <td colspan="3" style="text-align: center">Total</th>
                                            <td style="text-align: center"><?=$masuk?></th>
                                            <td style="text-align: center"><?=$terima?></th>
                                            <td style="text-align: center"><?=$tolak?></th>
                                            <td style="text-align: center"><?=$proses?></th>
                                            <td style="text-align: center"><?=$selesai?></th>
                                        </tr>
                                        <?php
                                        } else {
                                            ?>
                                            <tr>
                                                <td colspan="8" style="text-align: center">Data tidak ditemukan</td>
                                            </tr>
                                            <?php
                                        }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!--end::Section-->
                </div>
            </div>

            <!--end::Portlet-->
        </div>
    </div>
</div>
<!--End::Row-->
