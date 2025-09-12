<?php
// Koneksi ke database
$host = "localhost";
$user = "root"; // default user XAMPP
$pass = "";     // default password kosong
$db   = "dokumen";

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Variabel pesan sukses
$success = "";

// Kalau form di-submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama     = $_POST['nama'];
    $telepon  = $_POST['telepon'];
    $email    = $_POST['email'];
    $pesan    = $_POST['pesan'];
    $user_id  = 1; // contoh default user_id = 1 (atau bisa ambil dari session/login)

    // Insert ke tabel kontak
    $stmt = $conn->prepare("INSERT INTO kontak (user_id, nama, no_telp, email, pesan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $nama, $telepon, $email, $pesan);

    if ($stmt->execute()) {
        $success = "Pesan berhasil terkirim!";
    } else {
        $success = "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kritik & Saran</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #fff;
            width: 400px;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .container h2 {
            text-align: center;
            color: #2e4632;
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        textarea {
            resize: none;
            height: 100px;
        }
        button {
            background-color: #2e4632;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background-color: #3b5b42;
        }
        .success {
            background: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Kritik & Saran</h2>

        <?php if (!empty($success)) { ?>
            <div class="success"><?php echo $success; ?></div>
        <?php } ?>

        <form method="POST" action="">
            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" placeholder="Nama Anda" required>

            <label for="telepon">No Telepon</label>
            <input type="text" id="telepon" name="telepon" placeholder="0812xxxxxxx" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="email@domain.com" required>

            <label for="pesan">Pesan</label>
            <textarea id="pesan" name="pesan" placeholder="Tulis pesan Anda..." required></textarea>

            <button type="submit">Kirim Pesan</button>
        </form>
    </div>
</body>
</html>