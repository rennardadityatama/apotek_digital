<?php
session_start();
require '../../service/connection.php';
require '../../service/utility.php';

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
        header('Location: produk.php');
        exit();
    }

    if ($product['stok'] > 0) {
        // Kalau stok masih ada, larang hapus
        $_SESSION['error'] = 'Produk tidak bisa dihapus karena stok masih tersedia.';
        header('Location: produk.php');
        exit();
    } else {
        // Stok 0, hapus produk dan gambarnya
        $queryDelete = "DELETE FROM products WHERE id = ?";
        $stmtDelete = $conn->prepare($queryDelete);
        $stmtDelete->bind_param("i", $delete_id);
        if ($stmtDelete->execute()) {
            // Hapus gambar
            $imagePath = $target_dir . $product['image'];
            if (file_exists($imagePath)) unlink($imagePath);

            // **Hapus produk dari keranjang juga jika ada**
            if (isset($_SESSION['cart'][$delete_id])) {
                unset($_SESSION['cart'][$delete_id]);
            }

            $_SESSION['success'] = 'Produk berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus produk!';
        }
        $stmtDelete->close();
        header('Location: produk.php');
        exit();
    }
}

// === HANDLE INSERT / UPDATE ===
if (isset($_POST['save_products'])) {
    $id            = $_POST['id'] ?? null;
    $product_name  = trim($_POST['product_name']);
    $barcode       = $_POST['barcode'] ?: strval(mt_rand(10000000, 99999999)); // 8 digit
    $fid_kategori  = $_POST['kategori'];
    $harga_awal    = floatval($_POST['harga_awal']);
    $harga_jual    = floatval($_POST['harga_jual']);
    $margin        = $harga_jual - $harga_awal;
    $stok          = $_POST['stok'];
    $expired_at    = $_POST['expired_at'];
    $description   = $_POST['description'];
    $image         = $_FILES['image'];
    $image_name    = $image['name'] ? uniqid() . "_" . basename($image["name"]) : '';
    $target_file   = $target_dir . $image_name;
    $allowed_ext   = ['jpg', 'jpeg', 'png', 'gif'];

    // Validasi gambar
    if ($image['name'] && (!in_array(strtolower(pathinfo($image["name"], PATHINFO_EXTENSION)), $allowed_ext) || $image["size"] > 2 * 1024 * 1024)) {
        $_SESSION['error'] = 'Format atau ukuran gambar tidak valid!';
        header('Location: produk.php');
        exit();
    }

    // Cek duplikat nama produk
    if (empty($id)) {
        if (isProductNameDuplicate($conn, $product_name)) {
            $_SESSION['error'] = 'Nama produk sudah ada!';
            header('Location: produk.php');
            exit();
        }

        if (!$image['name']) {
            $_SESSION['error'] = 'Gambar wajib diupload saat tambah produk!';
            header('Location: produk.php');
            exit();
        }

        // Nama gambar hanya angka random
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
                header('Location: produk.php');
                exit();
            } else {
                $_SESSION['error'] = 'Gagal menambahkan produk!';
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Gagal upload gambar!';
        }
    } else {
        if (isProductNameDuplicate($conn, $product_name, $id)) {
            $_SESSION['error'] = 'Nama produk sudah ada!';
            header('Location: produk.php');
            exit();
        }

        // Ambil stok lama dari database
        $get_old = $conn->prepare("SELECT image, stok FROM products WHERE id = ?");
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

        if ($image['name']) {
            // Nama gambar hanya angka random
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
                header('Location: produk.php');
                exit();
            }
        } else {
            $sql = "UPDATE products SET product_name=?, barcode=?, fid_kategori=?, harga_awal=?, harga_jual=?, margin=?, stok=?, expired_at=?, description=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssidddissi",
                $product_name,
                $barcode,
                $fid_kategori,
                $harga_awal,
                $harga_jual,
                $margin,
                $stok_total,
                $expired_at,
                $description,
                $id
            );
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Produk berhasil diperbarui!';
            header('Location: produk.php');
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
    <title>Data Produk</title>
    <link href="../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
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
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Healthy Mart</h2>
        <a href="../../super_dashboard.php"><i class="fas fa-home"></i> Beranda</a>
        <a href="../admin/admin.php"><i class="fas fa-user"></i> Data Kasir</a>
        <a href="../admin/member.php"><i class="fas fa-users"></i> Data Member</a>
        <a href="../admin/kategori.php"><i class="fas fa-list"></i> Data Kategori</a>
        <a class="active" href="../admin/produk.php"><i class="fas fa-box"></i> Data Produk</a>
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
                <h2>Data Produk</h2>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#productModal">
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
                                <td><?= !empty($row["expired_at"]) ? htmlspecialchars($row["expired_at"]) : '-'; ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm editProduct"
                                        data-id="<?= $row['id']; ?>"
                                        data-name="<?= htmlspecialchars($row['product_name']); ?>"
                                        data-barcode="<?= htmlspecialchars($row['barcode']); ?>"
                                        data-kategori="<?= $row['fid_kategori']; ?>"
                                        data-harga_awal="<?= $row['harga_awal']; ?>"
                                        data-harga_jual="<?= $row['harga_jual']; ?>"
                                        data-stok="<?= $row['stok']; ?>"
                                        data-expired="<?= $row['expired_at']; ?>"
                                        data-description="<?= htmlspecialchars($row['description']); ?>"
                                        data-image="<?= $row['image']; ?>"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="produk.php?delete_id=<?= $row['id']; ?>"
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

    <!-- Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form action="produk.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="productId">
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
                                    <input type="text" name="product_name" class="form-control" id="namaProduk">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="barcodeInput" class="form-label">Barcode</label>
                                    <input type="text" name="barcode" id="barcodeInput" class="form-control"
                                        oninput="generateBarcode(); this.value = this.value.replace(/[^0-9]/g, '').slice(0,8);"
                                        maxlength="8" pattern="\d{8}" inputmode="numeric"
                                        placeholder="Kosongkan jika ingin di-generate otomatis">
                                    <div id="barcodePreview" class="mt-2"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="kategori" class="form-label">Kategori</label>
                                    <select name="kategori" class="form-control" id="kategori">
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
                                    <input type="number" name="harga_awal" class="form-control" id="harga_awal" oninput="hitungMargin()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="harga_jual" class="form-label">Harga Jual</label>
                                    <input type="number" name="harga_jual" class="form-control" id="harga_jual" oninput="hitungMargin()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="margin" class="form-label">Margin</label>
                                    <input type="number" name="margin" class="form-control" id="margin" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stok" class="form-label">Tambah Stok</label>
                                    <input type="number" name="stok" class="form-control" id="stok" min="0" value="0">
                                    <div class="form-text" id="stokLamaText"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="expired_at" class="form-label">Expired At</label>
                                    <input type="date" name="expired_at" class="form-control" id="expired_at" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="fotoProduct" class="form-label">Upload Foto</label>
                                    <input type="file" name="image" class="form-control" id="fotoProduct">
                                    <div class="form-text" id="currentImageText"></div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" name="description" class="form-control" id="description" required>
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
                harga: "harga",
                stok: "stok",
                expired: "expired_at",
                description: "description"
            };
            const imageText = document.getElementById("currentImageText");
            const modalLabel = document.getElementById("modalLabel");

            document.querySelectorAll(".editProduct").forEach(btn => {
                btn.addEventListener("click", () => {
                    document.getElementById("productId").value = btn.dataset.id || '';
                    document.getElementById("namaProduk").value = btn.dataset.name || '';
                    document.getElementById("barcodeInput").value = btn.dataset.barcode || '';
                    document.getElementById("kategori").value = btn.dataset.kategori || '';
                    document.getElementById("harga_awal").value = btn.dataset.harga_awal || '';
                    document.getElementById("harga_jual").value = btn.dataset.harga_jual || '';
                    document.getElementById("margin").value = (btn.dataset.harga_jual - btn.dataset.harga_awal) || '';
                    document.getElementById("stok").value = 0;
                    document.getElementById("expired_at").value = btn.dataset.expired || '';
                    document.getElementById("description").value = btn.dataset.description || '';
                    document.getElementById("stokLamaText").innerHTML = "Stok saat ini: <strong>" + btn.dataset.stok + "</strong>";
                    document.getElementById("currentImageText").innerHTML = `Gambar saat ini: <strong>${btn.dataset.image}</strong>`;
                    document.getElementById("modalLabel").innerHTML = '<i class="fas fa-edit"></i> Edit Produk';
                    generateBarcode();
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