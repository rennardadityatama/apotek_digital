<?php
session_start();
require '../../service/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Simpan data lama ke session untuk refill form jika error
    $_SESSION['old'] = [
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'level' => $_POST['level'] ?? '',
    ];

    $username = $_SESSION['old']['username'];
    $email = trim($_SESSION['old']['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $level = "Kasir"; // Paksa level selalu Kasir

    // Cek email sudah ada atau belum
    $check_email_sql = "SELECT id FROM admin WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $check_email_sql);
    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    $email_exists = mysqli_stmt_num_rows($stmt_check) > 0;
    mysqli_stmt_close($stmt_check);

    if ($email_exists) {
        $_SESSION['error'] = 'Email sudah terdaftar!';
        header("Location: tambah_admin.php");
        exit();
    }

    $target_dir = "../../assets/img/admin/";
    $image = "";

    if (!empty($_FILES["image"]["name"])) {
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileSizeLimit = 2 * 1024 * 1024; // 2MB

        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $_SESSION['error'] = 'File bukan gambar!';
            header("Location: tambah_admin.php");
            exit();
        }

        if (!in_array($imageFileType, $allowedTypes)) {
            $_SESSION['error'] = 'Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan!';
            header("Location: tambah_admin.php");
            exit();
        }

        if ($_FILES["image"]["size"] > $fileSizeLimit) {
            $_SESSION['error'] = 'Ukuran file terlalu besar! Maksimal 2MB';
            header("Location: tambah_admin.php");
            exit();
        }

        $image = uniqid('admin_', true) . '.' . $imageFileType;
        $target_file = $target_dir . $image;

        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $_SESSION['error'] = 'Gagal mengunggah gambar!';
            header("Location: tambah_admin.php");
            exit();
        }
    }

    $sql = "INSERT INTO admin (username, email, password, image, level) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $password, $image, $level);

    if (mysqli_stmt_execute($stmt)) {
        unset($_SESSION['old']); // hapus data lama karena sudah berhasil
        $_SESSION['success'] = 'Admin berhasil ditambahkan';
        header("Location: ../../pages/admin/admin.php");
        exit();
    } else {
        $_SESSION['error'] = 'Gagal menambahkan admin. Coba lagi!';
        header("Location: tambah_admin.php");
        exit();
    }
}