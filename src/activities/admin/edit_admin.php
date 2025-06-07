<?php
session_start();
require '../../service/connection.php';
require '../../service/utility.php';

if (!isset($_GET['id'])) {
    die("ID admin tidak ditemukan!");
}

$id = (int)$_GET['id'];
$loggedInAdminId = $_SESSION['id'] ?? 0;

// Ambil data admin berdasarkan ID
$query = "SELECT username, email, password, image FROM admin WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($username, $email, $hashed_password, $image);
$fetchSuccess = $stmt->fetch();
$stmt->close();

if (!$fetchSuccess) {
    die("Admin tidak ditemukan!");
}

// Cek apakah sedang edit diri sendiri
$isEditingSelf = ($id === $loggedInAdminId);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_post = trim($_POST['username']);

    if ($isEditingSelf) {
        $email_post = trim($_POST['email']);
        $new_password = $_POST['password'];

        // Cek duplikat email kecuali untuk id ini sendiri
        $check_email_sql = "SELECT id FROM admin WHERE email = ? AND id != ?";
        $stmt_check = $conn->prepare($check_email_sql);
        $stmt_check->bind_param("si", $email_post, $id);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $_SESSION['error'] = 'Email sudah digunakan oleh admin lain!';
            header("Location: edit_admin.php?id=$id");
            exit;
        }
        $stmt_check->close();

        // Password baru atau tetap
        $final_password = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : $hashed_password;

        $new_image = $image;

        // Upload gambar jika ada
        if (!empty($_FILES['image']['name'])) {
            $file_name = $_FILES['image']['name'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png'];

            if (!in_array($file_ext, $allowed_extensions)) {
                $_SESSION['error'] = 'Format gambar tidak valid! Hanya JPG, JPEG, PNG yang diperbolehkan.';
                header("Location: edit_admin.php?id=$id");
                exit;
            }

            if ($image && file_exists("../../assets/img/admin/" . $image)) {
                unlink("../../assets/img/admin/" . $image);
            }

            $new_image = uniqid('admin_', true) . "." . $file_ext;
            if (!move_uploaded_file($file_tmp, "../../assets/img/admin/" . $new_image)) {
                $_SESSION['error'] = 'Gagal mengunggah gambar!';
                header("Location: edit_admin.php?id=$id");
                exit;
            }
        }

        // Update semua field
        $updateQuery = $conn->prepare("UPDATE admin SET username = ?, email = ?, password = ?, image = ? WHERE id = ?");
        $updateQuery->bind_param("ssssi", $username_post, $email_post, $final_password, $new_image, $id);

    } else {
        // Edit admin lain, hanya update username saja
        $updateQuery = $conn->prepare("UPDATE admin SET username = ? WHERE id = ?");
        $updateQuery->bind_param("si", $username_post, $id);
    }

    if ($updateQuery->execute()) {
        $_SESSION['success'] = 'Data admin berhasil diperbarui!';
        header("Location: ../../pages/admin/admin.php");
        exit;
    } else {
        $_SESSION['error'] = "Gagal memperbarui data admin: " . $conn->error;
        header("Location: edit_admin.php?id=$id");
        exit;
    }
    $updateQuery->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .card {
            max-width: 400px;
            margin: auto;
        }
    </style>
</head>
<body class="d-flex align-items-center vh-100">
<div class="container">
    <div class="card shadow-lg p-4">
        <h2 class="text-center mb-4">Edit Admin</h2>
        <form action="" method="POST" enctype="multipart/form-data" novalidate>
            <div class="mb-3">
                <label for="username" class="form-label">Nama Admin:</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
            </div>

            <?php if ($isEditingSelf): ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password (Kosongkan jika tidak ingin mengubah):</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password">
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Foto Admin (Upload jika ingin mengubah):</label>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
                    <?php if ($image && file_exists("../../assets/img/admin/" . $image)): ?>
                        <small>Gambar saat ini:</small><br>
                        <img src="../../assets/img/admin/<?= htmlspecialchars($image) ?>" alt="Foto Admin" style="max-width: 150px; margin-top: 5px;">
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <small class="text-muted">Anda hanya dapat mengubah username untuk admin lain.</small>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-success w-100">Simpan Perubahan</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
