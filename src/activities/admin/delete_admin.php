<?php
session_start();
require '../../service/connection.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $loggedInAdminId = $_SESSION['id'] ?? 0;

    if ($id === $loggedInAdminId) {
        $_SESSION['error'] = 'Admin yang sedang login tidak dapat dihapus!';
        header('Location: ../../pages/admin/admin.php');
        exit();
    }

    $sql_check = "SELECT * FROM admin WHERE id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $id);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result) > 0) {
        $sql_delete = "DELETE FROM admin WHERE id = ?";
        $stmt_delete = mysqli_prepare($conn, $sql_delete);
        mysqli_stmt_bind_param($stmt_delete, "i", $id);

        if (mysqli_stmt_execute($stmt_delete)) {
            $_SESSION['success'] = 'Admin berhasil dihapus';
        } else {
            $_SESSION['error'] = 'Gagal menghapus admin. Coba lagi!';
        }
    } else {
        $_SESSION['error'] = 'Admin tidak ditemukan!';
    }
} else {
    $_SESSION['error'] = 'ID tidak valid!';
}

header('Location: ../../pages/admin/admin.php');
exit();
