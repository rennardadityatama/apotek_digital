<?php
session_start();
require '../../service/connection.php';

if (!isset($_SESSION['username'])) {
    header('location: ../auth/login.php');
    exit();
}

$username = $_SESSION['username'];
$query = "SELECT * FROM admin WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// DELETE
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Cek produk terkait
    $cekProduk = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE fid_kategori = ?");
    $cekProduk->bind_param("i", $delete_id);
    $cekProduk->execute();
    $resultProduk = $cekProduk->get_result()->fetch_assoc();
    $cekProduk->close();

    if ($resultProduk['total'] > 0) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Gagal!',
            'text' => 'Kategori tidak bisa dihapus karena masih ada produk yang menggunakan kategori ini!'
        ];
        header("Location: kategori.php");
        exit();
    }

    $del = $conn->prepare("DELETE FROM category WHERE id = ?");
    $del->bind_param("i", $delete_id);
    if ($del->execute()) {
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Kategori berhasil dihapus!'
        ];
    } else {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Gagal!',
            'text' => 'Gagal menghapus kategori!'
        ];
    }
    header("Location: kategori.php");
    exit();
}

// INSERT / UPDATE
if (isset($_POST['save_category'])) {
    $category = htmlspecialchars(trim($_POST['category']), ENT_QUOTES, 'UTF-8');
    $id = isset($_POST['id']) ? $_POST['id'] : null;

    if (empty($id)) {
        // Cek duplikat
        $check = $conn->prepare("SELECT id FROM category WHERE category = ?");
        $check->bind_param("s", $category);
        $check->execute();
        $check_result = $check->get_result();
        if ($check_result->num_rows > 0) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Gagal!',
                'text' => 'Nama kategori sudah ada, gunakan nama lain!'
            ];
            header("Location: kategori.php");
            exit();
        }
        $sql = "INSERT INTO category (category) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Kategori berhasil ditambahkan!'
        ];
        header("Location: kategori.php");
        exit();
    } else {
        $sql = "UPDATE category SET category = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $category, $id);
        if ($stmt->execute()) {
            $_SESSION['swal'] = [
                'icon' => 'success',
                'title' => 'Berhasil!',
                'text' => 'Kategori berhasil diperbarui!'
            ];
        } else {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Gagal!',
                'text' => 'Gagal memperbarui kategori!'
            ];
        }
        header("Location: kategori.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kategori</title>
    <link href="../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background: #f4f6f9;
        }

        .sidebar {
            width: 250px;
            background: white;
            padding: 20px;
            height: 580px;
            margin-top: 100px;
            border-radius: 10px;
            position: fixed;
            left: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar.hidden {
            left: -250px;
        }

        .sidebar h2 {
            text-align: center;
            color: #4CAF50;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 10px;
            text-decoration: none;
            color: inherit;
            border-radius: 5px;
        }

        .menu-item i {
            margin-right: 10px;
        }

        .menu-item a:hover,
        .menu-item.active a {
            background: #e0f2f1;
        }

        .main-content {
            min-height: 100vh;
            /* Pastikan ketinggian minimal 100% viewport */
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }

        .main-content.full-width {
            margin-left: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px;
            width: calc(100% - 40px);
            position: fixed;
            top: 20px;
            left: 20px;
            height: 60px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .header .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }

        .header .toggle-btn {
            font-size: 24px;
            cursor: pointer;
            color: #4CAF50;
        }

        .profile-container {
            position: relative;
            display: inline-block;
        }

        .profile-icon {
            font-size: 24px;
            cursor: pointer;
            color: #4CAF50;
            background: #e0f2f1;
            border-radius: 50%;
            padding: 10px;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 200px;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .dropdown-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .dropdown-item {
            padding: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border-radius: 5px;
        }

        .dropdown-item:hover {
            background: #e0f2f1;
        }

        .data-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 76px;
            /* Sesuaikan dengan tinggi header */
            left: 320px;
            /* Sesuaikan dengan lebar sidebar */
            width: calc(100% - 340px);
            /* Sesuaikan dengan ukuran layar */
            height: calc(100vh - 140px);
            /* Sesuaikan agar tidak melebihi layar */
            overflow-y: auto;
            /* Tambahkan scroll jika perlu */
        }

        .data-container h2 {
            font-size: 24px;
            font-weight: bold;
        }

        .data-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-fixed-height {
            min-height: 250px;
            /* Tinggi minimum kartu */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-img-top {
            max-width: 100%;
            height: 200px;
            /* Perbesar gambar */
            object-fit: cover;
            border-radius: 10px;
        }

        .category-title {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            height: 100px;
            /* Sesuaikan tinggi agar tetap di tengah */
            font-size: 1.2rem;
            font-weight: bold;
        }

        .btn-group-custom {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-custom {
            width: auto;
            padding: 6px 12px;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="header">
        <div class="toggle-btn" onclick="toggleSidebar()">
            <i class="fa fa-chevron-left"></i>
        </div>
        <div class="logo">BatokMart</div>

        <div class="profile-container">
            <i class="fa fa-smile profile-icon" onclick="toggleDropdown()"></i>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-header">
                    <img src="../../assets/img/admin/<?= $admin['image']; ?>" alt="User Image" id="userImage">
                    <div>
                        <strong id="username"><?= $admin['username']; ?></strong>
                        <p id="email" style="font-size: 12px; margin: 0;"><?= $admin['email']; ?></p>
                    </div>
                </div>
                <div class="dropdown-item logout">
                    <a href="../../auth/logout.php">
                        <i class="fa fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambahkan id="sidebar" -->
    <div class="sidebar" id="sidebar">
        <h2>BatokMart</h2>
        <div class="menu-item">
            <a href="../../super_dashboard.php" class="menu-link">
                <i class="fa fa-home"></i> Beranda
            </a>
        </div>
        <div class="menu-item active">
            <a href="kategori.php" class="menu-link">
                <i class="fa fa-list"></i> Data Kategori Barang
            </a>
        </div>
        <div class="menu-item">
            <a href="produk.php" class="menu-link">
                <i class="fa fa-box"></i> Data Barang
            </a>
        </div>
        <div class="menu-item">
            <a href="member.php" class="menu-link">
                <i class="fa fa-users"></i> Data Member
            </a>
        </div>
        <div class="menu-item">
            <a href="admin.php" class="menu-link">
                <i class="fa fa-user"></i> Data Admin
            </a>
        </div>
        <div class="menu-item">
            <a href="laporan.php" class="menu-link">
                <i class="fa fa-file-alt"></i> Laporan
            </a>
        </div>
    </div>


    <div class="main-content" id="main-content">
        <div class="data-container">
            <div class="data-header">
                <h2>Data Kategori</h2>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#kategoriModal">
                    <i class="fas fa-plus"></i> Tambah Kategori
                </button>
            </div>

            <div class="row mt-3 g-3">
                <?php
                $query = "SELECT * FROM category";
                $result = mysqli_query($conn, $query);
                ?>

                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                        <div class="card text-white h-100 shadow-sm" style="border-radius: 12px; background-color: #6c757d;">
                            <div class="card-body p-2 text-center text-dark d-flex flex-column justify-content-between">
                                <h6 class="mb-2" style="font-size: 0.9rem;"><?= htmlspecialchars($row["category"]); ?></h6>

                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-warning btn-sm editKategori"
                                        data-id="<?= $row['id']; ?>"
                                        data-category="<?= htmlspecialchars($row['category']); ?>"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="../../activities/admin/delete_category.php?id=<?= $row['id']; ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Yakin ingin menghapus kategori ini?')"
                                        title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="kategoriModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="kategori.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="kategoriId"> <!-- untuk edit -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel">
                            <i class="fas fa-folder-plus"></i> Tambah Kategori
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <label for="namaKategori" class="form-label">Nama Kategori</label>
                            <input type="text" name="category" class="form-control" id="namaKategori" placeholder="Masukkan nama kategori">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="save_category" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var mainContent = document.getElementById('main-content');
            var toggleIcon = document.querySelector('.toggle-btn i');
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                mainContent.classList.remove('full-width');
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-left');
            } else {
                sidebar.classList.add('hidden');
                mainContent.classList.add('full-width');
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const editBtns = document.querySelectorAll(".editKategori");
            const kategoriModal = new bootstrap.Modal(document.getElementById("kategoriModal"));
            const idInput = document.getElementById("kategoriId");
            const namaInput = document.getElementById("namaKategori");
            const modalLabel = document.getElementById("modalLabel");

            editBtns.forEach(btn => {
                btn.addEventListener("click", () => {
                    idInput.value = btn.dataset.id;
                    namaInput.value = btn.dataset.category;
                    modalLabel.innerHTML = '<i class="fas fa-edit"></i> Edit Kategori';
                    kategoriModal.show();
                });
            });

            document.getElementById("kategoriModal").addEventListener("hidden.bs.modal", () => {
                idInput.value = '';
                namaInput.value = '';
                modalLabel.innerHTML = '<i class="fas fa-folder-plus"></i> Tambah Kategori';
            });
        });

        function toggleDropdown() {
            document.getElementById("dropdownMenu").classList.toggle("show");
        }

        document.addEventListener("click", function(event) {
            var dropdown = document.getElementById("dropdownMenu");
            var profileIcon = document.querySelector(".profile-icon");
            if (!dropdown.contains(event.target) && !profileIcon.contains(event.target)) {
                dropdown.classList.remove("show");
            }
        });

        <?php if (isset($_SESSION['swal'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['swal']['icon'] ?>',
                title: '<?= $_SESSION['swal']['title'] ?>',
                text: '<?= $_SESSION['swal']['text'] ?>',
                timer: 2000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['swal']); ?>
        <?php endif; ?>
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.btn-hapus').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    Swal.fire({
                        title: 'Yakin ingin menghapus kategori ini?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = url;
                        }
                    });
                });
            });
        });
    </script>
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