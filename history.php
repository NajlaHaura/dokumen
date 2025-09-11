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

// ambil history dokumen berdasarkan user yang login
$sql = "SELECT h.id, h.nama_dokumen, j.judul 
        FROM history h 
        JOIN judul_dokumen j ON h.id_judul_dokumen = j.id 
        WHERE j.user_id = '$user_id'";
$result = $db->getALL($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>History</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {margin: 0; padding: 0; box-sizing: border-box;}
    body {font-family: 'Segoe UI', sans-serif; background: #f9f9f9;}
    .navbar {
      width: 100%; background: #2e2b28ff; color: white;
      padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .navbar h1 {margin: 0; font-size: 18px; font-weight: 600;}
    .navbar .right {display: flex; align-items: center; gap: 12px;}
    .navbar .username {
      font-size: 15px; font-weight: 500; display: flex; align-items: center; gap: 6px;
      color: white;
    }
    .logout-btn {
      display: flex; align-items: center; justify-content: center;
      width: 32px; height: 32px; border-radius: 50%;
      background: rgba(255, 255, 255, 0.2); color: white;
      text-decoration: none; font-size: 16px; transition: background 0.3s;
    }
    .logout-btn:hover {background: rgba(255, 255, 255, 0.35);}
    .back-btn {
      display: inline-flex; align-items: center; margin: 20px;
      font-size: 16px; color: #2e2b28ff; text-decoration: none; font-weight: 500;
    }
    .back-btn i {margin-right: 6px;}
    .container {padding: 20px;}
    table {
      width: 100%; border-collapse: collapse; margin-top: 20px;
      background: white; border-radius: 10px; overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    table th, table td {
      padding: 12px 15px; text-align: center; border-bottom: 1px solid #ddd;
    }
    table th {
      background: #2e2b28ff; color: white; font-size: 14px;
    }
    table td {font-size: 14px; color: #333;}
    .action-icons {
      display: flex; justify-content: center; gap: 12px;
    }
    .action-icons a {
      text-decoration: none; color: #2e2b28ff; font-size: 16px;
    }
    .export-dropdown {
      position: relative; display: inline-block;
    }
    .dropdown-content {
      display: none; position: absolute; background: white; min-width: 120px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 6px; z-index: 1;
    }
    .dropdown-content a {
      display: block; padding: 8px 12px; text-decoration: none; color: #333;
      font-size: 14px;
    }
    .dropdown-content a:hover {background: #f1f1f1;}
    .export-dropdown:hover .dropdown-content {display: block;}
  </style>
</head>
<body>
  <div class="navbar">
    <h1>Design Document</h1>
    <div class="right">
      <span class="username"><i class="fas fa-user"></i> <?php echo $username; ?></span>
      <a href="logout.php" class="logout-btn" title="Logout" onclick="return confirm('Yakin logout?')">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>

  <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>

  <div class="container">
    <h2>History Dokumen</h2>
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Dokumen</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($result) {
            $no = 1;
            foreach ($result as $row) {
                echo "<tr>
                        <td>".$no++."</td>
                        <td>".$row['judul']."</td>
                        <td>
                          <div class='action-icons'>
                            <a href='view.php?id=".$row['id']."' title='View PDF'><i class='fas fa-file-pdf'></i></a>
                            <div class='export-dropdown'>
                              <a href='#' title='Export'><i class='fas fa-download'></i></a>
                              <div class='dropdown-content'>
                                <a href='export.php?id=".$row['id']."&type=pdf'>Export as PDF</a>
                                <a href='export.php?id=".$row['id']."&type=doc'>Export as DOC</a>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Belum ada history dokumen.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</body>
</html>
