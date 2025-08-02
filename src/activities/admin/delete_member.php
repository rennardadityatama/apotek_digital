<?php
require "../../service/connection.php";
session_start();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    $_SESSION['error'] = 'ID member tidak valid';
    header('Location: ../../pages/admin/member.php');
    exit();
}

// Cek status member
$stmt = $conn->prepare("SELECT status FROM member WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    $_SESSION['error'] = 'Member tidak ditemukan';
    header('Location: ../../pages/admin/member.php');
    exit();
}

if ($res['status'] === 'active') {
    $_SESSION['error'] = 'Member yang aktif tidak bisa dihapus!';
    header('Location: ../../pages/admin/member.php');
    exit();
}

// Hapus member jika non-active
$stmt = $conn->prepare("DELETE FROM member WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $_SESSION['success'] = 'Data berhasil dihapus';
} else {
    $_SESSION['error'] = 'Gagal menghapus member';
}
$stmt->close();

header('Location: ../../pages/admin/member.php');
exit();