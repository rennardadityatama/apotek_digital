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

// Hitung total modal dan margin dari seluruh transaksi
$query_modal_margin = "SELECT SUM(harga_awal * stok) AS total_modal, SUM(margin * stok) AS total_margin FROM products";
$result_modal_margin = mysqli_query($conn, $query_modal_margin);
$row_modal_margin = mysqli_fetch_assoc($result_modal_margin);
$total_modal = $row_modal_margin['total_modal'] ?? 0;
$total_margin = $row_modal_margin['total_margin'] ?? 0;

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
    $total_revenue = array_sum($data); // <-- Tambahkan di sini
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
    $total_revenue = array_sum($data); // <-- Tambahkan di sini
} elseif ($periode == 'tahunan') {
    $tahun_filter = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
    // 12 bulan pada tahun yang dipilih
    $query = "SELECT DATE_FORMAT(date, '%Y-%m') as bulan, SUM(total_price) as total 
              FROM transactions 
              WHERE YEAR(date) = $tahun_filter
              GROUP BY bulan
              ORDER BY bulan ASC";
    $result = mysqli_query($conn, $query);
    $labels = [];
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['bulan'];
        $data[] = (int)$row['total'];
    }
    $total_revenue = array_sum($data);
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
    $total_revenue = array_sum($data); // Sudah ada di sini
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
            background: #ffffff;
            padding: 20px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            gap: 20px;
            /* jarak antar elemen dalam sidebar */
        }

        .sidebar h2 {
            text-align: center;
            color: #4CAF50;
            margin-top: 20px;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 10px;
            text-decoration: none;
            color: inherit;
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
            margin-left: 250px;
            padding: 100px 20px 20px;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            box-shadow: none;
            border-radius: 0;
            z-index: 1000;
            border-bottom: 1px solid #ccc;
        }

        .header .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }

        .profile-container {
            position: relative;
            display: inline-block;
        }

        .profile-container:hover .dropdown-menu {
            display: block;
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
            padding: 0;
            /* buang padding di sini */
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 200px;
            z-index: 1000;
        }

        .dropdown-header {
            padding: 10px;
            padding-left: 12px;
            /* lebih kiri */
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
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">HealthyMart</div>

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
            <a href="./super_dashboard.php" class="menu-link">
                <i class="fa fa-home"></i> Beranda
            </a>
        </div>
        <div class="menu-item">
            <a href="./pages/admin/admin.php" class="menu-link">
                <i class="fa fa-user"></i> Data Admin
            </a>
        </div>
        <div class="menu-item">
            <a href="./pages/admin/member.php" class="menu-link">
                <i class="fa fa-users"></i> Data Member
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
            <a href="./pages/admin/laporan.php" class="menu-link">
                <i class="fa fa-file-alt"></i> Laporan
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
        <div class="chart-flex" style="display: flex; gap: 30px; align-items: flex-start;">
            <div class="chart-container" style="flex: 2;">
                <h3>Total Pendapatan Berdasarkan Periode Waktu</h3>
                <div style="font-size:18px;margin-bottom:10px;">
                    <strong>Total: Rp <?= number_format($total_revenue, 0, ',', '.') ?></strong>
                </div>
                <form method="get" class="mb-2">
                    <select name="periode" onchange="this.form.submit()" class="form-select" style="width:200px;display:inline-block;">
                        <option value="harian" <?= $periode == 'harian' ? 'selected' : '' ?>>Per Hari</option>
                        <option value="mingguan" <?= $periode == 'mingguan' ? 'selected' : '' ?>>Per Minggu</option>
                        <option value="bulanan" <?= $periode == 'bulanan' ? 'selected' : '' ?>>Per Bulan</option>
                        <option value="tahunan" <?= $periode == 'tahunan' ? 'selected' : '' ?>>Per Tahun</option>
                    </select>
                    <?php if ($periode == 'tahunan'): ?>
                        <select name="tahun" onchange="this.form.submit()" class="form-select" style="width:120px;display:inline-block;">
                            <?php
                            $tahun_sekarang = date('Y');
                            $tahun_awal = $tahun_sekarang - 5;
                            $tahun_filter = isset($_GET['tahun']) ? intval($_GET['tahun']) : $tahun_sekarang;
                            for ($t = $tahun_sekarang; $t >= $tahun_awal; $t--) {
                                echo '<option value="' . $t . '" ' . ($tahun_filter == $t ? 'selected' : '') . '>' . $t . '</option>';
                            }
                            ?>
                        </select>
                    <?php endif; ?>
                </form>
                <canvas id="revenueChart" style="max-width:400px;max-height:250px;"></canvas>
            </div>
            <div class="chart-container" style="flex: 1; text-align:center;">
                <h4>Perbandingan Modal & Margin</h4>
                <canvas id="pieChart" style="max-width:250px;max-height:250px;margin:auto;"></canvas>
                <div style="margin-top:10px;">
                    <span style="color:#2196f3;">●</span> Modal: <b>Rp <?= number_format($total_modal, 0, ',', '.') ?></b><br>
                    <span style="color:#ff9800;">●</span> Margin: <b>Rp <?= number_format($total_margin, 0, ',', '.') ?></b>
                </div>
            </div>
        </div>
    </div>


    <script>
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

        // Pie/Doughnut Chart untuk Modal & Margin
        var pieCtx = document.getElementById('pieChart').getContext('2d');
        var pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Modal', 'Margin'],
                datasets: [{
                    data: [<?= $total_modal ?>, <?= $total_margin ?>],
                    backgroundColor: ['#2196f3', '#ff9800'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                var label = tooltipItem.label || '';
                                var value = tooltipItem.raw || 0;
                                var formattedValue = 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                return label + ': ' + formattedValue;
                            }
                        }
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