<?php
session_start();
require "../../service/connection.php";

// Ambil role dari session
$role = $_SESSION['level'] ?? 'Kasir'; // default Kasir jika tidak ada

// Tentukan halaman redirect sesuai role
if ($role === 'Admin') {
    $redirectPage = "../../pages/admin/member.php";
} else {
    $redirectPage = "../../pages/kasir/member_kasir.php";
}

// Ambil ID dari POST (form) atau GET (URL)
$id = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id = (int)$_POST['id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
} else {
    $_SESSION['error'] = 'ID member tidak valid!';
    header("Location: $redirectPage");
    exit();
}

// Ambil data member dari database
$query = "SELECT * FROM member WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$member = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$member) {
    $_SESSION['error'] = "Member tidak ditemukan!";
    header("Location: $redirectPage");
    exit();
}

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name   = trim($_POST['name']);
    $phone  = trim($_POST['phone']);
    $status = $_POST['status'];

    // Cek duplikat nomor telepon (kecuali nomor member ini sendiri)
    $check_sql = "SELECT id FROM member WHERE phone = ? AND id != ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "si", $phone, $id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $_SESSION['error'] = "Nomor telepon sudah digunakan member lain!";
        header("Location: $redirectPage");
        exit();
    }
    mysqli_stmt_close($stmt_check);

    // Update data ke database (tidak update point)
    $update_sql = "UPDATE member SET name = ?, phone = ?, status = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt_update, "sssi", $name, $phone, $status, $id);

    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['success'] = "Data member berhasil diupdate!";
        header("Location: $redirectPage");
        exit();
    } else {
        $_SESSION['error'] = "Gagal update data: " . mysqli_error($conn);
        header("Location: $redirectPage");
        exit();
    }
    mysqli_stmt_close($stmt_update);
}