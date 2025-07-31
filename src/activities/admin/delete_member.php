<?php
require "../../service/connection.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "<script>alert('ID member tidak valid'); window.location.href='../../pages/admin/member.php';</script>";
    exit();
}

// Cek status member
$stmt = $conn->prepare("SELECT status FROM member WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    echo "<script>alert('Member tidak ditemukan'); window.location.href='../../pages/admin/member.php';</script>";
    exit();
}

if ($res['status'] === 'active') {
    echo "<script>alert('Member yang aktif tidak bisa dihapus!'); window.location.href='../../pages/admin/member.php';</script>";
    exit();
}

// Hapus member jika non-active
$stmt = $conn->prepare("DELETE FROM member WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo "<script>alert('Data berhasil dihapus'); window.location.href='../../pages/admin/member.php';</script>";
} else {
    echo "<script>alert('Gagal menghapus member'); window.location.href='../../pages/admin/member.php';</script>";
}
$stmt->close();