<?php
session_start();
require '../../service/connection.php';
require '../../service/utility.php';

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

$target_dir = "../../assets/img/product/";

// Fungsi cek duplikat nama produk
function isProductNameDuplicate($conn, $productName, $excludeId = null)
{
    if ($excludeId) {
        $sql = "SELECT COUNT(*) as count FROM products WHERE product_name = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $productName, $excludeId);
    } else {
        $sql = "SELECT COUNT(*) as count FROM products WHERE product_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $productName);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['count'] > 0;
}

// === HANDLE DELETE ===
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];

    // Cek stok produk dulu
    $stmtCheck = $conn->prepare("SELECT stok, image FROM products WHERE id = ?");
    $stmtCheck->bind_param("i", $delete_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $product = $resultCheck->fetch_assoc();

    if (!$product) {
        $_SESSION['error'] = 'Produk tidak ditemukan!';
        header('Location: produk_kasir.php');
        exit();
    }

    if ($product['stok'] > 0) {
        $_SESSION['error'] = 'Produk tidak bisa dihapus karena stok masih tersedia.';
        header('Location: produk_kasir.php');
        exit();
    } else {
        $queryDelete = "DELETE FROM products WHERE id = ?";
        $stmtDelete = $conn->prepare($queryDelete);
        $stmtDelete->bind_param("i", $delete_id);
        if ($stmtDelete->execute()) {
            $imagePath = $target_dir . $product['image'];
            if (file_exists($imagePath)) unlink($imagePath);

            if (isset($_SESSION['cart'][$delete_id])) {
                unset($_SESSION['cart'][$delete_id]);
            }

            $_SESSION['success'] = 'Produk berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus produk!';
        }
        $stmtDelete->close();
        header('Location: produk_kasir.php');
        exit();
    }
}

// === HANDLE INSERT / UPDATE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        // --- PROSES TAMBAH PRODUK ---
        $product_name  = trim($_POST['product_name']);
        $barcode       = $_POST['barcode'] ?: strval(mt_rand(10000000, 99999999)); // 8 digit
        $fid_kategori  = $_POST['kategori'];
        $harga_awal    = $_POST['harga_awal'];
        $harga_jual    = $_POST['harga_jual'];
        $margin        = $harga_jual - $harga_awal;
        $stok          = $_POST['stok'];
        $expired_at    = !empty($_POST['expired_at']) ? $_POST['expired_at'] : null;
        $description   = $_POST['description'];
        $image         = $_FILES['image'];
        $image_name    = $image['name'] ? uniqid() . "_" . basename($image["name"]) : '';
        $target_file   = $target_dir . $image_name;
        $allowed_ext   = ['jpg', 'jpeg', 'png', 'gif'];

        // Validasi gambar
        if ($image['name'] && (!in_array(strtolower(pathinfo($image["name"], PATHINFO_EXTENSION)), $allowed_ext) || $image["size"] > 2 * 1024 * 1024)) {
            $_SESSION['error'] = 'Format atau ukuran gambar tidak valid!';
            header('Location: produk_kasir.php');
            exit();
        }

        // Cek duplikat nama produk
        if (isProductNameDuplicate($conn, $product_name)) {
            $_SESSION['error'] = 'Nama produk sudah ada!';
            header('Location: produk_kasir.php');
            exit();
        }

        if (!$image['name']) {
            $_SESSION['error'] = 'Gambar wajib diupload saat tambah produk!';
            header('Location: produk_kasir.php');
            exit();
        }

        $ext = strtolower(pathinfo($image["name"], PATHINFO_EXTENSION));
        $image_name = mt_rand(100000, 999999) . "." . $ext;
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($image["tmp_name"], $target_file)) {
            $sql = "INSERT INTO products (product_name, barcode, fid_kategori, harga_awal, harga_jual, margin, stok, expired_at, image, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssiddidsss",
                $product_name,
                $barcode,
                $fid_kategori,
                $harga_awal,
                $harga_jual,
                $margin,
                $stok,
                $expired_at,
                $image_name,
                $description
            );
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Produk berhasil ditambahkan!';
                header('Location: produk_kasir.php');
                exit();
            } else {
                $_SESSION['error'] = 'Gagal menambahkan produk!';
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Gagal upload gambar!';
        }
    } elseif ($action === 'update') {
        // --- PROSES UPDATE PRODUK ---
        $id           = $_POST['id'];
        $product_name = trim($_POST['product_name']);
        $barcode      = $_POST['barcode'];
        $fid_kategori = $_POST['kategori'];
        $harga_awal   = $_POST['harga_awal'];
        $harga_jual   = $_POST['harga_jual'];
        $margin       = $harga_jual - $harga_awal;
        $stok         = $_POST['stok'];
        $expired_at   = isset($_POST['expired_at']) && $_POST['expired_at'] !== '' ? $_POST['expired_at'] : null;
        $description  = $_POST['description'];
        $image        = $_FILES['image'];

        // Cek duplikat nama produk
        if (isProductNameDuplicate($conn, $product_name, $id)) {
            $_SESSION['error'] = 'Nama produk sudah ada!';
            header('Location: produk_kasir.php');
            exit();
        }

        // Ambil data lama
        $get_old = $conn->prepare("SELECT harga_awal, harga_jual, margin, image, stok, expired_at FROM products WHERE id = ?");
        $get_old->bind_param("i", $id);
        $get_old->execute();
        $old_data = $get_old->get_result()->fetch_assoc();
        $old_image = $old_data['image'];
        $old_stok = (int)$old_data['stok'];
        $get_old->close();

        // Jika input stok kosong, gunakan 0
        $stok_baru = is_numeric($stok) ? (int)$stok : 0;
        // Tambahkan stok baru ke stok lama
        $stok_total = $old_stok + $stok_baru;

        // Fallback data lama jika field kosong
        $product_name = $product_name !== '' ? $product_name : $old_data['product_name'];
        $barcode      = $barcode !== '' ? $barcode : $old_data['barcode'];
        $fid_kategori = $fid_kategori !== '' ? $fid_kategori : $old_data['fid_kategori'];
        $harga_awal   = $harga_awal !== '' ? floatval($harga_awal) : $old_data['harga_awal'];
        $harga_jual   = $harga_jual !== '' ? floatval($harga_jual) : $old_data['harga_jual'];
        $margin       = $harga_jual - $harga_awal;
        $expired_at   = $expired_at !== null ? $expired_at : $old_data['expired_at'];
        $description  = $description !== '' ? $description : $old_data['description'];

        if ($image['name']) {
            $ext = strtolower(pathinfo($image["name"], PATHINFO_EXTENSION));
            $image_name = mt_rand(100000, 999999) . "." . $ext;
            $target_file = $target_dir . $image_name;

            if (move_uploaded_file($image["tmp_name"], $target_file)) {
                if (file_exists($target_dir . $old_image)) unlink($target_dir . $old_image);
                $sql = "UPDATE products SET product_name=?, barcode=?, fid_kategori=?, harga_awal=?, harga_jual=?, margin=?, stok=?, expired_at=?, image=?, description=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssidddisssi",
                    $product_name,
                    $barcode,
                    $fid_kategori,
                    $harga_awal,
                    $harga_jual,
                    $margin,
                    $stok_total,
                    $expired_at,
                    $image_name,
                    $description,
                    $id
                );
            } else {
                $_SESSION['error'] = 'Gagal upload gambar baru!';
                header('Location: produk_kasir.php');
                exit();
            }
        } else {
            $sql = "UPDATE products SET product_name=?, barcode=?, fid_kategori=?, harga_awal=?, harga_jual=?, margin=?, stok=?, expired_at=?, image=?, description=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssidddisssi",
                $product_name,
                $barcode,
                $fid_kategori,
                $harga_awal,
                $harga_jual,
                $margin,
                $stok_total,
                $expired_at,
                $old_image,
                $description,
                $id
            );
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Produk berhasil diperbarui!';
            header('Location: produk_kasir.php');
            exit();
        } else {
            $_SESSION['error'] = 'Gagal memperbarui produk!';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product</title>
    <link href="../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

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
            <a href="../../dashboard.php" class="menu-link">
                <i class="fa fa-home"></i> Beranda
            </a>
        </div>
        <div class="menu-item">
            <a href="kategori_kasir.php" class="menu-link">
                <i class="fa fa-list"></i> Data Kategori Produk
            </a>
        </div>
        <div class="menu-item active">
            <a href="produk_kasir.php" class="menu-link">
                <i class="fa fa-box"></i> Data Produk
            </a>
        </div>
        <div class="menu-item">
            <a href="member_kasir.php" class="menu-link">
                <i class="fa fa-user"></i> Data Member
            </a>
        </div>
        <div class="menu-item">
            <a href="laporan.php" class="menu-link">
                <i class="fa fa-file-alt"></i> Laporan
            </a>
        </div>
        <div class="menu-item">
            <a href="transaksi.php" class="menu-link">
                <i class="fa fa-plus"></i> Transaksi Baru
            </a>
        </div>
    </div>


    <div class="main-content" id="main-content">
        <div class="data-container">
            <div class="data-header">
                <h2>Data Produk</h2>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createProductModal">
                    <i class="fas fa-plus"></i> Tambah Produk
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Barcode</th>
                            <th>Kategori</th>
                            <th>Harga Awal</th>
                            <th>Harga Jual</th>
                            <th>Margin</th>
                            <th>Stok</th>
                            <th>Expired At</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT products.*, category.category 
                        FROM products 
                        LEFT JOIN category ON products.fid_kategori = category.id";
                        $result = mysqli_query($conn, $query);
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)) :
                            $margin = $row["harga_jual"] - $row["harga_awal"];
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <img src="../../assets/img/product/<?= htmlspecialchars($row["image"]); ?>"
                                        alt="<?= htmlspecialchars($row["product_name"]); ?>"
                                        style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                </td>
                                <td><?= htmlspecialchars($row["product_name"]); ?></td>
                                <td>
                                    <div>
                                        <svg id="barcode<?= $row['id']; ?>"></svg>
                                        <div style="font-size: 0.75rem;"><?= htmlspecialchars($row["barcode"]); ?></div>
                                    </div>
                                    <script>
                                        JsBarcode("#barcode<?= $row['id']; ?>", "<?= $row['barcode']; ?>", {
                                            format: "CODE128",
                                            width: 1.5,
                                            height: 40,
                                            displayValue: false
                                        });
                                    </script>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($row["category"]); ?></span>
                                </td>
                                <td class="text-primary">Rp <?= number_format($row["harga_awal"], 0, ',', '.'); ?></td>
                                <td class="text-success">Rp <?= number_format($row["harga_jual"], 0, ',', '.'); ?></td>
                                <td class="text-danger">Rp <?= number_format($margin, 0, ',', '.'); ?></td>
                                <td><?= $row["stok"]; ?></td>
                                <td><?= !empty($row["expired_at"]) && $row["expired_at"] !== "0000-00-00" ? htmlspecialchars($row["expired_at"]) : '-'; ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm editProduct"
                                        data-id="<?= $row['id']; ?>"
                                        data-name="<?= htmlspecialchars($row['product_name']); ?>"
                                        data-barcode="<?= htmlspecialchars($row['barcode']); ?>"
                                        data-kategori="<?= $row['fid_kategori']; ?>"
                                        data-harga_awal="<?= $row['harga_awal']; ?>"
                                        data-harga_jual="<?= $row['harga_jual']; ?>"
                                        data-margin="<?= $margin; ?>"
                                        data-stok="<?= $row['stok']; ?>"
                                        data-expired_at="<?= $row['expired_at']; ?>"
                                        data-updated_at="<?= $row['updated_at'] ?>"
                                        data-description="<?= htmlspecialchars($row['description']); ?>"
                                        data-image="<?= $row['image']; ?>"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="produk_kasir.php?delete_id=<?= $row['id']; ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Yakin ingin menghapus produk ini?')"
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
    </div>

    <!-- Modal Tambah Produk -->
    <div class="modal fade" id="createProductModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form action="produk_kasir.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel">
                            <i class="fas fa-folder-plus"></i> Tambah Produk
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="namaProduk" class="form-label">Nama Produk</label>
                                    <input type="text" name="product_name" class="form-control" id="namaProdukCreate">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="barcodeInput" class="form-label">Barcode</label>
                                    <input type="text" name="barcode" id="barcodeInputCreate" class="form-control"
                                        maxlength="20" pattern="\d*" inputmode="numeric"
                                        placeholder="Scan barcode atau input manual">
                                    <div id="barcodePreview" class="mt-2"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="kategori" class="form-label">Kategori</label>
                                    <select name="kategori" class="form-control" id="kategoriCreate">
                                        <option value="">Pilih Kategori</option>
                                        <?php
                                        $kategori = $conn->query("SELECT * FROM category");
                                        while ($row = $kategori->fetch_assoc()) {
                                            echo "<option value ='{$row['id']}'>{$row['category']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="harga_awal" class="form-label">Harga Awal</label>
                                    <input type="number" name="harga_awal" class="form-control" id="harga_awal_create" oninput="hitungMarginCreate()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="harga_jual" class="form-label">Harga Jual</label>
                                    <input type="number" name="harga_jual" class="form-control" id="harga_jual_create" oninput="hitungMarginCreate()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="margin" class="form-label">Margin</label>
                                    <input type="number" name="margin" class="form-control" id="margin_create" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stok" class="form-label">Tambah Stok</label>
                                    <input type="number" name="stok" class="form-control" id="stok_create" min="0" value="0">
                                    <div class="form-text" id="stokLamaText"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="expired_at" class="form-label">Expired At</label>
                                    <input type="date" name="expired_at" class="form-control" id="expired_at_create">
                                    <div class="form-text" id="expiredAtText"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="fotoProduct" class="form-label">Upload Foto</label>
                                    <input type="file" name="image" class="form-control" id="fotoProductCreate">
                                    <div class="form-text" id="currentImageText"></div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" name="description" class="form-control" id="descriptionCreate" required readonly
                                    onfocus="this.removeAttribute('readonly');">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="save_products" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Produk -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form action="produk_kasir.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editProductId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="fas fa-edit"></i> Edit Produk
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="namaProdukEdit" class="form-label">Nama Produk</label>
                                    <input type="text" name="product_name" class="form-control" id="namaProdukEdit">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="barcodeInputEdit" class="form-label">Barcode</label>
                                    <input type="text" name="barcode" id="barcodeInputEdit" class="form-control"
                                        oninput="generateBarcode(); this.value = this.value.replace(/[^0-9]/g, '').slice(0,8);"
                                        maxlength="8" pattern="\d{8}" inputmode="numeric"
                                        placeholder="Kosongkan jika ingin di-generate otomatis">
                                    <div id="barcodePreviewEdit" class="mt-2"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="kategoriEdit" class="form-label">Kategori</label>
                                    <select name="kategori" class="form-control" id="kategoriEdit">
                                        <option value="">Pilih Kategori</option>
                                        <?php
                                        $kategori = $conn->query("SELECT * FROM category");
                                        while ($row = $kategori->fetch_assoc()) {
                                            echo "<option value ='{$row['id']}'>{$row['category']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="harga_awal_edit" class="form-label">Harga Awal</label>
                                    <input type="number" name="harga_awal" class="form-control" id="harga_awal_edit" oninput="hitungMarginEdit()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="harga_jual_edit" class="form-label">Harga Jual</label>
                                    <input type="number" name="harga_jual" class="form-control" id="harga_jual_edit" oninput="hitungMarginEdit()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="margin_edit" class="form-label">Margin</label>
                                    <input type="number" name="margin" class="form-control" id="margin_edit" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stok_edit" class="form-label">Tambah Stok</label>
                                    <input type="number" name="stok" class="form-control" id="stok_edit" min="0" value="0">
                                    <div class="form-text" id="stokLamaTextEdit"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="expired_at_edit" class="form-label">Expired At</label>
                                    <input type="date" name="expired_at" class="form-control" id="expired_at_edit">
                                    <div class="form-text" id="expiredAtTextEdit"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="fotoProductEdit" class="form-label">Upload Foto</label>
                                    <input type="file" name="image" class="form-control" id="fotoProductEdit">
                                    <div class="form-text" id="currentImageTextEdit"></div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="descriptionEdit" class="form-label">Description</label>
                                    <input type="text" name="description" class="form-control" id="descriptionEdit" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="save_products" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>

    <script>
        function generateBarcode() {
            const value = document.getElementById("barcodeInput").value;
            const barcodeDiv = document.getElementById("barcodePreview");
            barcodeDiv.innerHTML = value ?
                '<svg id="barcodeSvg"></svg>' :
                '';
            if (value) {
                JsBarcode("#barcodeSvg", value, {
                    format: "CODE128",
                    width: 2,
                    height: 40,
                    displayValue: true
                });
            }
        }

        function validateBarcode(input) {
            // Hapus semua karakter yang bukan angka
            input.value = input.value.replace(/\D/g, '');

            // Batasi hanya 8 digit
            if (input.value.length > 8) {
                input.value = input.value.slice(0, 8);
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const toggleIcon = document.querySelector('.toggle-btn i');

            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('full-width');
            toggleIcon.classList.toggle('fa-chevron-left');
            toggleIcon.classList.toggle('fa-chevron-right');
        }

        function toggleDropdown() {
            document.getElementById("dropdownMenu").classList.toggle("show");
        }

        function hitungMargin() {
            const hargaAwal = parseFloat(document.getElementById('harga_awal').value) || 0;
            const hargaJual = parseFloat(document.getElementById('harga_jual').value) || 0;
            const margin = hargaJual - hargaAwal;
            document.getElementById('margin').value = margin;
        }

        function hitungMarginCreate() {
            const hargaAwal = parseFloat(document.getElementById('harga_awal_create').value) || 0;
            const hargaJual = parseFloat(document.getElementById('harga_jual_create').value) || 0;
            const margin = hargaJual - hargaAwal;
            document.getElementById('margin_create').value = margin;
        }

        function hitungMarginEdit() {
            const hargaAwal = parseFloat(document.getElementById('harga_awal_edit').value) || 0;
            const hargaJual = parseFloat(document.getElementById('harga_jual_edit').value) || 0;
            const margin = hargaJual - hargaAwal;
            document.getElementById('margin_edit').value = margin;
        }

        document.addEventListener("click", function(event) {
            const dropdown = document.getElementById("dropdownMenu");
            const profileIcon = document.querySelector(".profile-icon");
            if (!dropdown.contains(event.target) && !profileIcon.contains(event.target)) {
                dropdown.classList.remove("show");
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            const modal = new bootstrap.Modal(document.getElementById("productModal"));
            const fields = {
                id: "productId",
                name: "namaProduk",
                barcode: "barcodeInput",
                kategori: "kategori",
                harga_awal: "harga_awal",
                harga_jual: "harga_jual",
                margin: "margin",
                stok: "stok",
                expired_at: "expired_at", // <-- ganti jadi expired_at
                description: "description"
            };
            const imageText = document.getElementById("currentImageText");
            const modalLabel = document.getElementById("modalLabel");

            document.querySelectorAll(".editProduct").forEach(btn => {
                btn.addEventListener("click", () => {
                    for (const key in fields) {
                        const input = document.getElementById(fields[key]);
                        if (input) {
                            input.value = btn.dataset[key] || '';
                        }
                    }
                    // Tampilkan stok lama
                    document.getElementById("stok").value = 0;
                    document.getElementById("stokLamaText").innerHTML = "Stok saat ini: <strong>" + btn.dataset.stok + "</strong>";
                    imageText.innerHTML = `Gambar saat ini: <strong>${btn.dataset.image}</strong>`;
                    modalLabel.innerHTML = '<i class="fas fa-edit"></i> Edit Produk';
                    generateBarcode();

                    // === Tampilkan tanggal expired lama ===
                    const expiredInfo = btn.dataset.expired_at ? btn.dataset.expired_at : '-';
                    let expiredText = document.getElementById("expiredAtText");
                    if (!expiredText) {
                        expiredText = document.createElement("div");
                        expiredText.id = "expiredAtText";
                        expiredText.className = "form-text";
                        document.getElementById("expired_at").parentNode.appendChild(expiredText);
                    }
                    expiredText.innerHTML = "Tanggal expired sebelum update: <strong>" + expiredInfo + "</strong>";

                    modal.show();
                });
            });

            document.getElementById("productModal").addEventListener("hidden.bs.modal", () => {
                for (const key in fields) {
                    const input = document.getElementById(fields[key]);
                    if (input) input.value = '';
                }
                document.getElementById("fotoProduct").value = '';
                imageText.innerHTML = '';
                document.getElementById("barcodePreview").innerHTML = '';
                modalLabel.innerHTML = '<i class="fas fa-folder-plus"></i> Tambah Produk';
                document.getElementById("stokLamaText").innerHTML = '';

                let expiredText = document.getElementById("expiredAtText");
                if (expiredText) expiredText.innerHTML = '';
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".editProduct").forEach(btn => {
                btn.addEventListener("click", function() {
                    document.getElementById("editProductId").value = btn.dataset.id;
                    document.getElementById("namaProdukEdit").value = btn.dataset.name;
                    document.getElementById("barcodeInputEdit").value = btn.dataset.barcode;
                    document.getElementById("kategoriEdit").value = btn.dataset.kategori;
                    document.getElementById("harga_awal_edit").value = btn.dataset.harga_awal;
                    document.getElementById("harga_jual_edit").value = btn.dataset.harga_jual;
                    document.getElementById("margin_edit").value = btn.dataset.margin;
                    document.getElementById("stok_edit").value = 0;
                    document.getElementById("expired_at_edit").value = btn.dataset.expired_at;
                    document.getElementById("descriptionEdit").value = btn.dataset.description;
                    document.getElementById("currentImageTextEdit").innerHTML = `Gambar saat ini: <strong>${btn.dataset.image}</strong>`;
                    document.getElementById("stokLamaTextEdit").innerHTML = "Stok saat ini: <strong>" + btn.dataset.stok + "</strong>";
                    document.getElementById("expiredAtTextEdit").innerHTML = "Tanggal expired sebelum update: <strong>" + (btn.dataset.expired_at || '-') + "</strong>";
                    // Tampilkan modal edit
                    var editModal = new bootstrap.Modal(document.getElementById("editProductModal"));
                    editModal.show();
                });
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            var createModal = document.getElementById("createProductModal");
            createModal.addEventListener("shown.bs.modal", function () {
                document.getElementById("barcodeInputCreate").focus();
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            var barcodeInput = document.getElementById("barcodeInputCreate");
            var createModal = document.getElementById("createProductModal");

            // Pastikan input barcode tetap fokus saat modal dibuka
            createModal.addEventListener("shown.bs.modal", function () {
                barcodeInput.focus();
            });

            // Jika barcode diisi (scan), tetap fokus di kolom barcode
            barcodeInput.addEventListener("input", function() {
                // Jika scanner mengirim enter/tab, tetap fokus di barcode
                setTimeout(function() {
                    barcodeInput.focus();
                }, 10);
            });

            barcodeInput.addEventListener("keydown", function(e) {
                // Jika scanner mengirim Tab atau Enter, tetap fokus di barcode
                if (e.key === "Tab" || e.key === "Enter") {
                    e.preventDefault();
                    barcodeInput.focus();
                    barcodeInput.select();
                }
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