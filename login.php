<?php
require 'config.php';
$error = '';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nip = $_POST['nip'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE nip = ?");
    $stmt->bind_param("s", $nip);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Password benar, buat session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nip'] = $nip;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Password yang anda masukkan salah!";
        }
    } else {
        $error = "NIP tidak ditemukan!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Buku Induk Guru</title>
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
                <div class="p">Login Guru & Tendik</div>
            </div>
        </div>

            
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="nip">NIP / NUPTK</label>
                    <input type="text" id="nip" name="nip" placeholder="Masukkan NIP / NUPTK Anda" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
                </div>
                            <?php if (!empty($error)): ?>
                            <p style="color:red;"><?= $error; ?></p>
                            <?php endif; ?>
                <button type="submit" class="btn btn-login">Masuk</button>
                <p style="margin-top:15px;">Belum punya akun?&nbsp;<a href="register.php"> Daftar di sini</a></p>
            </form>
        </div>
    </div>
</body>
</html>