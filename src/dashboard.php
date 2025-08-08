<?php
session_start();
require './service/connection.php';

if (!isset($_SESSION['email'])) {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['level'];

if (isset($_SESSION['image'])) {
    $profileImage = "assets/img/admin/" . $_SESSION['image'];
} else {
    $profileImage = "assets/img/admin/default.jpg";
}

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
$query_modal_margin = "SELECT SUM(harga_awal *stok) AS total_modal, SUM(margin * stok) AS total_margin FROM products";
$result_modal_margin = mysqli_query($conn, $query_modal_margin);
$row_modal_margin = mysqli_fetch_assoc($result_modal_margin);
$total_modal = $row_modal_margin['total_modal'] ?? 0;
$total_margin = $row_modal_margin['total_margin'] ?? 0;

// Tambahkan kode untuk menghitung total revenue
$total_revenue = array_sum($data);
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/main.css">

    <style>
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

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Healthy Mart</h2>
        <a class="active" href="./dashboard.php"><i class="fas fa-home"></i> Beranda</a>
        <a href="pages/kasir/member_kasir.php"><i class="fas fa-user"></i> Data Member</a>
        <a href="pages/kasir/laporan.php"><i class="fas fa-clipboard"></i> Laporan</a>
        <a href="pages/kasir/transaksi.php"><i class="fas fa-plus"></i> Transaksi Baru</a>
        <a href="profile/index.php"><i class="fas fa-user-circle"></i> Profil</a>
    </div>

    <div class="header">
        <a href="profile/index.php">
            <div class="profile-box">
                <img src="<?= $profileImage ?>" alt="Profile">
                <div>
                    <?= $username ?><br>
                    <small>Role: <?= $role ?></small>
                </div>
            </div>
        </a>
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
                <form method="get" class="mb-2">
                    <select name="periode" onchange="this.form.submit()" class="form-select"
                        style="width:200px;display:inline-block;">
                        <option value="harian" <?= $periode == 'harian' ? 'selected' : '' ?>>Per Hari</option>
                        <option value="mingguan" <?= $periode == 'mingguan' ? 'selected' : '' ?>>Per Minggu</option>
                        <option value="bulanan" <?= $periode == 'bulanan' ? 'selected' : '' ?>>Per Bulan</option>
                    </select>
                </form>
                <div style="font-size:18px;margin-bottom:10px;">
                    <strong>
                        Total Pendapatan: Rp <?= number_format($total_revenue, 0, ',', '.') ?>
                    </strong>
                    <br>
                    <span style="font-size:14px;color:#888;">
                        (<?= $periode == 'harian' ? 'Per Hari' : ($periode == 'mingguan' ? 'Per Minggu' : 'Per Bulan') ?>)
                    </span>
                </div>
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
                    barThickness: 30,
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
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
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