<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

include "db.php";
$db = new DB();

// proses simpan judul dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['judul'])) {
    $judul = mysqli_real_escape_string($db->koneksiDB(), $_POST['judul']);

    // simpan dengan relasi ke user
    $sql = "INSERT INTO judul_dokumen (judul, user_id) VALUES ('$judul', '$user_id')";
    $db->runSQL($sql);

    echo "<script>
            alert('Judul dokumen berhasil disimpan!');
            window.location.href='doc_design.php';
          </script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {margin: 0; padding: 0; box-sizing: border-box;}
    body {font-family: 'Segoe UI', sans-serif; background: #f9f9f9;}
    .navbar {
      width: 100%; background: #2e2b28ff; color: white;
      padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .navbar h1 {margin: 0; font-size: 18px; font-weight: 600; white-space: nowrap;}
    .navbar .right {display: flex; align-items: center; gap: 12px;}
    .navbar .username {
      font-size: 15px; font-weight: 500; display: flex; align-items: center; gap: 6px;
      color: white; white-space: nowrap;
    }
    .logout-btn {
      display: flex; align-items: center; justify-content: center;
      width: 32px; height: 32px; border-radius: 50%;
      background: rgba(255, 255, 255, 0.2); color: white;
      text-decoration: none; font-size: 16px; transition: background 0.3s;
    }
    .logout-btn:hover {background: rgba(255, 255, 255, 0.35);}
    .content {padding: 30px; text-align: center;}
    .content h2 {color: #333;}
    .image-container {
      display: flex; justify-content: center; align-items: flex-start;
      gap: 100px; margin-top: 50px; flex-wrap: wrap;
    }
    .image-box {text-align: center;}
    .image-box h3 {margin-bottom: 15px; font-size: 18px; color: #333;}
    .image-box img {
      width: 150px; height: 150px; border-radius: 10px; object-fit: cover;
      margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .image-box button {
      padding: 10px 20px; background: #2e2b28ff; color: white;
      border: none; border-radius: 8px; cursor: pointer; font-size: 14px;
      display: block; margin: 0 auto;
    }
    .image-box button:hover {background: #444;}
    .modal {
      display: none; position: fixed; z-index: 999; left: 0; top: 0;
      width: 100%; height: 100%; background: rgba(0,0,0,0.5);
      justify-content: center; align-items: center;
    }
    .modal-content {
      background: white; padding: 20px; border-radius: 10px;
      text-align: center; width: 300px;
    }
    .modal-content h3 {margin-bottom: 15px;}
    .modal-content input {
      width: 90%; padding: 8px; margin-bottom: 10px;
      border-radius: 5px; border: 1px solid #ccc;
    }
    .modal-content button {
      padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer;
    }
    .modal-content button[type="submit"] {background: #2e2b28ff; color: white;}
    .modal-content button[type="button"] {background: #ccc; margin-top: 8px;}
    @media (max-width: 600px) {
      .navbar {flex-direction: column; align-items: flex-start; gap: 8px;}
      .navbar h1 {font-size: 16px;}
      .navbar .right {width: 100%; justify-content: space-between;}
      .image-container {flex-direction: column; gap: 40px;}
    }
  </style>
  <script>
    function confirmLogout(event) {
      event.preventDefault();
      if (confirm("Apakah Anda yakin ingin logout?")) {
        window.location.href = "logout.php";
      }
    }
    function openModal() {
      document.getElementById("dokumenModal").style.display = "flex";
    }
    function closeModal() {
      document.getElementById("dokumenModal").style.display = "none";
    }
    function confirmSubmit(event) {
      if (!confirm("Apakah Anda yakin ingin submit dokumen?")) {
        event.preventDefault();
      }
    }
  </script>
</head>
<body>
  <div class="navbar">
    <h1>Design Document</h1>
    <div class="right">
      <span class="username"><i class="fas fa-user"></i> <?php echo $username; ?></span>
      <a href="logout.php" class="logout-btn" title="Logout" onclick="confirmLogout(event)">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>

  <div class="content">
    <h2>Selamat datang, <?php echo $username; ?>!</h2>

    <div class="image-container">
      <!-- History -->
      <div class="image-box">
        <h3>History</h3>
        <img src="images/history.png" alt="History">
        <button onclick="window.location.href='history.php'">Click Here</button>
      </div>

      <!-- Dokumen -->
      <div class="image-box">
        <h3>Dokumen</h3>
        <img src="images/dokumen.png" alt="Dokumen">
        <button onclick="openModal()">Click Here</button>
      </div>
    </div>
  </div>

  <!-- Modal Form -->
  <div class="modal" id="dokumenModal">
    <div class="modal-content">
      <h3>Tambah Dokumen</h3>
      <form method="POST" onsubmit="confirmSubmit(event)">
        <input type="text" name="judul" placeholder="Judul Dokumen" required>
        <button type="submit">Submit</button>
      </form>
      <br>
      <button type="button" onclick="closeModal()">Tutup</button>
    </div>
  </div>
</body>
</html>
