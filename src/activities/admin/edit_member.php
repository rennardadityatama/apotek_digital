<?php
session_start();
require "../../service/connection.php";

// Ambil ID dari URL dan validasi
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID member tidak valid!");
}

$id = (int)$_GET['id'];

// Ambil data member dari database
$query = "SELECT * FROM member WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$member = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$member) {
    die("Member tidak ditemukan!");
}

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name   = trim($_POST['name']);
    $phone  = trim($_POST['phone']);
    $point  = (int)$_POST['point'];
    $status = $_POST['status'];

    // Cek duplikat nomor telepon (kecuali nomor member ini sendiri)
    $check_sql = "SELECT id FROM member WHERE phone = ? AND id != ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "si", $phone, $id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $_SESSION['error'] = "Nomor telepon sudah digunakan member lain!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
    mysqli_stmt_close($stmt_check);

    // Update data ke database
    $update_sql = "UPDATE member SET name = ?, phone = ?, point = ?, status = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt_update, "ssisi", $name, $phone, $point, $status, $id);

    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['success'] = "Data member berhasil diupdate!";
        header("Location: ../../pages/admin/member.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal update data: " . mysqli_error($conn);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
    mysqli_stmt_close($stmt_update);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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
            <h2 class="text-center mb-4">Edit Member</h2>
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($member['name']) ?>" required />
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone:</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone']) ?>" required />
                </div>

                <div class="mb-3">
                    <label for="point" class="form-label">Points:</label>
                    <input type="number" name="point" class="form-control" value="<?= (int)$member['point'] ?>" required />
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status:</label>
                    <select name="status" class="form-select" required>
                        <option value="active" <?= $member['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="non-active" <?= $member['status'] === 'non-active' ? 'selected' : '' ?>>Non-Active</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100">Update</button>
            </form>
        </div>
    </div>

    <script>
    <?php if (isset($_SESSION['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: <?= json_encode($_SESSION['success']) ?>,
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: <?= json_encode($_SESSION['error']) ?>,
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
