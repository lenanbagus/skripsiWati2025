<?php
session_start();
include 'config.php';
include 'header.php';

if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM population_data WHERE id='$id'");
    echo "<script>alert('Data berhasil dihapus!'); window.location='dataset.php';</script>";
}

if(isset($_POST['update_data'])){
    $id_edit = $_POST['id'];
    $tahun_edit = $_POST['tahun'];
    $x1 = $_POST['x1'];
    $x2 = $_POST['x2'];
    $x3 = $_POST['x3'];
    $x4 = $_POST['x4'];
    $query_update_self = "UPDATE population_data SET 
                         kelahiran='$x1', 
                         kematian='$x2', 
                         pindah_keluar='$x3', 
                         pindah_datang='$x4' 
                         WHERE id='$id_edit'";
    mysqli_query($conn, $query_update_self);
    $set = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
    $current_pop = $set['base_population'] ?? 0;
    $res_all = mysqli_query($conn, "SELECT * FROM population_data ORDER BY tahun ASC");
    
    while($row = mysqli_fetch_assoc($res_all)){
        $id_row = $row['id'];
        $l = $row['kelahiran'];
        $m = $row['kematian'];
        $pk = $row['pindah_keluar'];
        $pd = $row['pindah_datang'];

        // (Lahir - Mati) + (Datang - Keluar) + Penduduk Sebelumnya
        $new_y = ($l - $m) + ($pd - $pk) + $current_pop;

        // Update database
        mysqli_query($conn, "UPDATE population_data SET jumlah_penduduk='$new_y' WHERE id='$id_row'");

        // Nilai penduduk sekarang untuk menjadi 'Penduduk Sebelumnya' di loop berikutnya
        $current_pop = $new_y;
    }

    echo "<script>alert('Data berhasil diperbarui! Seluruh data tahun berikutnya telah disinkronkan.'); window.location='dataset.php';</script>";
}
?>

<?php
// Mengambil data setting
$get_setting = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
$data_setting = mysqli_fetch_assoc($get_setting);
$base_year = $data_setting['base_year'] ?? '-';
$base_pop = $data_setting['base_population'] ?? 0;
?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card bg-light border-0 shadow-sm">
            <div class="card-body d-flex justify-content-around align-items-center py-2">
                <div class="text-center">
                    <span class="text-muted small d-block">Tahun Awal (Base Year)</span>
                    <strong class="h5"><?= $base_year ?></strong>
                </div>
                <div class="vr"></div> <div class="text-center">
                    <span class="text-muted small d-block">Jumlah Penduduk Existing</span>
                    <strong class="h5 text-primary"><?= number_format($base_pop) ?> Jiwa</strong>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="table-responsive">

<h3>Data Set (Variabel + Y Terhitung)</h3>
<hr>

<div class="table-responsive">
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
            $query = mysqli_query($conn, "SELECT * FROM population_data ORDER BY tahun ASC");
            $no = 1;
            while($row = mysqli_fetch_assoc($query)){
            ?>
                <tr>
                    <td class="text-center"><?= $no ?></td>
                    <td class="text-center"><?= $row['tahun'] ?></td>
                    <td class="text-center"><?= $row['kelahiran'] ?></td>
                    <td class="text-center"><?= $row['kematian'] ?></td>
                    <td class="text-center"><?= $row['pindah_keluar'] ?></td>
                    <td class="text-center"><?= $row['pindah_datang'] ?></td>
                    <td class="text-center fw-bold bg-light"><?= number_format($row['jumlah_penduduk']) ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>">
                            Edit
                        </button>
                        <a href="dataset.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data ini?')">Delete</a>
                    </td>
                </tr>

                <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title" id="exampleModalLabel">Edit Data Tahun <?= $row['tahun'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                                    <div class="mb-2">
                                        <label>Tahun</label>
                                        <input type="number" name="tahun" class="form-control" value="<?= $row['tahun'] ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Kelahiran (X1)</label>
                                        <input type="number" name="x1" class="form-control" value="<?= $row['kelahiran'] ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Kematian (X2)</label>
                                        <input type="number" name="x2" class="form-control" value="<?= $row['kematian'] ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Pindah Keluar (X3)</label>
                                        <input type="number" name="x3" class="form-control" value="<?= $row['pindah_keluar'] ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Pindah Datang (X4)</label>
                                        <input type="number" name="x4" class="form-control" value="<?= $row['pindah_datang'] ?>" required>
                                    </div>
                                    <div class="alert alert-info mt-3 py-2">
                                        <small><i class="bi bi-info-circle"></i> Nilai <b>Jumlah Penduduk (Y)</b> akan dikalkulasi ulang otomatis setelah disimpan.</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="update_data" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php
                $no++;
            }
            ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>