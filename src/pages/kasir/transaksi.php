<?php
session_start();
require '../../service/connection.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header('location: ../auth/login.php');
    exit();
}

// Ambil info admin
$username = $_SESSION['username'];
$query = "SELECT * FROM admin WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$logged_in_admin_id = $admin['id'] ?? null;

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Tambah produk ke keranjang via GET
if (isset($_GET['add_to_cart']) && is_numeric($_GET['add_to_cart'])) {
    $productId = (int) $_GET['add_to_cart'];

    // Hitung jumlah produk unik di keranjang
    $uniqueProductsCount = count($_SESSION['cart']);

    // Cek apakah produk sudah ada di keranjang
    $alreadyInCart = isset($_SESSION['cart'][$productId]);

    // Batasi maksimal 5 produk berbeda
    if (!$alreadyInCart && $uniqueProductsCount >= 5) {
        $_SESSION['error'] = 'Keranjang hanya boleh berisi maksimal 5 produk berbeda.';
    } else {
        // Cek stok produk saat ini
        $stmt_stok = $conn->prepare("SELECT stok FROM products WHERE id = ?");
        $stmt_stok->bind_param("i", $productId);
        $stmt_stok->execute();
        $result_stok = $stmt_stok->get_result()->fetch_assoc();
        $stmt_stok->close();

        if ($result_stok && $result_stok['stok'] > 0) {
            // Kurangi stok 1
            $stmt_update = $conn->prepare("UPDATE products SET stok = stok - 1 WHERE id = ?");
            $stmt_update->bind_param("i", $productId);
            $stmt_update->execute();
            $stmt_update->close();

            // Tambah ke keranjang
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
            $_SESSION['success'] = 'Produk berhasil ditambahkan ke keranjang.';
        } else {
            $_SESSION['error'] = 'Stok produk habis, tidak bisa ditambahkan.';
        }
    }

    $redirect = 'transaksi.php';
    if (isset($_GET['kategori'])) {
        $redirect .= '?kategori=' . urlencode($_GET['kategori']);
    }
    header("Location: $redirect");
    exit();
}

// Tambah produk ke keranjang via barcode scan
if (isset($_GET['scan_barcode'])) {
    $barcode = $_GET['scan_barcode'];
    $stmt = $conn->prepare("SELECT id FROM products WHERE barcode = ?");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && isset($res['id'])) {
        $productId = (int)$res['id'];
        // Proses sama seperti add_to_cart
        $uniqueProductsCount = count($_SESSION['cart']);
        $alreadyInCart = isset($_SESSION['cart'][$productId]);
        if (!$alreadyInCart && $uniqueProductsCount >= 5) {
            $_SESSION['error'] = 'Keranjang hanya boleh berisi maksimal 5 produk berbeda.';
        } else {
            // Cek stok produk saat ini
            $stmt_stok = $conn->prepare("SELECT stok FROM products WHERE id = ?");
            $stmt_stok->bind_param("i", $productId);
            $stmt_stok->execute();
            $result_stok = $stmt_stok->get_result()->fetch_assoc();
            $stmt_stok->close();

            if ($result_stok && $result_stok['stok'] > 0) {
                // Kurangi stok 1
                $stmt_update = $conn->prepare("UPDATE products SET stok = stok - 1 WHERE id = ?");
                $stmt_update->bind_param("i", $productId);
                $stmt_update->execute();
                $stmt_update->close();

                // Tambah ke keranjang
                $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
                $_SESSION['success'] = 'Produk berhasil ditambahkan ke keranjang.';
            } else {
                $_SESSION['error'] = 'Stok produk habis, tidak bisa ditambahkan.';
            }
        }
    } else {
        $_SESSION['error'] = 'Produk dengan barcode tersebut tidak ditemukan.';
    }
    header("Location: transaksi.php");
    exit();
}

// Hitung total item di keranjang untuk badge (bisa dipakai di UI)
$totalItems = array_sum($_SESSION['cart']);

// Ambil member dari session
$member = $_SESSION['member'] ?? null;

// Proses pencarian member
$memberSearch = '';
if (isset($_POST['search_member'])) {
    $phone_number = $_POST['phone_number'] ?? '';
    $memberSearch = $phone_number;

    $member_query = "SELECT * FROM member WHERE phone = ?";
    $stmt = $conn->prepare($member_query);
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $memberData = $result->fetch_assoc();
        $_SESSION['success'] = "Member ditemukan: " . $memberData['name'];
        $_SESSION['member'] = $memberData;
    } else {
        $_SESSION['error'] = "Member dengan nomor $phone_number tidak ditemukan.";
        unset($_SESSION['member']);
    }
    $stmt->close();

    // Jangan redirect di sini, biarkan halaman reload dengan hasil pencarian
    // header("Location: transaksi.php");
    // exit();
}

// Proses transaksi order
if (isset($_POST['order'])) {
    $cart = $_SESSION['cart'] ?? [];
    $use_point = isset($_POST['use_point']) && $_POST['use_point'] == "1";
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $product_ids = $_POST['product_ids'] ?? [];
    $qty_selected = $_POST['qty_selected'] ?? [];

    $filtered_cart = [];
    foreach ($product_ids as $pid) {
        $pid = (int)$pid;
        if (isset($qty_selected[$pid]) && $qty_selected[$pid] > 0) {
            $filtered_cart[$pid] = (int)$qty_selected[$pid];
        }
    }

    // Ambil data member jika pakai point
    $member_point = 0;
    if ($use_point && $member_id) {
        $stmt = $conn->prepare("SELECT point FROM member WHERE id = ?");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $member_point = $res ? (int)$res['point'] : 0;
    }

    // Hitung total harga
    $total_price = 0;
    $margin_total = 0;
    $detail = '';
    $details_for_insert = [];
    foreach ($filtered_cart as $product_id => $qty) {
        $stmt_p = $conn->prepare("SELECT product_name, harga_jual FROM products WHERE id = ?");
        $stmt_p->bind_param("i", $product_id);
        $stmt_p->execute();
        $res_p = $stmt_p->get_result()->fetch_assoc();
        $stmt_p->close();

        if (!$res_p) continue;

        $harga = $res_p['harga_jual'];
        $nama = $res_p['product_name'];
        $subtotal = $harga * $qty;
        $total_price += $subtotal;
        $detail .= "$nama x $qty, ";

        $details_for_insert[] = [
            'product_id' => $product_id,
            'quantity' => $qty,
            'price' => $harga,
            'subtotal' => $subtotal,
        ];
    }
    $detail = rtrim($detail, ', ');

    // Kurangi total dengan point jika digunakan
    if ($use_point && $member_point >= 500) {
        // Misal: 1 point = Rp1
        $potongan = min($member_point, $total_price);
        $total_price -= $potongan;
    } else {
        $potongan = 0;
    }

    // Ambil uang bayar dari form dan cek cukup atau tidak
    $amount_paid = isset($_POST['amount_paid']) ? (int)$_POST['amount_paid'] : 0;
    $change_amount = $amount_paid - $total_price;
    if ($amount_paid < $total_price) {
        $_SESSION['error'] = "Uang bayar kurang dari total harga. Total harus dibayar Rp" . number_format($total_price, 0, ',', '.');
        header("Location: transaksi.php");
        exit();
    }

    $conn->begin_transaction();

    try {
        $query = "INSERT INTO transactions (fid_admin, fid_member, detail, total_price, margin_total, payment_method, amount_paid, change_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

        $stmt->bind_param("iisdssdd", $logged_in_admin_id, $member_id, $detail, $total_price, $margin_total, $payment_method, $amount_paid, $change_amount);

        if (!$stmt->execute()) throw new Exception("Insert transaction failed: " . $stmt->error);

        $transaction_id = $conn->insert_id;
        $stmt->close();

        $stmt_detail = $conn->prepare("INSERT INTO transactions_details (fid_transaction, fid_product, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt_detail) throw new Exception("Prepare failed: " . $conn->error);

        foreach ($details_for_insert as $item) {
            $stmt_detail->bind_param("iiiid", $transaction_id, $item['product_id'], $item['quantity'], $item['price'], $item['subtotal']);
            if (!$stmt_detail->execute()) throw new Exception("Insert transaction detail failed: " . $stmt_detail->error);
        }
        $stmt_detail->close();

        if ($member_id !== null && $member_id > 0 && $total_price >= 1000) {
            $earned_points = floor($total_price / 1000);
            $stmt_point = $conn->prepare("UPDATE member SET point = point + ? WHERE id = ?");
            if (!$stmt_point) throw new Exception("Prepare failed: " . $conn->error);
            $stmt_point->bind_param("ii", $earned_points, $member_id);
            if (!$stmt_point->execute()) throw new Exception("Update member points failed: " . $stmt_point->error);
            $stmt_point->close();
        }

        $conn->commit();

        // Kurangi qty produk yang dibeli dari keranjang session
        foreach ($filtered_cart as $pid => $qty_beli) {
            if (isset($_SESSION['cart'][$pid])) {
                $_SESSION['cart'][$pid] -= $qty_beli;
                if ($_SESSION['cart'][$pid] <= 0) {
                    unset($_SESSION['cart'][$pid]);
                }
            }
        }
        
        if (empty($_SESSION['cart'])) unset($_SESSION['cart']);
        unset($_SESSION['member']);
        $_SESSION['success'] = "Transaksi berhasil! ID Transaksi: $transaction_id";
        header("Location: struk.php?transaction_id=$transaction_id");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Transaksi gagal: " . $e->getMessage());
    }

    // Setelah transaksi berhasil, jika pakai point, kurangi point member
    if ($use_point && $member_id && $potongan > 0) {
        $stmt = $conn->prepare("UPDATE member SET point = point - ? WHERE id = ?");
        $stmt->bind_param("ii", $potongan, $member_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <link href="../../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
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

        .cart-icon {
            position: relative;
            margin-right: 15px;
        }

        /* Sidebar modal */
        .modal.modal-side .modal-dialog {
            position: fixed;
            top: 0;
            right: 0;
            margin: 0;
            width: 400px;
            height: 100%;
            transform: translateX(100%);
            transition: transform 0.3s ease-out;
            pointer-events: none;
        }

        .modal.modal-side.show .modal-dialog {
            transform: translateX(0);
            pointer-events: auto;
        }

        .modal.modal-side .modal-content {
            height: 100%;
            border-radius: 0;
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
            <button type="button" class="btn btn-link text-dark position-relative" data-bs-toggle="modal" data-bs-target="#cartModal">
                <i class="fa fa-shopping-cart fa-lg"></i>
                <?php if ($totalItems > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $totalItems ?>
                    </span>
                <?php endif; ?>
            </button>
            <button type="button" class="btn btn-link text-dark position-relative" id="btnOpenCamera" title="Scan Barcode/QR">
                <i class="fa fa-camera fa-lg"></i>
            </button>
            <!-- Profil -->
            <i class="fa fa-smile profile-icon" onclick="toggleDropdown()"></i>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-header">
                    <img src="../assets/img/admin/<?= $admin['image']; ?>" alt="User Image">
                    <div>
                        <strong><?= $admin['username']; ?></strong>
                        <p style="font-size: 12px; margin: 0;"><?= $admin['email']; ?></p>
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

    <!-- Modal Keranjang -->
    <div class="modal fade modal-side" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Keranjang Belanja & Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <!-- Form Search Member tetap di luar form transaksi -->
                    <form method="post" action="transaksi.php" class="mt-3">
                        <div class="mb-3">
                            <label for="member_search" class="form-label">Cari Member (Opsional)</label>
                            <div class="input-group">
                                <input type="text" id="member_search" name="phone_number" class="form-control" placeholder="Masukkan nomor telepon member" value="<?= htmlspecialchars($memberSearch ?? '') ?>">
                                <button type="submit" name="search_member" class="btn btn-outline-primary">Cari</button>
                            </div>
                        </div>
                    </form>
                    <form id="transactionForm" method="post">
                        <!-- Member Info -->
                        <div class="mt-3 mb-4">
                            <input type="hidden" name="order" value="1">
                            <?php if (isset($member) && $member): ?>
                                <input type="hidden" name="member_id" value="<?= htmlspecialchars($member['id']) ?>">
                                <p>
                                    <strong>Member:</strong> <?= htmlspecialchars($member['name']) ?><br>
                                    <strong>Point:</strong> <span id="member-point"><?= (int)$member['point'] ?></span>
                                    <?php if ($member['point'] >= 500): ?>
                                        <button type="button" class="btn btn-warning btn-sm ms-2" id="btn-use-point">Gunakan Point</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-sm ms-2" id="btn-use-point" disabled>Tidak Cukup Point</button>
                                    <?php endif; ?>
                                </p>
                                <input type="hidden" name="use_point" id="use_point" value="0">
                            <?php else: ?>
                                <p class="text-muted">Transaksi tanpa member.</p>
                            <?php endif; ?>
                        </div>
                        <!-- Keranjang List -->
                        <?php $total = 0; ?>
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <ul class="list-group mb-3">
                                <?php
                                $total = 0;
                                foreach ($_SESSION['cart'] as $id => $qty):
                                    $stmt = $conn->prepare("SELECT product_name, harga_jual FROM products WHERE id = ?");
                                    $stmt->bind_param("i", $id);
                                    $stmt->execute();
                                    $res = $stmt->get_result()->fetch_assoc();
                                    $nama = htmlspecialchars($res['product_name']);
                                    $harga = $res['harga_jual'];
                                    $subtotal = $harga * $qty;
                                    $total += $subtotal;
                                ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="me-2"><?= $nama ?></span>
                                            <span class="px-2 py-1 rounded bg-secondary text-white small" style="font-weight:500; font-size:0.95em; margin-left:4px;"><?= 'x' . $qty ?></span> <!-- Input hidden agar produk & qty terkirim saat submit -->
                                            <input type="hidden" name="product_ids[]" value="<?= $id ?>">
                                            <input type="hidden" name="qty_selected[<?= $id ?>]" value="<?= $qty ?>">
                                        </div>
                                        <span id="subtotal-<?= $id ?>">Rp<?= number_format($subtotal, 0, ',', '.') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>Keranjang kosong.</p>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Metode Pembayaran</label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                <option value="" disabled <?= empty($_POST['payment_method']) ? 'selected' : '' ?>>Pilih Metode Pembayaran</option>
                                <option value="qris" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'qris') ? 'selected' : '' ?>>QRIS</option>
                                <option value="cash" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                                <option value="transfer" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'transfer') ? 'selected' : '' ?>>Transfer</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="uang_bayar">Uang Bayar (Rp)</label>
                            <input type="number" id="uang_bayar" name="amount_paid" min="0" required />
                        </div>

                        <div class="mb-3">
                            <label for="uang_kembali">Uang Kembalian (Rp)</label>
                            <input type="text" id="uang_kembali" readonly value="Rp0" />
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-bold">Total: <span id="cart-total">Rp<?= number_format($total, 0, ',', '.') ?></span></div>
                            <button type="submit" id="btn-bayar" class="btn btn-success" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>Bayar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Kamera Scan -->
    <div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cameraModalLabel"><i class="fa fa-camera"></i> Scan Barcode/QR</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="reader" style="width:100%;"></div>
                    <div id="scanResult" class="mt-2"></div>
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
        <div class="menu-item ">
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
        <div class="menu-item active">
            <a href="transaksi.php" class="menu-link">
                <i class="fa fa-plus"></i> Transaksi Baru
            </a>
        </div>
    </div>


    <div class="main-content" id="main-content">
        <div class="data-container">
            <div class="data-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2>Transaksi</h2>
                <div class="d-flex align-items-center gap-2">
                    <a href="transaksi.php" class="btn btn-secondary btn-sm">Semua Produk</a>
                    <form method="GET" action="">
                        <select name="kategori" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 180px;">
                            <option value="">-- Pilih Kategori --</option>
                            <?php
                            $kategoriQuery = "SELECT * FROM category";
                            $kategoriResult = mysqli_query($conn, $kategoriQuery);
                            while ($kategori = mysqli_fetch_assoc($kategoriResult)) :
                            ?>
                                <option value="<?= $kategori['id']; ?>" <?= (isset($_GET['kategori']) && $_GET['kategori'] == $kategori['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kategori['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>
            </div>

            <div class="row mt-3 g-3">
                <?php
                $filterKategori = isset($_GET['kategori']) && is_numeric($_GET['kategori']) ? (int) $_GET['kategori'] : null;
                $query = "SELECT * FROM products";
                if ($filterKategori) {
                    $query .= " WHERE fid_kategori = $filterKategori";
                }
                $result = mysqli_query($conn, $query);

                while ($row = mysqli_fetch_assoc($result)) :
                    $link = "transaksi.php?add_to_cart=" . $row['id'];
                    if ($filterKategori) $link .= "&kategori=$filterKategori";
                ?>
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                        <a href="<?= $link ?>" style="text-decoration: none;">
                            <div class="card text-white h-100 shadow-sm" style="border-radius: 12px; background-color: #6c757d;">
                                <img src="../../assets/img/product/<?= htmlspecialchars($row["image"]); ?>" class="card-img-top" alt="<?= htmlspecialchars($row["product_name"]); ?>" style="width: 100%; height: 100px; object-fit: cover; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                                <div class="card-body p-2 text-center text-white d-flex flex-column justify-content-between" style="font-size: 0.8rem;">
                                    <h6 class="mb-1"><?= htmlspecialchars($row["product_name"]); ?></h6>
                                    <p class="mb-1">Harga: Rp<?= number_format($row["harga_jual"], 2, ',', '.'); ?></p>
                                    <p class="mb-1">Stok: <?= htmlspecialchars($row["stok"]); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            // Validasi hanya jika tombol submit "Bayar" yang diklik
            if (e.submitter && e.submitter.id !== 'btn-bayar') return;

            const totalText = document.getElementById('cart-total').textContent || "Rp0";
            const total = parseInt(totalText.replace(/[^0-9]/g, ''), 10) || 0;

            const bayarInput = document.getElementById('uang_bayar');
            const bayar = parseInt(bayarInput.value, 10) || 0;

            if (bayar < total) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: `Uang bayar tidak cukup. Total yang harus dibayar adalah Rp${total.toLocaleString('id-ID')}`,
                    showConfirmButton: true
                });
                bayarInput.focus();
                return;
            }

            // Submit form di frame berikutnya supaya e.preventDefault() tidak menghalangi
            const form = this;
            setTimeout(() => form.submit(), 0);
        });

        document.addEventListener('DOMContentLoaded', () => {
            const totalElem = document.getElementById('cart-total');
            const bayarInput = document.getElementById('uang_bayar');
            const kembaliInput = document.getElementById('uang_kembali');

            function hitungKembalian() {
                let totalText = totalElem.textContent || "Rp0";
                let total = parseInt(totalText.replace(/[^0-9]/g, '')) || 0;
                const bayar = parseInt(bayarInput.value) || 0;
                const kembali = bayar - total;
                kembaliInput.value = kembali >= 0 ?
                    "Rp" + kembali.toLocaleString('id-ID') :
                    "Rp0";
            }

            bayarInput.addEventListener('input', hitungKembalian);

            // Hitung awal saat halaman load
            hitungKembalian();
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const icon = document.querySelector('.toggle-btn i');

            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('full-width');
            icon.classList.toggle('fa-chevron-left');
            icon.classList.toggle('fa-chevron-right');
        }

        function toggleDropdown() {
            const menu = document.getElementById("dropdownMenu");
            menu?.classList.toggle("show");
        }

        function toggleCart() {
            const cartElement = document.getElementById('cartModal');
            if (!cartElement) return;
            const modal = bootstrap.Modal.getOrCreateInstance(cartElement);
            cartElement.classList.contains('show') ? modal.hide() : modal.show();
        }

        // Klik di luar dropdown dan cart = close
        document.addEventListener("click", function(event) {
            const dropdown = document.getElementById("dropdownMenu");
            const profileIcon = document.querySelector(".profile-icon");

            // Tutup dropdown jika klik di luar
            if (dropdown && !dropdown.contains(event.target) && !profileIcon.contains(event.target)) {
                dropdown.classList.remove("show");
            }
        });

        let html5Qr;
        let cameraModal = null;

        document.getElementById('btnOpenCamera').addEventListener('click', function() {
            cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));
            cameraModal.show();

            // Mulai scanner saat modal tampil
            setTimeout(() => {
                if (!html5Qr) {
                    html5Qr = new Html5Qrcode("reader");
                }
                html5Qr.start({
                        facingMode: "environment"
                    }, {
                        fps: 10,
                        qrbox: 250
                    },
                    qrCodeMessage => {
                        document.getElementById('scanResult').innerHTML = `<div class="alert alert-success">Hasil Scan: <strong>${qrCodeMessage}</strong></div>`;
                        // Jika barcode produk, redirect untuk tambah ke keranjang
                        window.location.href = "transaksi.php?scan_barcode=" + encodeURIComponent(qrCodeMessage);
                        // Tutup modal otomatis setelah scan
                        setTimeout(() => {
                            cameraModal.hide();
                        }, 500);
                    },
                    errorMessage => {
                        // Tidak perlu tampilkan error setiap frame
                    }
                );
            }, 400); // Delay agar modal sudah tampil
        });

        // Stop kamera saat modal ditutup
        document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function() {
            if (html5Qr) {
                html5Qr.stop().then(() => {
                    html5Qr.clear();
                });
            }
            document.getElementById('scanResult').innerHTML = '';
        });

        document.addEventListener('DOMContentLoaded', function() {
            const btnUsePoint = document.getElementById('btn-use-point');
            const usePointInput = document.getElementById('use_point');
            const memberPointElem = document.getElementById('member-point');
            const cartTotalElem = document.getElementById('cart-total');
            const bayarInput = document.getElementById('uang_bayar');
            const kembaliInput = document.getElementById('uang_kembali');

            // Simpan total asli (sebelum potongan)
            let totalAsli = parseInt(cartTotalElem.textContent.replace(/[^0-9]/g, ''), 10) || 0;

            function updateTotal() {
                let memberPoint = parseInt(memberPointElem ? memberPointElem.textContent : "0", 10) || 0;
                let usePoint = usePointInput.value === "1";
                let potongan = (usePoint && memberPoint >= 500) ? Math.min(memberPoint, totalAsli) : 0;
                let totalBaru = totalAsli - potongan;

                cartTotalElem.textContent = "Rp" + totalBaru.toLocaleString('id-ID');

                // Reset input bayar dan kembalian
                bayarInput.value = "";
                kembaliInput.value = "Rp0";
            }

            if (btnUsePoint && !btnUsePoint.disabled) {
                btnUsePoint.addEventListener('click', function() {
                    usePointInput.value = usePointInput.value === "1" ? "0" : "1";
                    btnUsePoint.classList.toggle('btn-success');
                    btnUsePoint.classList.toggle('btn-warning');
                    btnUsePoint.textContent = usePointInput.value === "1" ? "Batalkan Gunakan Point" : "Gunakan Point";
                    updateTotal();
                });
            }
        });
    </script>

    <?php if (isset($_POST['search_member'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
                cartModal.show();
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success'] ?>',
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    <?php unset($_SESSION['success']);
    endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= $_SESSION['error'] ?>',
                showConfirmButton: true
            });
        </script>
    <?php unset($_SESSION['error']);
    endif; ?>

</body>

</html>