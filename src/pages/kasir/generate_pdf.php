<?php
require_once '../../service/connection.php';
require_once '../../../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Ambil ID transaksi dari URL
$transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;

if ($transaction_id <= 0) {
    die("Transaksi tidak valid.");
}

// Ambil data transaksi utama
$stmt = $conn->prepare("
    SELECT t.*, 
           m.name AS member_name, 
           m.phone, 
           a.username AS admin_name
    FROM transactions t
    LEFT JOIN member m ON t.fid_member = m.id
    LEFT JOIN admin a ON t.fid_admin = a.id
    WHERE t.id = ?
");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$trans = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trans) {
    die("Transaksi tidak ditemukan.");
}

// Ambil detail produk transaksi (bisa banyak item)
$stmt2 = $conn->prepare("
    SELECT td.quantity, td.price, td.subtotal, p.product_name 
    FROM transactions_details td
    LEFT JOIN products p ON td.fid_product = p.id
    WHERE td.fid_transaction = ?
");
$stmt2->bind_param("i", $transaction_id);
$stmt2->execute();
$details = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Fallback nama admin
$admin = !empty($trans['admin_name']) ? $trans['admin_name'] : 'Admin tidak diketahui';

// Hitung total asli dari detail produk
$total_asli = 0;
foreach ($details as $item) {
    $total_asli += $item['subtotal'];
}

// Hitung potongan point jika ada
$potongan = 0;
if ($total_asli > $trans['total_price']) {
    $potongan = $total_asli - $trans['total_price'];
}

// Buat HTML untuk PDF (mirip tampilan struk yang kamu mau)
$html = '
<div style="
    width: 320px;
    margin: 20px auto;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px #ccc;
    font-family: Arial, sans-serif;
    background-color: #fff;
    color: #333;
    border: 1.5px solid #ccc;
">
    <div style="text-align:center; margin-bottom: 20px;">
        <div style="
            color: #00c851;
            font-weight: bold;
            font-size: 28px;
            font-family: Arial, sans-serif;
            user-select: none;
        ">Batokmart</div>
        <p style="margin: 10px 0 5px; font-weight: 700;">Order_ID #' . htmlspecialchars($trans['id']) . '</p>
        <p style="margin: 0; font-weight: 700; font-size: 16px;">Pembayaran Berhasil !</p>
        <hr style="border: 1px solid #ddd; margin: 15px 0;">
        <div style="margin-bottom:10px; font-size:15px;">
    Member: <span style="font-weight:700;">' . (!empty($trans['member_name']) ? htmlspecialchars($trans['member_name']) : '-') . '</span>
</div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
        <tbody>';
foreach ($details as $item) {
    $html .= '
            <tr>
                <td style="padding: 6px 0;">' . htmlspecialchars($item['product_name']) . ' x ' . $item['quantity'] . '</td>
                <td style="padding: 6px 0; text-align: right;">Rp' . number_format($item['subtotal'], 0, ",", ".") . '</td>
            </tr>';
}
$html .= '
        </tbody>
    </table>
    <hr style="border: 1px solid #ddd; margin: 10px 0;">
    <table style="width: 100%; font-size: 15px;">
        <tr>
            <td style="font-weight:700;">Total:</td>
            <td style="text-align:right; font-weight:700;">Rp' . number_format($total_asli, 0, ",", ".") . '</td>
        </tr>';
if (!empty($trans['member_name']) && $potongan > 0) {
    $html .= '
        <tr>
            <td>Potongan Point:</td>
            <td style="text-align:right;">-Rp' . number_format($potongan, 0, ",", ".") . '</td>
        </tr>
        <tr>
            <td style="font-weight:700;">Total Setelah Potongan:</td>
            <td style="text-align:right; font-weight:700;">Rp' . number_format($trans['total_price'], 0, ",", ".") . '</td>
        </tr>';
}
$html .= '
        <tr>
            <td>Uang Bayar:</td>
            <td style="text-align:right;">Rp' . number_format($trans['amount_paid'], 0, ",", ".") . '</td>
        </tr>
        <tr>
            <td>Kembalian:</td>
            <td style="text-align:right;">Rp' . number_format($trans['change_amount'], 0, ",", ".") . '</td>
        </tr>
        <tr>
            <td>Metode:</td>
            <td style="text-align:right;">' . htmlspecialchars(ucfirst($trans['payment_method'])) . '</td>
        </tr>
    </table>
    <div style="text-align: left; margin-top: 10px; font-size: 16px; font-weight: 700; color: #555;">
        Admin: ' . htmlspecialchars($admin) . '
    </div>
    <div style="text-align: center; margin-top: 15px; font-size: 12px; color: #555;">
        Terima kasih atas pembelian Anda!
    </div>
</div>
';

// Setup Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Set ukuran kertas agak lebih besar supaya muat konten
$dompdf->setPaper([0, 0, 350, 600], 'portrait');

$dompdf->render();
$dompdf->stream("Struk_" . $trans['id'] . ".pdf", ["Attachment" => true]);
exit;
