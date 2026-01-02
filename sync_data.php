<?php
// Script ini digunakan untuk menghitung ulang seluruh kolom Y dari awal sampai akhir
$set = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
$current_pop = $set['base_population'] ?? 0;
$res_all = mysqli_query($conn, "SELECT * FROM population_data ORDER BY tahun ASC");
while($row = mysqli_fetch_assoc($res_all)){
    $id_row = $row['id'];
    $new_y = ($row['kelahiran'] - $row['kematian']) + ($row['pindah_datang'] - $row['pindah_keluar']) + $current_pop;
    mysqli_query($conn, "UPDATE population_data SET jumlah_penduduk='$new_y' WHERE id='$id_row'");
    $current_pop = $new_y;
}
?>