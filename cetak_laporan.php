<?php
session_start();
require_once 'config.php';

// Proteksi jika session kosong (user belum klik Run Prediksi)
if (!isset($_SESSION['b0']) || !isset($_SESSION['last_prediction'])) {
    echo "<script>alert('Harap lakukan analisis prediksi terlebih dahulu!'); window.close();</script>";
    exit;
}

// Ambil data dari Session
$b0 = $_SESSION['b0'];
$b1 = $_SESSION['b1'];
$b2 = $_SESSION['b2'];
$b3 = $_SESSION['b3'];
$b4 = $_SESSION['b4'];
$mape = $_SESSION['mape'];
$r_squared = $_SESSION['r_squared'];
$model_data = $_SESSION['model_data'];
$pred = $_SESSION['last_prediction'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Prediksi Penduduk - USB</title>
    <style>
        body { font-family: 'Times New Roman', serif; line-height: 1.5; color: #333; padding: 20px; }
        .kop { text-align: center; border-bottom: 3px double #000; margin-bottom: 20px; padding-bottom: 10px; }
        .kop h2 { margin: 0; text-transform: uppercase; font-size: 20px; }
        .kop p { margin: 5px 0; font-size: 14px; }
        .title { text-align: center; text-decoration: underline; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #000; padding: 8px; font-size: 12px; }
        .table th { background-color: #f2f2f2; }
        .summary-box { border: 2px solid #000; padding: 15px; margin-top: 20px; background-color: #f9f9f9; }
        .footer-sign { margin-top: 50px; float: right; text-align: center; min-width: 200px; }
        @media print { 
            .no-print { display: none; } 
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align: right; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">Print</button>
</div>

<div class="kop">
    <h2>Universitas Sanggabuana YPKP</h2>
    <p>Jl. PH.H. Mustofa No.68, Bandung, Jawa Barat</p>
    <p>Sistem Informasi Prediksi Lonjakan Penduduk</p>
</div>

<h3 class="title">LAPORAN ANALISIS DAN PREDIKSI PENDUDUK</h3>

<p><strong>A. Ringkasan Model Regresi</strong></p>
<table class="table">
    <tr>
        <th width="30%">Persamaan Regresi</th>
        <td>Y = <?= number_format($b0, 2) ?> + (<?= number_format($b1, 2) ?>)X1 + (<?= number_format($b2, 2) ?>)X2 + (<?= number_format($b3, 2) ?>)X3 + (<?= number_format($b4, 2) ?>)X4</td>
    </tr>
    <tr>
        <th>Akurasi (MAPE)</th>
        <td><?= number_format($mape, 2) ?> %</td>
    </tr>
    <tr>
        <th>R-Squared</th>
        <td><?= number_format($r_squared * 100, 2) ?> %</td>
    </tr>
</table>

<p><strong>B. Tabel Pengujian Model (Actual vs Prediction)</strong></p>
<table class="table">
    <thead>
        <tr>
            <th>Tahun</th>
            <th>Data Aktual (Jiwa)</th>
            <th>Hasil Prediksi Model</th>
            <th>Selisih (Residual)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($model_data as $md): ?>
        <tr>
            <td align="center"><?= $md['tahun'] ?></td>
            <td align="right"><?= number_format($md['y_act']) ?></td>
            <td align="right"><?= number_format($md['y_pred'], 2) ?></td>
            <td align="right"><?= number_format($md['res'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><strong>C. Hasil Proyeksi / Prediksi</strong></p>
<div class="summary-box">
    <p style="margin: 0; font-size: 16px;">Berdasarkan tren data historis, prediksi jumlah penduduk untuk tahun <strong><?= $pred['tahun'] ?></strong> adalah:</p>
    <h1 style="text-align: center; margin: 15px 0;"><?= number_format($pred['nilai']) ?> Jiwa</h1>
    <p style="margin: 0; font-style: italic; font-size: 13px; text-align: center;">
        (Estimasi Faktor: Kelahiran <?= round($pred['est_x1']) ?>, Kematian <?= round($pred['est_x2']) ?>, Pindah Keluar <?= round($pred['est_x3']) ?>, Pindah Datang <?= round($pred['est_x4']) ?>)
    </p>
</div>

<div class="footer-sign">
    <p>Bandung, <?= date('d F Y') ?></p>
    <p>Petugas Analisis,</p>
    <br><br><br>
    <p><strong>Wati Lediawati, A.Md.</strong></p>
</div>

</body>
</html>