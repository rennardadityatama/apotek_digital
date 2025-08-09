<?php
require '../service/connection.php';
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['level'];

if (isset($_SESSION['image'])) {
    $profileImage = "../assets/img/admin/" . $_SESSION['image'];
} else {
    $profileImage = "../assets/img/admin/default.jpg";
}

// Tentukan folder gambar berdasarkan role
if ($role === 'Admin') {
    $profileImage = "../assets/img/admin/" . $profileImage;
} else {
    $profileImage = "../assets/img/kasir/" . $profileImage;
}

if (isset($_GET['success'])) {
    $alert = "Swal.fire('Berhasil!', 'Profil berhasil diperbarui.', 'success');";
} elseif (isset($_GET['error'])) {
    $alert = "Swal.fire('Gagal!', 'Terjadi kesalahan saat memperbarui profil.', 'error');";
} elseif (isset($_GET['used'])) {
    $alert = "Swal.fire('Gagal!', 'Username atau email sudah digunakan.', 'error');";
} elseif (isset($_GET['empty'])) {
    $alert = "Swal.fire('Kosong!', 'Username dan email tidak boleh kosong.', 'warning');";
} else {
    $alert = "";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profil <?= htmlspecialchars($role) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/main.css">
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Healthy Mart</h2>
        <a href="../dashboard.php"><i class="fas fa-home"></i> Beranda</a>
        <a href="../pages/kasir/member_kasir.php"><i class="fas fa-user"></i> Data Member</a>
        <a href="../pages/kasir/laporan.php"><i class="fas fa-clipboard"></i> Laporan</a>
        <a href="../pages/kasir/transaksi.php"><i class="fas fa-plus"></i> Transaksi Baru</a>
        <a class="active" href="../profile/index.php"><i class="fas fa-user-circle"></i> Profil</a>
    </div>

    <!-- Header -->
    <div class="header">
        <a href="profile/index.php" style="text-decoration: none; color: inherit;">
            <div class="profile-box">
                <img src="<?= $profileImage ?>" alt="Profile">
                <div>
                    <?= $username ?><br>
                    <small>Role: <?= $role ?></small>
                </div>
            </div>
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="mb-4">Profil <?= htmlspecialchars($role) ?></h1>
        <div class="card shadow card-profile">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Profil <?= htmlspecialchars($role) ?></h5>
                <img src="<?= $profileImage ?>" alt="Foto Profil" class="rounded-circle border border-white" style="width:60px; height:60px; object-fit:cover;">
            </div>
            <div class="card-body">
                <form action="update_profile.php" method="POST" id="profileForm">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-user me-2"></i>Username</label>
                        <div class="input-group">
                            <input type="text" name="username" class="form-control profile-input" value="<?= htmlspecialchars($username) ?>" readonly>
                            <button type="button" class="btn btn-outline-secondary edit-btn">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                        <div class="input-group">
                            <input type="email" name="email" class="form-control profile-input" value="<?= htmlspecialchars($email) ?>" readonly>
                            <button type="button" class="btn btn-outline-secondary edit-btn">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-user-tag me-2"></i>Role</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($role) ?>" readonly disabled>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-success d-none" id="saveBtn"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
                        <a href="../auth/logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (!empty($alert)): ?>
        <script>
            <?= $alert ?>
        </script>
    <?php endif; ?>

    <!-- Script -->
    <script>
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                input.readOnly = false;
                input.classList.add('editing');
                document.getElementById('saveBtn').classList.remove('d-none');
            });
        });
    </script>
</body>

</html>