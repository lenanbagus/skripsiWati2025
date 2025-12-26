<?php
session_start();
include 'header.php';
?>

<div class="row mb-4">
    <div class="col-12 text-center">
        <img src="ypkp.png" alt="Logo Universitas Sanggabuana" class="img-fluid" style="max-height: 120px;">
        <h2 class="mt-2 fw-bold">Universitas Sangga Buana YPKP</h2>
        <p class="text-muted">Sistem Informasi Prediksi Lonjakan Penduduk</p>
        <hr style="width: 50%; margin: auto; border-top: 3px solid #0d6efd;">
    </div>
</div>

<div class="row">

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-info text-white">Profile Pembuat</div>
            <div class="card-body">
                <h5 class="card-title">Wati Lediawati, A.Md.</h5>
                <p class="card-text">Mahasiswi Universitas Sanggabuana.</p>
                <p class="card-text">Aplikasi ini dibuat untuk memenuhi kebutuhan prediksi lonjakan penduduk di Kabupaten Bandung menggunakan metode statistik Regresi Linear Berganda, Sebagai syarat kelulusan Sidang Skripsi Strata I</p>
                <p><strong>Teknologi:</strong> PHP, MySQL, Bootstrap 5.</p>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-warning text-dark">Regresi Linear Berganda</div>
            <div class="card-body">
                <p class="card-text">
                    Regresi Linear Berganda adalah metode statistik yang digunakan untuk memodelkan hubungan antara satu variabel dependen (Y) dengan dua atau lebih variabel independen (X).
                    <br><br>
                    <strong>Rumus Umum:</strong><br>
                    Y = a + b₁X₁ + b₂X₂ + ... + bₙXₙ + e
                </p>
                <a href="kriteria.php" class="btn btn-primary">Input Variabel</a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php';?>