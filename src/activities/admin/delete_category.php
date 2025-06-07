<?php
session_start();
require '../../service/connection.php';

if (isset($_GET['id'])) {
    $id = intval($_GET["id"]); // Validasi ID agar aman

    // Cek apakah kategori ada sebelum dihapus
    $sql_check = "SELECT * FROM category WHERE id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        // Cek apakah masih ada produk dengan kategori ini
        $sql_product = "SELECT COUNT(*) as total FROM products WHERE fid_kategori = ?";
        $stmt_product = mysqli_prepare($conn, $sql_product);
        mysqli_stmt_bind_param($stmt_product, "i", $id);
        mysqli_stmt_execute($stmt_product);
        $result_product = mysqli_stmt_get_result($stmt_product);
        $product_data = mysqli_fetch_assoc($result_product);
        mysqli_stmt_close($stmt_product);

        if ($product_data['total'] > 0) {
            // Jika masih ada produk, jangan hapus
            $_SESSION['error'] = "Kategori tidak bisa dihapus karena masih ada produk yang menggunakan kategori ini.";
            header('Location: ../../pages/admin/kategori.php');
            exit();
        } else {
            // Jika tidak ada produk, hapus kategori
            $query = "DELETE FROM category WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            $execute = mysqli_stmt_execute($stmt);

            if ($execute) {
                $_SESSION['success'] = "Kategori berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus kategori!";
            }
            mysqli_stmt_close($stmt);
            header('Location: ../../pages/admin/kategori.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Kategori tidak ditemukan!";
        header('Location: ../../pages/admin/kategori.php');
        exit();
    }

    // Tutup statement cek kategori
    mysqli_stmt_close($stmt_check);
    mysqli_close($conn);
} else {
    $_SESSION['error'] = "Akses tidak valid!";
    header('Location: ../../pages/admin/kategori.php');
    exit();
}
?>
