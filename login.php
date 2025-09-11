<?php
session_start();
include "db.php";
$db = new DB();
$conn = $db->koneksiDB();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Ambil data user berdasarkan username
    $user = $db->getITEM("SELECT * FROM `user` WHERE `username` = '$username'");

    if ($user) {
        $dbPassword = $user['password'];

        // jika admin -> password plain text (admin tetap bisa login walaupun status tidak aktif)
        if ($user['role'] === 'admin' && $password === $dbPassword) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['role']     = $user['role'];
            header("Location: admin_dashboard.php");
            exit();
        }

        // jika user biasa -> cek status harus aktif + password hash
        if ($user['role'] === 'user' && $user['status'] === 'aktif' && password_verify($password, $dbPassword)) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['role']     = $user['role'];
            header("Location: index.php");
            exit();
        }
    }

    echo "<script>alert('Username, password salah, atau akun Anda tidak aktif!'); window.location='login.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      height: 100vh;
    }
    .left {
      flex: 1;
      background: #2e2b28ff;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .left img {
      width: 300px;
    }
    .right {
      flex: 1;
      background: #f9f9f9;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .card {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
    }
    h2 {
      margin: 0 0 20px;
      font-size: 24px;
      color: #333;
    }
    h2 span {
      color: #cbab39ff;
      font-weight: bold;
    }
    .input-box {
      margin-bottom: 15px;
    }
    .input-box input {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ccc;
      outline: none;
      font-size: 14px;
    }
    .btn {
      width: 100%;
      padding: 12px;
      background: #2e2b28ff;
      border: none;
      color: white;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
    }
    .btn:hover {
      background: #2e2b28ff;
    }
    .link {
      margin-top: 15px;
      text-align: center;
      font-size: 14px;
    }
    .link a {
      color: #cbab39ff;
      text-decoration: none;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="left">
    <img src="images/DPR_RI.png" alt="Logo DPR-RI">
  </div>
  <div class="right">
    <div class="card">
      <h2>Welcome to <span>Design Document</span></h2>
      <form method="post">
        <div class="input-box">
          <input type="text" name="username" placeholder="Username" required>
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn">Login</button>
      </form>
      <div class="link">
        Belum punya akun? <a href="regis.php">Register</a>
      </div>
    </div>
  </div>
</body>
</html>
