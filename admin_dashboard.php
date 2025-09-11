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
$conn = $db->koneksiDB();

// Proses ubah status aktif/non-aktif
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $result = mysqli_query($conn, "SELECT status FROM user WHERE id='$id'");
    if ($row = mysqli_fetch_assoc($result)) {
        $new_status = ($row['status'] == 'aktif') ? 'tidak aktif' : 'aktif';
        mysqli_query($conn, "UPDATE user SET status='$new_status' WHERE id='$id'");
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Proses delete user
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM user WHERE id='$id'");
    header("Location: admin_dashboard.php");
    exit();
}

// Ambil semua user role 'user'
$users = mysqli_query($conn, "SELECT * FROM user WHERE role='user'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
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
    .content {padding: 30px;}
    h2 {margin-bottom: 20px; color: #333;}
    table {
      width: 100%; border-collapse: collapse; background: white;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden;
    }
    th, td {
      padding: 12px 15px; text-align: center; border-bottom: 1px solid #ddd;
    }
    th {background: #2e2b28ff; color: white;}
    tr:hover {background: #f5f5f5;}
    .status-btn {
      padding: 5px 10px; border-radius: 5px; color: white; text-decoration: none;
    }
    .aktif {background: green;}
    .tidak-aktif {background: red;}
    .action a {
      margin: 0 5px; color: #333; text-decoration: none; font-size: 16px;
    }
    .action a:hover {color: red;}
  </style>
  <script>
    function confirmLogout(event) {
      event.preventDefault();
      if (confirm("Apakah Anda yakin ingin logout?")) {
        window.location.href = "logout.php";
      }
    }
    function confirmDelete(event, url) {
      event.preventDefault();
      if (confirm("Apakah Anda yakin ingin menghapus user ini?")) {
        window.location.href = url;
      }
    }
  </script>
</head>
<body>
  <div class="navbar">
    <h1>Admin Dashboard</h1>
    <div class="right">
      <span class="username"><i class="fas fa-user"></i> <?php echo $username; ?></span>
      <a href="logout.php" class="logout-btn" title="Logout" onclick="confirmLogout(event)">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>

  <div class="content">
    <h2>Manajemen User</h2>
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Username</th>
          <th>Password</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($users)): ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['username']); ?></td>
            <td><?= htmlspecialchars($row['password']); ?></td>
            <td>
              <a href="admin_dashboard.php?toggle_status=<?= $row['id']; ?>" 
                 class="status-btn <?= ($row['status']=='aktif') ? 'aktif':'tidak-aktif'; ?>">
                 <?= $row['status']; ?>
              </a>
            </td>
            <td class="action">
              <a href="#" onclick="confirmDelete(event, 'admin_dashboard.php?delete=<?= $row['id']; ?>')">
                <i class="fas fa-trash"></i>
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
