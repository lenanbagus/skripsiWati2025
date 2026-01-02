<?php
session_start();
include 'config.php';
include 'header.php';

// Simpan/Update Setting Awal
if (isset($_POST['save_settings'])) {
    $by = $_POST['base_year'];
    $bp = $_POST['base_population'];
    // Cek data existing
    $check = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET base_year='$by', base_population='$bp'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (base_year, base_population) VALUES ('$by', '$bp')");
    }
    echo "<script>alert('Data Existing Tersimpan!');</script>";
}

// Tambah Data Tahunan
if (isset($_POST['add_data'])) {
    $thn = $_POST['tahun'];
    $x1 = $_POST['x1'];
    $x2 = $_POST['x2'];
    $x3 = $_POST['x3'];
    $x4 = $_POST['x4'];
    // Ambil data base population
    $set = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
    $base_pop = $set['base_population'] ?? 0;

    // Ambil data tahun sebelumnya
    $prev_year = $thn - 1;
    $qry_prev = mysqli_query($conn, "SELECT jumlah_penduduk FROM population_data WHERE tahun='$prev_year'");
    if (mysqli_num_rows($qry_prev) > 0) {
        $d_prev = mysqli_fetch_assoc($qry_prev);
        $prev_pop = $d_prev['jumlah_penduduk'];
    } else {
        $prev_pop = $base_pop; // Asumsi tahun pertama setelah base
    }

    $y = ($x1 - $x2) + ($x4 - $x3) + $prev_pop;

    $sql = "INSERT INTO population_data (tahun, kelahiran, kematian, pindah_keluar, pindah_datang, jumlah_penduduk) 
            VALUES ('$thn', '$x1', '$x2', '$x3', '$x4', '$y')";
    mysqli_query($conn, $sql);
}

$setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
?>

<h3>Input Data</h3>
<hr>

<div class="card mb-4">
    <div class="card-body">
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label>Tahun Awal (Base Year)</label>
                <input type="number" name="base_year" class="form-control" value="<?= $setting['base_year'] ?? '' ?>" required>
            </div>
            <div class="col-md-4">
                <label>Jumlah Penduduk Existing</label>
                <input type="number" name="base_population" class="form-control" value="<?= $setting['base_population'] ?? '' ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" name="save_settings" class="btn btn-success w-100">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Data Variabel (X)</span>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">Tambah Data</button>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tahun</th>
                    <th>Kelahiran (X1)</th>
                    <th>Kematian (X2)</th>
                    <th>Pindah Keluar (X3)</th>
                    <th>Pindah Datang (X4)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q = mysqli_query($conn, "SELECT * FROM population_data ORDER BY tahun ASC");
                $no = 1;
                while ($d = mysqli_fetch_assoc($q)) {
                    echo "<tr>
                        <td>$no</td>
                        <td>{$d['tahun']}</td>
                        <td>{$d['kelahiran']}</td>
                        <td>{$d['kematian']}</td>
                        <td>{$d['pindah_keluar']}</td>
                        <td>{$d['pindah_datang']}</td>
                    </tr>";
                    $no++;
                }
                ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-end mt-4">
        <a href="dataset.php" class="btn btn-primary px-4 py-2 shadow-sm">
            Next <i class="bi bi-arrow-right-short"></i>
        </a>
    </div>
    </div>
</div>

<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Input Data Tahunan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><label>Tahun</label><input type="number" name="tahun" class="form-control" required></div>
                    <div class="mb-2"><label>Kelahiran (X1)</label><input type="number" name="x1" class="form-control" required></div>
                    <div class="mb-2"><label>Kematian (X2)</label><input type="number" name="x2" class="form-control" required></div>
                    <div class="mb-2"><label>Pindah Keluar (X3)</label><input type="number" name="x3" class="form-control" required></div>
                    <div class="mb-2"><label>Pindah Datang (X4)</label><input type="number" name="x4" class="form-control" required></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_data" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>