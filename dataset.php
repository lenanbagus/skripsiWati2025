<?php
session_start();
include 'config.php';
include 'header.php';

// --- LOGIC IMPORT CSV ---
if (isset($_POST['import_csv'])) {
    $filename = $_FILES['file_csv']['tmp_name'];

    if ($_FILES['file_csv']['size'] > 0) {
        $file = fopen($filename, "r");

        // Lewati baris pertama (header)
        fgetcsv($file);

        $success_count = 0;
        while (($column = fgetcsv($file, 1000, ",")) !== FALSE) {
            $tahun = $column[0];
            $x1 = $column[1];
            $x2 = $column[2];
            $x3 = $column[3];
            $x4 = $column[4];

            // Cek apakah tahun sudah ada
            $check = mysqli_query($conn, "SELECT id FROM population_data WHERE tahun = '$tahun'");
            if (mysqli_num_rows($check) == 0) {
                mysqli_query($conn, "INSERT INTO population_data (tahun, kelahiran, kematian, pindah_keluar, pindah_datang) 
                                     VALUES ('$tahun', '$x1', '$x2', '$x3', '$x4')");
                $success_count++;
            }
        }
        fclose($file);

        // PENTING: Jalankan Sinkronisasi Efek Domino setelah import masal
        include 'sync_data.php'; // Kita buat file terpisah agar kode rapi

        echo "<script>alert('$success_count Data baru berhasil diimport dan disinkronkan!'); window.location='dataset.php';</script>";
    }
}

// --- 1. LOGIC HAPUS & UPDATE (SAMA SEPERTI SEBELUMNYA) ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM population_data WHERE id='$id'");
    echo "<script>alert('Data berhasil dihapus!'); window.location='dataset.php';</script>";
}

if (isset($_POST['update_data'])) {
    $id_edit = $_POST['id'];
    // ... (Code update sama persis seperti revisi sebelumnya) ...
    // Tahun tidak diupdate agar konsisten, kita ambil value hidden nya saja
    // $tahun_edit = $_POST['tahun']; 
    $x1 = $_POST['x1'];
    $x2 = $_POST['x2'];
    $x3 = $_POST['x3'];
    $x4 = $_POST['x4'];

    // A. Update data baris ini
    mysqli_query($conn, "UPDATE population_data SET kelahiran='$x1', kematian='$x2', pindah_keluar='$x3', pindah_datang='$x4' WHERE id='$id_edit'");

    // B. PROSES RE-KALKULASI EFEK DOMINO
    $set = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
    $current_pop = $set['base_population'] ?? 0;
    $res_all = mysqli_query($conn, "SELECT * FROM population_data ORDER BY tahun ASC");
    while ($row = mysqli_fetch_assoc($res_all)) {
        $id_row = $row['id'];
        $new_y = ($row['kelahiran'] - $row['kematian']) + ($row['pindah_datang'] - $row['pindah_keluar']) + $current_pop;
        mysqli_query($conn, "UPDATE population_data SET jumlah_penduduk='$new_y' WHERE id='$id_row'");
        $current_pop = $new_y;
    }
    echo "<script>alert('Data berhasil diperbarui dan disinkronkan!'); window.location='dataset.php';</script>";
}

// --- LOGIC RESET DATA ---
if (isset($_POST['reset_data'])) {
    // Menghapus semua data dari tabel population_data
    $query_reset = mysqli_query($conn, "TRUNCATE TABLE population_data");

    if ($query_reset) {
        echo "<script>alert('Semua data berhasil dihapus!'); window.location='dataset.php';</script>";
    } else {
        echo "<script>alert('Gagal meriset data.');</script>";
    }
}

// --- 2. PERSIAPAN DATA UNTUK GRAFIK & TABEL ---
$get_setting = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
$data_setting = mysqli_fetch_assoc($get_setting);

// Ambil SEMUA data penduduk
$all_data = [];
$query = mysqli_query($conn, "SELECT * FROM population_data ORDER BY tahun ASC");
while ($r = mysqli_fetch_assoc($query)) {
    $all_data[] = $r;
}

// // Siapkan array untuk JSON Chart.js (UPDATE: Tambah X3 dan X4)
// $js_tahun = [];
// $js_y = [];
// $js_x1 = [];
// $js_x2 = [];
// $js_x3 = [];
// $js_x4 = []; // Array baru untuk Pindah

// foreach ($all_data as $d) {
//     $js_tahun[] = $d['tahun'];
//     $js_y[] = $d['jumlah_penduduk'];
//     $js_x1[] = $d['kelahiran'];
//     $js_x2[] = $d['kematian'];
//     // Isi data baru
//     $js_x3[] = $d['pindah_keluar'];
//     $js_x4[] = $d['pindah_datang'];
// }
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Data Set</h3>
    <div>
        <a href="export_sample.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download"></i> Download Format CSV
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
            <i class="bi bi-file-earmark-excel"></i> Import CSV
        </button>
    </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Data via CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <p class="small text-muted">Pastikan format file Anda sesuai dengan template yang telah disediakan.</p>
                    <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="import_csv" class="btn btn-primary w-100">Upload dan Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>
<hr>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card bg-light border-0 shadow-sm">
            <div class="card-body d-flex justify-content-around align-items-center py-2">
                <div class="text-center"><span class="text-muted small">Tahun Awal </span><strong class="h5"><?= $data_setting['base_year'] ?? '-' ?></strong></div>
                <div class="vr"></div>
                <div class="text-center"><span class="text-muted small">Penduduk Existing </span><strong class="h5 text-primary"><?= number_format($data_setting['base_population'] ?? 0) ?> Jiwa</strong></div>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive card shadow-sm p-3 mb-4">
    <h5 class="card-title mb-3">Tabel Data Rinci</h5>
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark text-center">
            <tr>
                <th>No</th>
                <th>Tahun</th>
                <th>Kelahiran (X1)</th>
                <th>Kematian (X2)</th>
                <th>Pindah Keluar (X3)</th>
                <th>Pindah Datang (X4)</th>
                <th>Jumlah Penduduk (Y)</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            foreach ($all_data as $row) {
            ?>
                <tr>
                    <td class="text-center"><?= $no ?></td>
                    <td class="text-center"><?= $row['tahun'] ?></td>
                    <td class="text-center text-success"><?= $row['kelahiran'] ?></td>
                    <td class="text-center text-danger"><?= $row['kematian'] ?></td>
                    <td class="text-center text-warning"><?= $row['pindah_keluar'] ?></td>
                    <td class="text-center text-info"><?= $row['pindah_datang'] ?></td>
                    <td class="text-center fw-bold bg-light"><?= number_format($row['jumlah_penduduk']) ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>">Edit</button>
                        <a href="dataset.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data ini?')">Hapus</a>
                    </td>
                </tr>

                <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title">Edit Data Thn <?= $row['tahun'] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <div class="mb-2"><label>Tahun</label><input type="number" name="tahun_view" class="form-control" value="<?= $row['tahun'] ?>" readonly disabled></div>
                                    <div class="mb-2"><label>Kelahiran (X1)</label><input type="number" name="x1" class="form-control" value="<?= $row['kelahiran'] ?>" required></div>
                                    <div class="mb-2"><label>Kematian (X2)</label><input type="number" name="x2" class="form-control" value="<?= $row['kematian'] ?>" required></div>
                                    <div class="mb-2"><label>Pindah Keluar (X3)</label><input type="number" name="x3" class="form-control" value="<?= $row['pindah_keluar'] ?>" required></div>
                                    <div class="mb-2"><label>Pindah Datang (X4)</label><input type="number" name="x4" class="form-control" value="<?= $row['pindah_datang'] ?>" required></div>
                                    <!-- <div class="alert alert-info mt-2 small py-1">Data tahun-tahun berikutnya akan dihitung ulang otomatis.</div> -->
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="update_data" class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php $no++;
            } ?>
        </tbody>
    </table>
    <div class="d-flex justify-content-end mt-3">
        <form method="POST" onsubmit="return confirm('PERINGATIN! Seluruh data akan terhapus. Tindakan ini tidak dapat dibatalkan.')">
            <button type="submit" name="reset_data" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash3"></i> Reset Data
            </button>
            <a href="regresi.php" class="btn btn-primary btn-sm">
                Lanjut ke Analisis <i class="bi bi-arrow-right-circle"></i>
            </a>
        </form>

    </div>
</div>

<!-- <div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-graph-up"></i> Grafik Tren Penduduk vs Faktor Demografi (X1-X4)
            </div>
            <div class="card-body">
                <canvas id="trendChart" style="max-height: 450px;"></canvas>
            </div>
        </div>
    </div>
</div> -->

<?php include 'footer.php'; ?>

<script>
    // Mengambil data JSON dari PHP (UPDATE: Tambah X3 dan X4)
    const yearsData = <?= json_encode($js_tahun) ?>;
    const yData = <?= json_encode($js_y) ?>;
    const x1Data = <?= json_encode($js_x1) ?>;
    const x2Data = <?= json_encode($js_x2) ?>;
    const x3Data = <?= json_encode($js_x3) ?>;
    const x4Data = <?= json_encode($js_x4) ?>;

    const ctx = document.getElementById('trendChart').getContext('2d');
    const trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: yearsData,
            datasets: [{
                    label: 'Total Penduduk (Y)',
                    data: yData,
                    borderColor: '#0d6efd', // Biru utama
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 3,
                    yAxisID: 'y', // Sumbu Y Kiri (Skala Besar)
                    fill: true,
                    tension: 0.3
                },
                // --- Dataset Faktor X (Sumbu Y Kanan) ---
                {
                    label: 'Kelahiran (X1)',
                    data: x1Data,
                    borderColor: '#198754', // Hijau
                    borderWidth: 2,
                    borderDash: [5, 5],
                    yAxisID: 'y1',
                    tension: 0.1
                },
                {
                    label: 'Kematian (X2)',
                    data: x2Data,
                    borderColor: '#dc3545', // Merah
                    borderWidth: 2,
                    borderDash: [5, 5],
                    yAxisID: 'y1',
                    tension: 0.1
                },
                // UPDATE: Tambahan X3 dan X4
                {
                    label: 'Pindah Keluar (X3)',
                    data: x3Data,
                    borderColor: '#fd7e14', // Orange
                    borderWidth: 2,
                    borderDash: [2, 3], // Pola garis putus beda dikit
                    yAxisID: 'y1',
                    tension: 0.1
                },
                {
                    label: 'Pindah Datang (X4)',
                    data: x4Data,
                    borderColor: '#0dcaf0', // Biru Muda (Cyan)
                    borderWidth: 2,
                    borderDash: [2, 3],
                    yAxisID: 'y1',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: { // Sumbu Y Kiri (Penduduk)
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Jumlah Penduduk (Jiwa)'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                },
                y1: { // Sumbu Y Kanan (Faktor X)
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Jumlah Faktor Demografi (Jiwa)'
                    },
                    grid: {
                        drawOnChartArea: false
                    },
                    min: 0
                },
                x: {
                    title: {
                        display: true,
                        text: 'Tahun'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID').format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                },
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>