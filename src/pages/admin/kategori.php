<?php
session_start();
require '../../service/connection.php';

if (!isset($_SESSION['username'])) {
    header('location: ../auth/login.php');
    exit();
}

$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['level'];

if (isset($_SESSION['image'])) {
    $profileImage = "../../assets/img/admin/" . $_SESSION['image'];
} else {
    $profileImage = "../../assets/img/admin/default.jpg";
}

$query = "SELECT * FROM admin WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// DELETE
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Ambil nama gambar sebelum hapus
    $getImg = $conn->prepare("SELECT image FROM category WHERE id = ?");
    $getImg->bind_param("i", $delete_id);
    $getImg->execute();
    $imgResult = $getImg->get_result()->fetch_assoc();
    $getImg->close();

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
        // Hapus gambar dari folder jika ada
        if (!empty($imgResult['image']) && file_exists("../../assets/img/kategori/" . $imgResult['image'])) {
            unlink("../../assets/img/kategori/" . $imgResult['image']);
        }
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

    // Proses upload gambar
    $imageName = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = mt_rand(10000000, 99999999) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], "../../assets/img/category/" . $imageName);
    }

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
        $sql = "INSERT INTO category (category, image) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $category, $imageName);
        $stmt->execute();
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Kategori berhasil ditambahkan!'
        ];
        header("Location: kategori.php");
        exit();
    } else {
        // Cek duplikat nama kategori (case-insensitive, kecuali id yang sedang diedit)
        $check = $conn->prepare("SELECT id FROM category WHERE LOWER(category) = LOWER(?) AND id != ?");
        $check->bind_param("si", $category, $id);
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

        // Jika ada gambar baru, hapus gambar lama
        if (!empty($imageName)) {
            $getImg = $conn->prepare("SELECT image FROM category WHERE id = ?");
            $getImg->bind_param("i", $id);
            $getImg->execute();
            $imgResult = $getImg->get_result()->fetch_assoc();
            $getImg->close();
            if (!empty($imgResult['image']) && file_exists("../../assets/img/category/" . $imgResult['image'])) {
                unlink("../../assets/img/category/" . $imgResult['image']);
            }
            $sql = "UPDATE category SET category = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $category, $imageName, $id);
        } else {
            $sql = "UPDATE category SET category = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $category, $id);
        }

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
    <link rel="stylesheet" href="../../css/main.css">
    <style>
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

<body>
    <div class="sidebar">
        <h2>Healthy Mart</h2>
        <a href="../../super_dashboard.php"><i class="fas fa-home"></i> Beranda</a>
        <a href="../admin/admin.php"><i class="fas fa-user"></i> Data Kasir</a>
        <a href="../admin/member.php"><i class="fas fa-users"></i> Data Member</a>
        <a class="active" href="../admin/kategori.php"><i class="fas fa-list"></i> Data Kategori</a>
        <a href="../admin/produk.php"><i class="fas fa-box"></i> Data Produk</a>
        <a href="../admin/laporan.php"><i class="fas fa-clipboard"></i> Laporan</a>
    </div>
    
    <!-- Header -->
    <div class="header">
        <a href="../../profile/index.php" style="text-decoration: none; color: inherit;">
            <div class="profile-box">
                <img src="<?= $profileImage ?>" alt="Profile">
                <div>
                    <?= $username ?><br>
                    <small>Role: <?= $role ?></small>
                </div>
            </div>
        </a>
    </div>

    <div class="main-content" id="main-content">
        <div class="data-container">
            <div class="data-header">
                <h2>Data Kategori</h2>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#kategoriModal">
                    <i class="fas fa-plus"></i> Tambah Kategori
                </button>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Nama Kategori</th>
                            <th>Gambar</th>
                            <th style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM category";
                        $result = mysqli_query($conn, $query);
                        $no = 1;
                        ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row["category"]); ?></td>
                                <td>
                                    <?php if (!empty($row["image"])): ?>
                                        <img src="../../assets/img/category/<?= htmlspecialchars($row["image"]); ?>" alt="Gambar" style="width:60px; height:60px; object-fit:cover;">
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-warning btn-sm editKategori"
                                        data-id="<?= $row['id']; ?>"
                                        data-category="<?= htmlspecialchars($row['category']); ?>"
                                        data-image="<?= htmlspecialchars($row['image']); ?>"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="kategori.php?delete_id=<?= $row['id']; ?>"
                                        class="btn btn-danger btn-sm btn-hapus"
                                        title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
                        <div class="mb-3">
                            <label for="image" class="form-label">Gambar Kategori</label>
                            <input type="file" name="image" class="form-control" id="image" accept="image/*">
                            <div id="previewGambar" style="margin-top:10px;">
                                <!-- Preview gambar lama akan muncul di sini -->
                            </div>
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

        document.addEventListener("DOMContentLoaded", function() {
            const editBtns = document.querySelectorAll(".editKategori");
            const kategoriModal = new bootstrap.Modal(document.getElementById("kategoriModal"));
            const idInput = document.getElementById("kategoriId");
            const namaInput = document.getElementById("namaKategori");
            const modalLabel = document.getElementById("modalLabel");
            const previewGambar = document.getElementById("previewGambar");

            editBtns.forEach(btn => {
                btn.addEventListener("click", () => {
                    idInput.value = btn.dataset.id;
                    namaInput.value = btn.dataset.category;
                    modalLabel.innerHTML = '<i class="fas fa-edit"></i> Edit Kategori';

                    // Tampilkan gambar lama jika ada
                    if (btn.dataset.image) {
                        previewGambar.innerHTML = `<img src="../../assets/img/category/${btn.dataset.image}" alt="Preview" style="max-width:120px;max-height:120px;border-radius:8px;">`;
                    } else {
                        previewGambar.innerHTML = `<span class="text-muted">Tidak ada gambar</span>`;
                    }

                    kategoriModal.show();
                });
            });

            document.getElementById("kategoriModal").addEventListener("hidden.bs.modal", () => {
                idInput.value = '';
                namaInput.value = '';
                modalLabel.innerHTML = '<i class="fas fa-folder-plus"></i> Tambah Kategori';
                previewGambar.innerHTML = '';
            });

            // Preview gambar baru saat upload
            document.getElementById("image").addEventListener("change", function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        previewGambar.innerHTML = `<img src="${ev.target.result}" alt="Preview" style="max-width:120px;max-height:120px;border-radius:8px;">`;
                    }
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
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