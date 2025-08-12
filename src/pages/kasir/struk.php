<?php
session_start();
require '../../service/connection.php';

if (!isset($_GET['transaction_id']) || !is_numeric($_GET['transaction_id'])) {
    die("Transaksi tidak ditemukan.");
}

$transaction_id = intval($_GET['transaction_id']);

// Ambil data transaksi utama
$stmt = $conn->prepare("SELECT t.id, t.detail, t.total_price, t.payment_method, t.margin_total, 
                               t.amount_paid, t.change_amount,
                               a.username AS admin_name, m.name AS member_name, m.phone AS member_phone
                        FROM transactions t
                        LEFT JOIN admin a ON t.fid_admin = a.id
                        LEFT JOIN member m ON t.fid_member = m.id
                        WHERE t.id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$trans = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trans) {
    die("Transaksi tidak ditemukan.");
}

// Ambil detail produk dari transactions_details beserta nama produk
$stmt = $conn->prepare("SELECT td.quantity, td.price, td.subtotal, p.product_name 
                        FROM transactions_details td
                        JOIN products p ON td.fid_product = p.id
                        WHERE td.fid_transaction = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hitung total asli dari detail produk
$total_asli = 0;
foreach ($details as $item) {
    $total_asli += $item['subtotal'];
}

// Hitung potongan point jika ada
$potongan = 0;
if (isset($trans['total_price']) && $total_asli > $trans['total_price']) {
    $potongan = $total_asli - $trans['total_price'];
}

$role = $_SESSION['level'] ?? 'Kasir'; // default Kasir jika tidak ada
if ($role === 'Admin') {
    $backUrl = "../admin/laporan.php";
} else {
    $backUrl = "transaksi.php";
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Struk Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow mx-auto" style="max-width: 400px;">
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center mb-2" style="width:50px;height:50px;">
                        <i class="fa fa-check text-white fs-3"></i>
                    </div>
                    <div class="fw-semibold text-secondary small">Order_ID #<?= htmlspecialchars($trans['id']) ?></div>
                    <h4 class="fw-bold mb-0">Pembayaran Berhasil!</h4>
                </div>
                <hr>
                <ul class="list-unstyled mb-2">
                    <li class="d-flex justify-content-between"><span>Admin:</span> <span><?= htmlspecialchars($trans['admin_name']) ?></span></li>
                    <li class="d-flex justify-content-between"><span>Member:</span> <span><?= $trans['member_name'] ? htmlspecialchars($trans['member_name']) : '<span class="text-muted">-</span>' ?></span></li>
                    <li class="d-flex justify-content-between"><span>Metode:</span> <span><?= htmlspecialchars(ucfirst($trans['payment_method'])) ?></span></li>
                </ul>
                <hr>
                <div class="mb-2">
                    <?php foreach ($details as $item): ?>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                            <span><?= htmlspecialchars($item['product_name']) ?> x <?= $item['quantity'] ?></span>
                            <span>Rp<?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr>
                <ul class="list-unstyled mb-2">
                    <li class="d-flex justify-content-between fw-semibold">
                        <span>Total:</span>
                        <span>Rp<?= number_format(isset($total_asli) ? $total_asli : $trans['total_price'] + ($potongan ?? 0), 0, ',', '.') ?></span>
                    </li>
                    <?php if (isset($trans['member_name']) && $trans['member_name'] && isset($potongan) && $potongan > 0): ?>
                        <li class="d-flex justify-content-between">
                            <span>Potongan Point:</span>
                            <span>-Rp<?= number_format($potongan, 0, ',', '.') ?></span>
                        </li>
                        <li class="d-flex justify-content-between fw-semibold">
                            <span>Total Setelah Potongan:</span>
                            <span>Rp<?= number_format($trans['total_price'], 0, ',', '.') ?></span>
                        </li>
                    <?php endif; ?>
                    <li class="d-flex justify-content-between"><span>Uang Bayar:</span> <span>Rp<?= number_format($trans['amount_paid'], 0, ',', '.') ?></span></li>
                    <li class="d-flex justify-content-between"><span>Kembalian:</span> <span>Rp<?= number_format($trans['change_amount'], 0, ',', '.') ?></span></li>
                </ul>
                <hr>
                <div class="d-flex justify-content-center gap-2">
                    <a href="generate_pdf.php?transaction_id=<?= $trans['id'] ?>" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener noreferrer">
                        <i class="fa fa-file-pdf me-1"></i> PDF
                    </a>
                    <a href="../../service/fonte.php?transaction_id=<?= $trans['id'] ?>" class="btn btn-success btn-sm" target="_blank" rel="noopener noreferrer" title="Kirim ke WhatsApp">
                        <i class="fab fa-whatsapp"></i> Kirim WA
                    </a>
                    <a href="<?= $backUrl ?>" class="btn btn-outline-dark btn-sm" title="Kembali">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>