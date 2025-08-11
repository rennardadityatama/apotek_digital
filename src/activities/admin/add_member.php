<?php
session_start();
require "../../service/connection.php";
require "../../service/utility.php";

date_default_timezone_set('Asia/Jakarta');

// Ambil role dari session
$role = $_SESSION['level'] ?? 'Kasir'; // default Kasir jika tidak ada

$name = $phone = "";
$point = 0;
$status = "non-active"; // Default status tidak bisa diubah

// Tentukan halaman redirect sesuai role
if ($role === 'Admin') {
    $redirectPage = "../../pages/admin/member.php";
} else {
    $redirectPage = "../../pages/kasir/member_kasir.php";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Cek nomor telepon sudah ada atau belum
    $check_sql = "SELECT id FROM member WHERE phone = ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "s", $phone);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $_SESSION['error'] = "Nomor telepon sudah terdaftar!";
        header("Location: " . $redirectPage);
        exit();
    }
    mysqli_stmt_close($stmt_check);

    $created_at = date("Y-m-d H:i:s");
    $point = 0; // default nilai 0

    $sql = "INSERT INTO member (name, phone, point, created_at, status)
        VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssiss", $name, $phone, $point, $created_at, $status);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Member berhasil ditambahkan!";
        header("Location: " . $redirectPage);
        exit();
    } else {
        $_SESSION['error'] = "Gagal tambah member: " . mysqli_error($conn);
        header("Location: " . $redirectPage);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <h2 class="text-center mb-4">Tambah Member</h2>
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone:</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>

                <!-- Tampilkan status (disabled), tetap kirim value dengan hidden input -->
                <div class="mb-3">
                    <label for="status" class="form-label">Status:</label>
                    <input type="text" class="form-control" value="non-active" readonly>
                    <input type="hidden" name="status" value="non-active">
                </div>

                <button type="submit" class="btn btn-primary w-100">Submit</button>
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