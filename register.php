<?php
require 'config.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nip = $_POST['nip'];
    $password = $_POST['password'];
    $nama_lengkap = $_POST['nama_lengkap'];

    // Validasi sederhana
    if (empty($nip) || empty($password) || empty($nama_lengkap)) {
        $message = "Semua field wajib diisi!";
    } else {
        // Cek apakah NIP sudah ada
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE nip = ?");
        $stmt_check->bind_param("s", $nip);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "NIP sudah terdaftar!";
        } else {
            // Hash password untuk keamanan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Mulai transaksi
            $conn->begin_transaction();

            try {
                // 1. Simpan ke tabel users
                $stmt_user = $conn->prepare("INSERT INTO users (nip, password) VALUES (?, ?)");
                $stmt_user->bind_param("ss", $nip, $hashed_password);
                $stmt_user->execute();
                $user_id = $stmt_user->insert_id;

                // 2. Simpan ke tabel guru
                $stmt_guru = $conn->prepare("INSERT INTO guru (user_id, nip, nama_lengkap) VALUES (?, ?, ?)");
                $stmt_guru->bind_param("iss", $user_id, $nip, $nama_lengkap);
                $stmt_guru->execute();
                
                // Commit transaksi
                $conn->commit();
                $message = "Registrasi berhasil! Silakan <a href='login.php'>login</a>.";

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $message = "Registrasi gagal: " . $exception->getMessage();
            }
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Guru</title>
    <link rel="stylesheet" href="style.css">
    <style>
    :root{--blue:#0b66ff;--muted:#6b7280;--card:#ffffff;--bg:#f3f6fb}
    *{box-sizing:border-box;font-family:Inter,system-ui,Segoe UI,Roboto,'Helvetica Neue',Arial}
    body{margin:0;background:var(--bg);color:#111}
    /* Login */
    .login-page{display:flex;align-items:center;justify-content:center;height:100vh}
    .login-card{width:400px;background:white;padding:28px;border-radius:12px;box-shadow:0 6px 30px rgba(9,30,66,0.08)}
    .logo{display:flex;align-items:center;gap:12px}
    .logo .mark{width:46px;height:46px;background:linear-gradient(135deg,var(--blue),#7aa2ff);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700}
    .h1{font-size:18px;font-weight:700}
    .login-card p {display: flex;justify-content: center;align-items: center;}
    .small{font-size:12px;color:var(--muted)}
    label{display:block;margin-top:12px;font-size:13px;color:var(--muted)}
    input[type=text],input[type=password],select,textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #e6e9ee;}
    button.btn{display:inline-block;padding:10px 14px;border-radius:8px;border:0;background:var(--blue);color:white;font-weight:600;cursor:pointer}
    .btn-login 
    {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 5px;
    background-color: var(--primary-color);
    color: var(--white);
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s;
    text-decoration: none; /* Untuk tag <a> */
    display: inline-block; /* Untuk tag <a> */
    }
    .btn-login:hover {background-color: #0b5ed7;}
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="logo">
                <div class="mark">BITK</div>
            <div>
                <div class="h1">Buku Induk Tenaga Kependidikan</div>
                <div class="p">Registrasi Akun</div>
            </div>
        </div>


            <form action="register.php" method="post">
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" placeholder="Masukkan nama lengkap anda" name="nama_lengkap" required>
                </div>
                <div class="form-group">
                    <label for="nip">NIP / NUPTK</label>
                    <input type="text" id="nip" placeholder="Masukkan NIP / NUPTK anda" name="nip" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" placeholder="Buat Password anda" name="password" required>
                </div>
                            <?php if (!empty($message)): ?>
                            <p style="color:red;"><?= $message; ?></p>
                            <?php endif; ?>
                <button type="submit" class="btn btn-login">Daftar</button>
                 <p style="margin-top:15px;">Sudah punya akun?&nbsp;<a href="login.php"> Login di sini</a></p>
            </form>
        </div>
    </div>
</body>
</html>