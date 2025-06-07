<?php
session_start();
require './service/connection.php';

if (!isset($_SESSION['email'])) {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION['username'];
$query = "SELECT * FROM admin WHERE username = '$username' ";
$result = mysqli_query($conn, $query);
$admin = mysqli_fetch_assoc($result);

// Query hitung data
$query_products = "SELECT COUNT(*) as total FROM products";
$query_category = "SELECT COUNT(*) as total FROM category";
$query_transactions = "SELECT COUNT(*) as total FROM transactions";

$result_products = mysqli_query($conn, $query_products);
$result_category = mysqli_query($conn, $query_category);
$result_transactions = mysqli_query($conn, $query_transactions);

$total_products = mysqli_fetch_assoc($result_products)['total'] ?? 0;
$total_category = mysqli_fetch_assoc($result_category)['total'] ?? 0;
$total_transactions = mysqli_fetch_assoc($result_transactions)['total'] ?? 0;

// Default: per hari 7 hari terakhir
$periode = $_GET['periode'] ?? 'harian';
$labels = [];
$data = [];

if ($periode == 'mingguan') {
    // 4 minggu terakhir
    $query = "SELECT YEARWEEK(date, 1) as week, SUM(total_price) as total 
              FROM transactions 
              WHERE date >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
              GROUP BY week
              ORDER BY week DESC
              LIMIT 4";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = 'Minggu ' . substr($row['week'], -2);
        $data[] = (int)$row['total'];
    }
    $labels = array_reverse($labels);
    $data = array_reverse($data);
} elseif ($periode == 'bulanan') {
    // 6 bulan terakhir
    $query = "SELECT DATE_FORMAT(date, '%Y-%m') as bulan, SUM(total_price) as total 
              FROM transactions 
              WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY bulan
              ORDER BY bulan DESC
              LIMIT 6";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['bulan'];
        $data[] = (int)$row['total'];
    }
    $labels = array_reverse($labels);
    $data = array_reverse($data);
} else {
    // Harian 7 hari terakhir
    $query = "SELECT DATE(date) as tanggal, SUM(total_price) as total 
              FROM transactions 
              WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              GROUP BY tanggal
              ORDER BY tanggal DESC
              LIMIT 7";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['tanggal'];
        $data[] = (int)$row['total'];
    }
    $labels = array_reverse($labels);
    $data = array_reverse($data);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="./css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin-top: 120px;
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
            flex-grow: 1;
            margin-left: 290px;
            padding: 100px 20px 20px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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

        .stats-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .stat-box {
            flex: 1;
            background: white;
            padding: 15px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .cart-icon {
            font-size: 24px;
            cursor: pointer;
            color: #4CAF50;
            margin-left: 15px;
        }

        .cart-sidebar {
            position: fixed;
            right: -300px;
            top: 70px;
            /* Menyesuaikan dengan tinggi header */
            width: 300px;
            height: 750px;
            /* Menghindari overlap dengan header */
            background: white;
            box-shadow: -4px 0 6px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease-in-out;
            padding: 16px;
            overflow-y: auto;
            z-index: 1000;
        }

        .cart-sidebar.open {
            right: 0;
        }

        .cart-overlay {
            position: fixed;
            top: 70px;
            /* Sama dengan posisi sidebar */
            left: 0;
            width: 100%;
            height: 750px;
            /* Menyesuaikan agar tidak menutupi header */
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }

        .cart-overlay.show {
            display: block;
        }

        .cart-sidebar h2 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .cart-item img {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            margin-right: 10px;
        }

        .cart-button {
            display: flex;
            align-items: center;
        }

        .cart-button button {
            padding: 4px 8px;
            background: #ddd;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .cart-footer {
            margin-top: 10px;
        }

        .cart-footer button {
            margin-top: 10px;
            width: 100%;
            background: #007bff;
            color: white;
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="toggle-btn" onclick="toggleSidebar()">
            <i class="fa fa-chevron-left"></i>
        </div>
        <div class="logo">BatokMart</div>

        <!-- Profile Dropdown -->
        <div class="profile-container">
            <i class="fa fa-smile profile-icon" onclick="toggleDropdown()"></i>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-header">
                    <img src="./assets/img/admin/<?= $admin['image']; ?>" alt="User Image" id="userImage">
                    <div>
                        <strong id="username"><?= $admin['username']; ?></strong>
                        <p id="email" style="font-size: 12px; margin: 0;"><?= $admin['email']; ?></p>
                    </div>
                </div>
                <div class="dropdown-item logout">
                    <a href="./auth/logout.php">
                        <i class="fa fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambahkan id="sidebar" -->
    <div class="sidebar" id="sidebar">
        <h2>BatokMart</h2>
        <div class="menu-item active">
            <a href="../super_dashboard.php" class="menu-link">
                <i class="fa fa-home"></i> Beranda
            </a>
        </div>
        <div class="menu-item">
            <a href="./pages/admin/kategori.php" class="menu-link">
                <i class="fa fa-list"></i> Data Kategori Barang
            </a>
        </div>
        <div class="menu-item">
            <a href="./pages/admin/produk.php" class="menu-link">
                <i class="fa fa-box"></i> Data Barang
            </a>
        </div>
        <div class="menu-item">
            <a href="./pages/admin/member.php" class="menu-link">
                <i class="fa fa-users"></i> Data Member
            </a>
        </div>
        <div class="menu-item">
            <a href="./pages/admin/admin.php" class="menu-link">
                <i class="fa fa-user"></i> Data Admin
            </a>
        </div>
        <div class="menu-item">
            <a href="./pages/admin/laporan.php" class="menu-link">
                <i class="fa fa-file-alt"></i> Transaksi
            </a>
        </div>
    </div>

    <!-- Tambahkan id="main-content" -->
    <div class="main-content" id="main-content">
        <div class="stats-container">
            <div class="stat-box">Data Barang<br><strong><?= $total_products ?></strong></div>
            <div class="stat-box">Data Kategori<br><strong><?= $total_category ?></strong></div>
            <div class="stat-box">Total Transaksi<br><strong><?= $total_transactions ?></strong></div>
        </div>
        <div class="chart-container">
            <h3>Total Pendapatan Berdasarkan Periode Waktu</h3>
            <form method="get" class="mb-2">
                <select name="periode" onchange="this.form.submit()" class="form-select"
                    style="width:200px;display:inline-block;">
                    <option value="harian" <?= $periode == 'harian' ? 'selected' : '' ?>>Per Hari</option>
                    <option value="mingguan" <?= $periode == 'mingguan' ? 'selected' : '' ?>>Per Minggu</option>
                    <option value="bulanan" <?= $periode == 'bulanan' ? 'selected' : '' ?>>Per Bulan</option>
                </select>
            </form>
            <canvas id="revenueChart"></canvas>
        </div>
    </div>


    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var mainContent = document.getElementById('main-content');
            var toggleIcon = document.querySelector('.toggle-btn i'); // Ambil ikon dari tombol toggle

            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                mainContent.classList.remove('full-width');
                toggleIcon.classList.remove('fa-chevron-right'); // Ganti ikon jadi panah kiri
                toggleIcon.classList.add('fa-chevron-left');
            } else {
                sidebar.classList.add('hidden');
                mainContent.classList.add('full-width');
                toggleIcon.classList.remove('fa-chevron-left'); // Ganti ikon jadi panah kanan
                toggleIcon.classList.add('fa-chevron-right');
            }
        }

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

        function toggleCart() {
            document.querySelector('.cart-sidebar').classList.toggle('open');
            document.querySelector('.cart-overlay').classList.toggle('show');
        }

        var ctx = document.getElementById('revenueChart').getContext('2d');
        var revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Pendapatan',
                    data: <?= json_encode($data) ?>,
                    backgroundColor: '#4CAF50',
                    barThickness: 30, // Lebar bar tetap
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
    <?php
    if (isset($_SESSION['success'])) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '" . $_SESSION['success'] . "',
                showConfirmButton: true
            });
        </script>";
        unset($_SESSION['success']);
    }
    ?>
</body>

</html>