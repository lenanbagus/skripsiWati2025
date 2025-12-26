<?php
session_start();
include 'config.php';

// Logic Register
if (isset($_POST['register'])) {
    $user = $_POST['reg_username'];
    $p1 = $_POST['reg_pass1'];
    $p2 = $_POST['reg_pass2'];

    if ($p1 === $p2) {
        $passHash = password_hash($p1, PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (username, password) VALUES ('$user', '$passHash')");
        echo "<script>alert('Registrasi Berhasil! Silakan Login.');</script>";
    } else {
        echo "<script>alert('Password tidak cocok!');</script>";
    }
}

// Logic Login
if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$user'");
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($pass, $row['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['username'] = $user;
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login & Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header text-center bg-primary text-white">
                        <h4>Aplikasi Prediksi Penduduk</h4>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#login">Login</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#register">Register</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="login">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label>Username</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="register">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label>Username Baru</label>
                                        <input type="text" name="reg_username" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Password</label>
                                        <input type="password" name="reg_pass1" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Ulangi Password</label>
                                        <input type="password" name="reg_pass2" class="form-control" required>
                                    </div>
                                    <button type="submit" name="register" class="btn btn-success w-100">Buat User</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>