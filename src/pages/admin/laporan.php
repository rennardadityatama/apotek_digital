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
    <title>Data Produk</title>
    <link href="../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Healthy Mart</h2>
        <a href="../../super_dashboard.php"><i class="fas fa-home"></i> Beranda</a>
        <a href="../admin/admin.php"><i class="fas fa-user"></i> Data Kasir</a>
        <a href="../admin/member.php"><i class="fas fa-users"></i> Data Member</a>
        <a href="../admin/kategori.php"><i class="fas fa-list"></i> Data Kategori</a>
        <a href="../admin/produk.php"><i class="fas fa-box"></i> Data Produk</a>
        <a class="active" href="../admin/laporan.php"><i class="fas fa-clipboard"></i> Laporan</a>
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
                                    <a href="../kasir/struk.php?transaction_id=<?= $t['id'] ?>" class="btn btn-info" title="Lihat Struk">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../kasir/generate_pdf.php?transaction_id=<?= $t['id']; ?>" class="btn btn-success" target="_blank" title="Download Struk">
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