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

// Ambil data dari tabel transactions JOIN dengan produk, member, dan payment method jika perlu
$query = "
    SELECT 
        t.id,
        t.date,
        t.total_price,
        a.username AS admin_name,
        m.name AS member_name,
        p.product_name,
        t.payment_method
    FROM transactions t
    LEFT JOIN admin a ON t.fid_admin = a.id
    LEFT JOIN member m ON t.fid_member = m.id
    LEFT JOIN transactions_details td ON td.fid_transaction = t.id
    LEFT JOIN products p ON td.fid_product = p.id
";
$result = $conn->query($query);

// Siapkan data dan hitung total
$transactions = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $transactions[] = [
        'id' => $row['id'],  // Include the id here
        'invoice' => 'INV' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
        'product' => $row['product_name'] ?? '—',
        'customer' => $row['member_name'] ?? 'Umum',
        'admin' => $row['admin_name'] ?? '—',
        'qty' => 1,
        'total' => $row['total_price'],
        'payment_method' => $row['payment_method'] ?? '—',  // Add payment method
        'receipt_link' => 'struk.php?id=' . $row['id'], // Add link to generate/download receipt
        'date' => $row['date'], // Add the date field
    ];

    // Calculate subtotal
    $total += $row['total_price']; // Add total price for each transaction to the subtotal
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
        <div class="menu-item active">
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
                <h2>Data Transaksi</h2>
                <!-- Kalau mau ada tombol tambah transaksi bisa ditambah disini -->
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Invoice</th>
                            <th>Pembelian</th>
                            <th>Konsumen</th>
                            <th>Admin</th>
                            <th>Jumlah</th>
                            <th>Total</th>
                            <th>Payment Method</th>
                            <th>Tanggal</th>
                            <th>Struk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($transactions as $t): ?> <!-- Ensure you're using $t here -->
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($t['invoice']); ?></td>
                                <td><?= htmlspecialchars($t['product']); ?></td>
                                <td><?= htmlspecialchars($t['customer']); ?></td>
                                <td><?= htmlspecialchars($t['admin']); ?></td>
                                <td><?= $t['qty']; ?></td>
                                <td>Rp. <?= number_format($t['total'], 0, ',', '.'); ?></td>
                                <td><?= htmlspecialchars($t['payment_method']); ?></td>
                                <td><?= date('D, M d, Y', strtotime($t['date'])); ?></td> <!-- Display date in the table -->
                                <td>
                                    <a href="struk.php?transaction_id=<?= $t['id'] ?>" class="btn btn-info" title="Lihat Struk">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="generate_pdf.php?transaction_id=<?= $t['id']; ?>" class="btn btn-success" target="_blank" title="Download Struk">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Display Sub Total -->
                        <tr>
                            <td colspan="7" class="text-end fw-bold">Sub Total</td>
                            <td class="fw-bold">Rp. <?= number_format($total, 0, ',', '.'); ?></td>
                            <td></td> <!-- Empty cell for Struk column -->
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <script>
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

        document.addEventListener("click", function(event) {
            const dropdown = document.getElementById("dropdownMenu");
            const profileIcon = document.querySelector(".profile-icon");
            if (!dropdown.contains(event.target) && !profileIcon.contains(event.target)) {
                dropdown.classList.remove("show");
            }
        });
    </script>

</body>

</html>