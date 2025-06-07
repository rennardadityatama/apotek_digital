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
    $allowed_levels = ['Admin', 'Kasir'];
    $level = $_SESSION['old']['level'];

    if (!in_array($level, $allowed_levels)) {
        $_SESSION['error'] = 'Level tidak valid!';
        header("Location: tambah_admin.php");
        exit();
    }

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
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tambah Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <h2 class="text-center mb-4">Tambah Admin</h2>

            <?php
            $old = $_SESSION['old'] ?? [];
            unset($_SESSION['old']);
            ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="username" class="form-label">Nama Admin:</label>
                    <input type="text" name="username" class="form-control" required
                        value="<?= htmlspecialchars($old['username'] ?? '') ?>" />
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control" required
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>" />
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" name="password" class="form-control" required />
                </div>

                <div class="mb-3">
                    <label for="level" class="form-label">Role:</label>
                    <select name="level" class="form-select" required>
                        <option value="" disabled <?= empty($old['level']) ? 'selected' : '' ?>>Pilih role</option>
                        <option value="Admin" <?= (isset($old['level']) && $old['level'] === 'Admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="Kasir" <?= (isset($old['level']) && $old['level'] === 'Kasir') ? 'selected' : '' ?>>Kasir</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Foto Admin:</label>
                    <input type="file" name="image" class="form-control" accept="image/*" />
                </div>

                <button type="submit" class="btn btn-primary w-100">Tambah Admin</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php
    if (isset($_SESSION['success'])) {
        echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: " . json_encode($_SESSION['success']) . ",
            showConfirmButton: true
        });
        </script>";
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error'])) {
        echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: " . json_encode($_SESSION['error']) . ",
            showConfirmButton: true
        });
        </script>";
        unset($_SESSION['error']);
    }
    ?>
</body>

</html>
