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

// Filter and Pagination
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$filter_week = isset($_GET['week']) ? intval($_GET['week']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$filter_day = isset($_GET['day']) ? intval($_GET['day']) : 0;

$where = "YEAR(t.date) = $filter_year";
if ($filter_month) $where .= " AND MONTH(t.date) = $filter_month";
if ($filter_day) $where .= " AND DAY(t.date) = $filter_day";

// Hitung total data
$countQuery = "
    SELECT COUNT(DISTINCT t.id) as total
    FROM transactions t
    WHERE $where
";
$countResult = $conn->query($countQuery);
$totalRows = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRows / $limit);

function getWeeksInMonth($year, $month)
{
    $weeks = [];
    $start = new DateTime("$year-$month-01");
    $end = clone $start;
    $end->modify('last day of this month');
    $week = 1;
    $current = clone $start;

    // Minggu pertama: dari tanggal 1 sampai Minggu pertama
    $firstSunday = clone $current;
    $firstSunday->modify('next sunday');
    if ($firstSunday > $end) $firstSunday = clone $end;
    $weeks[$week] = "Minggu $week";
    $week++;
    $current = $firstSunday;
    $current->modify('+1 day');

    // Minggu berikutnya: Senin-Minggu
    while ($current <= $end) {
        $weeks[$week] = "Minggu $week";
        $current->modify('next sunday');
        if ($current > $end) break;
        $week++;
        $current->modify('+1 day');
    }
    return $weeks;
}

function getWeekRange($year, $month, $week)
{
    $start = new DateTime("$year-$month-01");
    // Minggu ke-1: tanggal 1 sampai Minggu pertama
    if ($week == 1) {
        $weekStart = clone $start;
        $weekEnd = clone $start;
        $weekEnd->modify('next sunday');
        $lastDay = new DateTime("$year-$month-01");
        $lastDay->modify('last day of this month');
        if ($weekEnd > $lastDay) $weekEnd = $lastDay;
    } else {
        // Minggu ke-N: Senin setelah minggu sebelumnya sampai Minggu berikutnya
        $weekStart = clone $start;
        $weekStart->modify('next sunday');
        $weekStart->modify('+' . ($week - 2) . ' week');
        $weekEnd = clone $weekStart;
        $weekEnd->modify('next sunday');
        $lastDay = new DateTime("$year-$month-01");
        $lastDay->modify('last day of this month');
        if ($weekEnd > $lastDay) $weekEnd = $lastDay;
    }
    return [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')];
}

// Query transaksi + join detail
$query = "
    SELECT 
        t.id,
        t.date,
        t.total_price,
        t.payment_method,
        a.username AS admin_name,
        m.name AS member_name,
        td.fid_product,
        td.quantity,
        p.product_name,
        p.harga_awal,
        p.margin
    FROM transactions t
    LEFT JOIN admin a ON t.fid_admin = a.id
    LEFT JOIN member m ON t.fid_member = m.id
    LEFT JOIN transactions_details td ON td.fid_transaction = t.id
    LEFT JOIN products p ON td.fid_product = p.id
    WHERE $where
    ORDER BY t.date DESC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($query);

// Siapkan data dan hitung total
$transactions = [];
$total_penjualan = 0;
$total_modal = 0;
$total_keuntungan = 0;

while ($row = $result->fetch_assoc()) {
    $modal = ($row['harga_awal'] ?? 0) * ($row['quantity'] ?? 1);
    $keuntungan = ($row['margin'] ?? 0) * ($row['quantity'] ?? 1);

    $transactions[] = [
        'id' => $row['id'],
        'invoice' => 'INV' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
        'product' => $row['product_name'] ?? '—',
        'customer' => $row['member_name'] ?? 'Umum',
        'admin' => $row['admin_name'] ?? '—',
        'qty' => $row['quantity'] ?? 1,
        'total' => $row['total_price'],
        'payment_method' => $row['payment_method'] ?? '—',
        'date' => $row['date'],
        'modal' => $modal,
        'keuntungan' => $keuntungan
    ];

    $total_penjualan += $row['total_price'];
    $total_modal += $modal;
    $total_keuntungan += $keuntungan;
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
            background: #ffffff;
            padding: 80px 20px 20px;
            /* Tambahkan jarak atas agar tidak nabrak header */
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar h2 {
            text-align: center;
            color: #4CAF50;
            margin-top: 80px;
            /* sebelumnya 20px */
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

        .dropdown-item a {
            color: inherit;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
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

<body>
    <div class="header">
        <div class="logo">Healthy Mart</div>
        <!-- Profile Dropdown -->
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
                    <a href="./auth/logout.php">
                        <i class="fa fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambahkan id="sidebar" -->
    <div class="sidebar" id="sidebar">
        <div class="menu-item">
            <a href="../../dashboard.php" class="menu-link">
                <i class="fa fa-home"></i> Beranda
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

            <!-- Filter Form -->
            <form method="GET" class="mb-3 d-flex gap-2 align-items-center">
                <select name="year" class="form-select form-select-sm" style="width:100px;" onchange="this.form.submit()">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <select name="month" class="form-select form-select-sm" style="width:100px;" onchange="this.form.submit()">
                    <option value="0">Bulan</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $filter_month == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="day" id="filter-day" class="form-select form-select-sm" style="width:100px;">
                    <option value="0">Hari</option>
                    <?php
                    if ($filter_month) {
                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $filter_month, $filter_year);
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            echo '<option value="' . $d . '" ' . ($filter_day == $d ? 'selected' : '') . '>' . $d . '</option>';
                        }
                    }
                    ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </form>

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
                            <th>Modal</th>
                            <th>Keuntungan</th>
                            <th>Payment Method</th>
                            <th>Tanggal</th>
                            <th>Struk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($t['invoice']); ?></td>
                                <td><?= htmlspecialchars($t['product']); ?></td>
                                <td><?= htmlspecialchars($t['customer']); ?></td>
                                <td><?= htmlspecialchars($t['admin']); ?></td>
                                <td><?= $t['qty']; ?></td>
                                <td>Rp. <?= number_format($t['total'], 0, ',', '.'); ?></td>
                                <td>Rp. <?= number_format($t['modal'], 0, ',', '.'); ?></td>
                                <td>Rp. <?= number_format($t['keuntungan'], 0, ',', '.'); ?></td>
                                <td><?= htmlspecialchars($t['payment_method']); ?></td>
                                <td><?= date('D, M d, Y', strtotime($t['date'])); ?></td>
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
                            <td colspan="6" class="text-end fw-bold">Total</td>
                            <td class="fw-bold">Rp. <?= number_format($total_penjualan, 0, ',', '.'); ?></td>
                            <td class="fw-bold">Rp. <?= number_format($total_modal, 0, ',', '.'); ?></td>
                            <td class="fw-bold">Rp. <?= number_format($total_keuntungan, 0, ',', '.'); ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Rincian Total Penjualan, Modal, Keuntungan -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card text-bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-2">Total Penjualan</h5>
                            <p class="card-text fs-4 fw-bold">Rp. <?= number_format($total_penjualan, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-2">Total Modal</h5>
                            <p class="card-text fs-4 fw-bold">Rp. <?= number_format($total_modal, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-2">Total Keuntungan</h5>
                            <p class="card-text fs-4 fw-bold">Rp. <?= number_format($total_keuntungan, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link"
                                href="?year=<?= $filter_year ?>&month=<?= $filter_month ?>&week=<?= $filter_week ?>&page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
    </div>

    <script>
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