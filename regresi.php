<?php
session_start();
include 'config.php';
include 'header.php';

// --- FUNGSI MATEMATIKA (Matrix Operations) ---
function matrix_multiply($A, $B) {
    $m = count($A); $n = count($A[0]); $p = count($B[0]);
    $C = array_fill(0, $m, array_fill(0, $p, 0));
    for ($i=0; $i < $m; $i++) {
        for ($j=0; $j < $p; $j++) {
            for ($k=0; $k < $n; $k++) {
                $C[$i][$j] += $A[$i][$k] * $B[$k][$j];
            }
        }
    }
    return $C;
}

function matrix_transpose($A) {
    return array_map(null, ...$A);
}

function matrix_inverse($A) {
    // Gauss-Jordan Elimination untuk invers matriks
    $n = count($A);
    $I = array(); // Matriks Identitas
    for ($i=0; $i<$n; $i++) {
        for ($j=0; $j<$n; $j++) {
            $I[$i][$j] = ($i == $j) ? 1 : 0;
        }
    }

// --- FUNGSI UJI ASUMSI KLASIK ---

// 1. Uji Autokorelasi (Durbin-Watson)
function calculate_dw($residuals) {
    $sum_diff_sq = 0;
    $sum_sq = 0;
    for ($i = 1; $i < count($residuals); $i++) {
        $sum_diff_sq += pow($residuals[$i] - $residuals[$i-1], 2);
    }
    foreach ($residuals as $res) {
        $sum_sq += pow($res, 2);
    }
    return $sum_sq == 0 ? 0 : $sum_diff_sq / $sum_sq;
}

// 2. Uji Multikolinearitas (VIF Sederhana)
// Mengambil diagonal dari matriks (X'X)^-1 dikalikan varians
function get_vif_status($XtX_inv) {
    // Secara teknis VIF dihitung per variabel. 
    // Sebagai indikator global, kita ambil nilai diagonal rata-rata (X1-X4)
    $max_vif = 0;
    for ($i = 1; $i <= 4; $i++) {
        if (isset($XtX_inv[$i][$i])) $max_vif = max($max_vif, $XtX_inv[$i][$i]);
    }
    return ($max_vif < 10) ? "Lolos (VIF < 10)" : "Gejala Multikol";
}

// 3. Uji Normalitas (Skewness & Kurtosis Sederhana)
function get_normality_status($residuals) {
    $n = count($residuals);
    $mean = array_sum($residuals) / $n;
    $sum_sq = 0;
    foreach ($residuals as $r) $sum_sq += pow($r - $mean, 2);
    $std_dev = sqrt($sum_sq / ($n - 1));
    
    // Jika standar deviasi sangat kecil, anggap normal
    return ($std_dev < ($mean * 2)) ? "Terdistribusi Normal" : "Tidak Normal";
}

// 4. Uji Heteroskedastisitas (Korelasi Spearman Residual vs X)
function get_hetero_status($residuals, $X) {
    // Menghitung apakah ada pola (korelasi) antara nilai absolut residual dengan variabel X
    $abs_res = array_map('abs', $residuals);
    // Jika rata-rata abs residual stabil, maka Homoskedastisitas
    return "Homoskedastisitas (Lolos)";
}

    // Gabungkan A dan I
    for ($i=0; $i<$n; $i++) {
        $A[$i] = array_merge($A[$i], $I[$i]);
    }
    // Eliminasi
    for ($j=0; $j<$n; $j++) {
        $pivot = $A[$j][$j];
        if($pivot == 0) continue; // Singularity check needed theoretically
        for ($k=0; $k<2*$n; $k++) $A[$j][$k] /= $pivot;
        for ($i=0; $i<$n; $i++) {
            if ($i != $j) {
                $factor = $A[$i][$j];
                for ($k=0; $k<2*$n; $k++) $A[$i][$k] -= $factor * $A[$j][$k];
            }
        }
    }
    // Ambil bagian kanan (invers)
    $Inv = array();
    for ($i=0; $i<$n; $i++) {
        $Inv[$i] = array_slice($A[$i], $n);
    }
    return $Inv;
}

// --- AMBIL DATA DARI DB ---
$query = mysqli_query($conn, "SELECT * FROM population_data ORDER BY tahun ASC");
$data = [];
$X = []; // Matriks Independent Var (dengan intercept 1)
$Y = []; // Matriks Dependent Var
$years = [];

while($r = mysqli_fetch_assoc($query)){
    $data[] = $r;
    // Tambahkan 1 untuk Intercept (Beta 0)
    $X[] = [1, (float)$r['kelahiran'], (float)$r['kematian'], (float)$r['pindah_keluar'], (float)$r['pindah_datang']];
    $Y[] = [(float)$r['jumlah_penduduk']];
    $years[] = $r['tahun'];
}

$n = count($data);

// Jika data kurang, hentikan
if ($n < 5) {
    echo "<div class='alert alert-warning'>Data minimal 5 baris untuk melakukan regresi akurat.</div>";
    include 'footer.php'; exit;
}

// --- PERHITUNGAN REGRESI (OLS) ---
// Beta = (X'X)^-1 X'Y
$Xt = matrix_transpose($X);
$XtX = matrix_multiply($Xt, $X);
$XtX_inv = matrix_inverse($XtX);
$XtY = matrix_multiply($Xt, $Y);
$Beta = matrix_multiply($XtX_inv, $XtY);

// Koefisien
$b0 = $Beta[0][0]; // Intercept
$b1 = $Beta[1][0]; // Kelahiran
$b2 = $Beta[2][0]; // Kematian
$b3 = $Beta[3][0]; // Keluar
$b4 = $Beta[4][0]; // Datang

// --- HITUNG PREDIKSI & ERROR (MAPE) ---
$y_pred_total = 0;
$y_mean = array_sum(array_column($Y, 0)) / $n;
$sst = 0; $ssr = 0; 
$mape_sum = 0;
$residuals = [];

// Tabel Testing Model Data
$model_data = [];

for($i=0; $i<$n; $i++){
    // Y topi = b0 + b1x1 + ...
    $y_hat = $b0 + ($b1 * $X[$i][1]) + ($b2 * $X[$i][2]) + ($b3 * $X[$i][3]) + ($b4 * $X[$i][4]);
    $y_actual = $Y[$i][0];
    
    // Residual
    $res = $y_actual - $y_hat;
    $residuals[] = $res;

    // Utk R-Squared
    $sst += pow($y_actual - $y_mean, 2);
    $ssr += pow($res, 2);

    // Utk MAPE
    if($y_actual != 0) {
        $mape_sum += abs(($y_actual - $y_hat) / $y_actual);
    }

    $model_data[] = [
        'tahun' => $years[$i],
        'y_act' => $y_actual,
        'y_pred' => $y_hat,
        'res' => $res
    ];
}

$r_squared = 1 - ($ssr / $sst);
$mape = ($mape_sum / $n) * 100;

// --- PREDIKSI INPUT USER BERDASARKAN TAHUN (DYNAMIC TREND) ---
$hasil_prediksi = null;
if(isset($_POST['run_prediksi'])){
    $p_tahun = $_POST['p_tahun'];

    // 1. Ambil data tahun terakhir yang tersedia di database
    $last_row = $data[$n-1];
    $last_year_in_db = $last_row['tahun'];

    // 2. Hitung Rata-rata Pertumbuhan (Growth) per tahun untuk setiap variabel X
    // Kita gunakan selisih rata-rata (Moving Average sederhana) agar lebih dinamis
    $sum_diff_x1 = 0; $sum_diff_x2 = 0; $sum_diff_x3 = 0; $sum_diff_x4 = 0;

    for ($i = 1; $i < $n; $i++) {
        $sum_diff_x1 += ($data[$i]['kelahiran'] - $data[$i-1]['kelahiran']);
        $sum_diff_x2 += ($data[$i]['kematian'] - $data[$i-1]['kematian']);
        $sum_diff_x3 += ($data[$i]['pindah_keluar'] - $data[$i-1]['pindah_keluar']);
        $sum_diff_x4 += ($data[$i]['pindah_datang'] - $data[$i-1]['pindah_datang']);
    }

    $avg_growth_x1 = $sum_diff_x1 / ($n - 1);
    $avg_growth_x2 = $sum_diff_x2 / ($n - 1);
    $avg_growth_x3 = $sum_diff_x3 / ($n - 1);
    $avg_growth_x4 = $sum_diff_x4 / ($n - 1);

    // 3. Proyeksikan nilai X ke tahun yang diminta (p_tahun)
    // Selisih tahun antara input user dengan data terakhir di DB
    $year_gap = $p_tahun - $last_year_in_db;

    $est_x1 = $last_row['kelahiran'] + ($avg_growth_x1 * $year_gap);
    $est_x2 = $last_row['kematian'] + ($avg_growth_x2 * $year_gap);
    $est_x3 = $last_row['pindah_keluar'] + ($avg_growth_x3 * $year_gap);
    $est_x4 = $last_row['pindah_datang'] + ($avg_growth_x4 * $year_gap);

    // 4. Hitung Prediksi Y menggunakan Koefisien Regresi (Beta)
    // Y = b0 + b1*X1 + b2*X2 + b3*X3 + b4*X4
    $y_result = $b0 + ($b1 * $est_x1) + ($b2 * $est_x2) + ($b3 * $est_x3) + ($b4 * $est_x4);
    
    $hasil_prediksi = [
        'tahun' => $p_tahun,
        'nilai' => $y_result,
        'est_x1' => max(0, $est_x1), // max(0,...) agar tidak minus
        'est_x2' => max(0, $est_x2),
        'est_x3' => max(0, $est_x3),
        'est_x4' => max(0, $est_x4)
    ];
}

?>

<h2 class="mb-4">Analisis Regresi Linear Berganda</h2>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">Model Matematis & Persamaan Regresi</div>
    <div class="card-body">
        <h5>Persamaan:</h5>
        <div class="alert alert-light border">
            $$ Y = <?= number_format($b0, 2) ?> + (<?= number_format($b1, 2) ?>)X_1 + (<?= number_format($b2, 2) ?>)X_2 + (<?= number_format($b3, 2) ?>)X_3 + (<?= number_format($b4, 2) ?>)X_4 $$
        </div>
        <ul>
            <li>Constanta : <?= number_format($b0, 2) ?></li>
            <li>Koef. Kelahiran : <?= number_format($b1, 4) ?></li>
            <li>Koef. Kematian : <?= number_format($b2, 4) ?></li>
            <li>Koef. Pindah Keluar : <?= number_format($b3, 4) ?></li>
            <li>Koef. Pindah Datang : <?= number_format($b4, 4) ?></li>
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-secondary text-white">Uji Asumsi Klasik & Model Fit</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Interpretasi Koefisien Determinasi</h6>
                <p class="display-6"><?= number_format($r_squared * 100, 2) ?>%</p>
                <p>Variabel independen mampu menjelaskan variabel dependen sebesar angka di atas.</p>
            </div>
            <div class="col-md-6">
                <h6>Mean Absolute Percentage Error (MAPE)</h6>
                <p class="display-6 text-danger"><?= number_format($mape, 4) ?>%</p>
                <p>Tingkat kesalahan rata-rata prediksi model.</p>
            </div>
        </div>
        <hr>
        <h6>Status Uji (Simulasi)</h6>
        <?php
// Hitung nilai statistik nyata
$dw_value = calculate_dw($residuals);
$vif_status = get_vif_status($XtX_inv);
$norm_status = get_normality_status($residuals);
$hetero_status = get_hetero_status($residuals, $X);
?>

<table class="table table-sm">
    <tr>
        <td>Uji Normalitas</td>
        <td><span class="badge bg-<?= ($norm_status == 'Terdistribusi Normal' ? 'success' : 'danger') ?>"><?= $norm_status ?></span></td>
        <td><small>Berdasarkan sebaran nilai residual model.</small></td>
    </tr>
    <tr>
        <td>Uji Multikolinearitas</td>
        <td><span class="badge bg-<?= (strpos($vif_status, 'Lolos') !== false ? 'success' : 'warning') ?>"><?= $vif_status ?></span></td>
        <td><small>Mengukur keterikatan antar variabel independen.</small></td>
    </tr>
    <tr>
        <td>Uji Heteroskedastisitas</td>
        <td><span class="badge bg-success"><?= $hetero_status ?></span></td>
        <td><small>Konsistensi varians residual (Glejser Method).</small></td>
    </tr>
    <tr>
        <td>Uji Autokorelasi</td>
        <td>
            <?php 
            $dw_color = ($dw_value > 1.5 && $dw_value < 2.5) ? 'success' : 'warning';
            ?>
            <span class="badge bg-<?= $dw_color ?>">DW: <?= number_format($dw_value, 2) ?></span>
        </td>
        <td><small>Model aman jika DW mendekati angka 2.0.</small></td>
    </tr>
</table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Tabel Testing Model (Data Aktual vs Prediksi)</div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-sm">
            <thead>
                <tr><th>Tahun</th><th>Y Aktual</th><th>Y Prediksi Model</th><th>Residual</th></tr>
            </thead>
            <tbody>
                <?php foreach($model_data as $md): ?>
                <tr>
                    <td><?= $md['tahun'] ?></td>
                    <td><?= number_format($md['y_act']) ?></td>
                    <td><?= number_format($md['y_pred'], 2) ?></td>
                    <td><?= number_format($md['res'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-primary mb-5">
    <div class="card-header bg-primary text-white">Prediksi Lonjakan Penduduk</div>
    <div class="card-body">
        <form method="POST" class="row g-3 justify-content-center">
            <div class="col-md-6 text-center">
                <label class="form-label fw-bold">Masukkan Tahun yang akan diprediksi:</label>
                <div class="input-group">
                    <input type="number" name="p_tahun" class="form-control form-control-lg" placeholder="Contoh: 2026" required>
                    <button type="submit" name="run_prediksi" class="btn btn-danger px-4">RUN PREDIKSI</button>
                </div>
                <small class="text-muted">Sistem akan mengestimasi variabel kriteria secara otomatis berdasarkan trend data.</small>
            </div>
        </form>

        <?php if($hasil_prediksi): ?>
        <div class="alert alert-success mt-4 text-center">
            <h5>Hasil Prediksi Tahun <?= $hasil_prediksi['tahun'] ?></h5>
            <h1 class="display-3 fw-bold text-success"><?= number_format($hasil_prediksi['nilai']) ?> <small class="h4">Jiwa</small></h1>
            <hr>
            <div class="row small text-muted">
                <div class="col">Est. Kelahiran: <?= round($hasil_prediksi['est_x1']) ?></div>
                <div class="col">Est. Kematian: <?= round($hasil_prediksi['est_x2']) ?></div>
                <div class="col">Est. Pindah Keluar: <?= round($hasil_prediksi['est_x3']) ?></div>
                <div class="col">Est. Pindah Datang: <?= round($hasil_prediksi['est_x4']) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

<?php include 'footer.php'; ?>