<?php
session_start();
require './service/connection.php';

if (!isset($_SESSION['email'])) {
  header("Location: ../auth/login.php");
  exit();
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
      margin-top: 60px;
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

    .profile-avatar {
      width: 45px;
      height: 45px;
      object-fit: cover;
      border-radius: 50%;
      cursor: pointer;
      border: 2px solid #4CAF50;
      transition: transform 0.2s ease;
    }

    .profile-avatar:hover {
      transform: scale(1.05);
    }

    .profile-header {
      cursor: pointer;
    }

    .modal-content {
      transition: all 0.4s ease-in-out;
    }

    .modal-dialog {
      animation: popIn 0.3s ease;
    }

    @keyframes popIn {
      0% {
        transform: scale(0.95);
        opacity: 0;
      }

      100% {
        transform: scale(1);
        opacity: 1;
      }
    }

    .modal-header h5 {
      font-weight: 600;
      font-size: 18px;
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
    <div class="logo">Healthy Mart</div>
    <!-- Profile Dropdown -->
    <div class="profile-container">
      <div class="d-flex align-items-center gap-2 profile-header">
        <img src="./assets/img/admin/<?= $admin['image']; ?>"
          alt="Profile Picture"
          class="profile-avatar"
          data-bs-toggle="modal"
          data-bs-target="#profileModal">
        <div class="d-none d-md-block">
          <div style="font-size: 13px; font-weight: 600;"><?= $admin['username']; ?></div>
          <div class="badge bg-secondary" style="font-size: 11px;">Role: <?= $admin['role'] ?? 'Admin' ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tambahkan id="sidebar" -->
  <div class="sidebar" id="sidebar">
    <div class="menu-item active">
      <a href="./dashboard.php" class="menu-link">
        <i class="fa fa-home"></i> Beranda
      </a>
    </div>
    <div class="menu-item">
      <a href="pages/kasir/member_kasir.php" class="menu-link">
        <i class="fa fa-user"></i> Data Member
      </a>
    </div>
    <div class="menu-item">
      <a href="pages/kasir/laporan.php" class="menu-link">
        <i class="fa fa-file-alt"></i> Laporan
      </a>
    </div>
    <div class="menu-item ">
      <a href="pages/kasir/transaksi.php" class="menu-link">
        <i class="fa fa-plus"></i> Transaksi Baru
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

  <!-- Profile Modal -->
  <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content shadow-sm border-0 rounded-4">
        <div class="modal-header border-0" style="background: linear-gradient(135deg, #43cea2, #185a9d); color: white; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
          <h5 class="modal-title" id="profileModalLabel">👤 Profil Admin</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-start px-4 pt-4 pb-2">
          <div class="d-flex align-items-center gap-3">
            <img src="./assets/img/admin/<?= $admin['image']; ?>" alt="Foto Profil" class="rounded-circle shadow" width="80" height="80">
            <div>
              <h6 class="mb-0 fw-semibold text-dark"><?= $admin['username']; ?></h6>
              <small class="text-muted"><?= $admin['email']; ?></small><br>
              <span class="badge bg-secondary mt-1">Role: <?= $admin['role'] ?? 'Admin' ?></span>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 justify-content-end px-4 pb-4">
          <a href="./auth/logout.php" class="btn btn-outline-danger btn-sm">
            <i class="fa fa-sign-out-alt me-1"></i> Logout
          </a>
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