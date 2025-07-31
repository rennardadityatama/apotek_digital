<?php
require 'connection.php';

// Ambil transaction_id dari GET atau POST
$transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;
if (!$transaction_id) {
    die("Transaction ID tidak ditemukan.");
}

// Ambil data transaksi dan member
$stmt = $conn->prepare("SELECT t.id, t.total_price, t.amount_paid, t.change_amount, t.payment_method,
                               a.username AS admin_name, m.name AS member_name, m.phone AS member_phone
                        FROM transactions t
                        LEFT JOIN admin a ON t.fid_admin = a.id
                        LEFT JOIN member m ON t.fid_member = m.id
                        WHERE t.id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$trans = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trans || !$trans['member_phone']) {
    die("Data member atau transaksi tidak ditemukan.");
}

// Ambil detail produk
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
$potongan_point = 0;
if ($total_asli > $trans['total_price']) {
    $potongan_point = $total_asli - $trans['total_price'];
}

// Format pesan WhatsApp
$wa_text = "*Struk BatokMart*\n";
$wa_text .= "Order_ID #{$trans['id']}\n";
$wa_text .= "Admin: {$trans['admin_name']}\n";
$wa_text .= "Member: " . ($trans['member_name'] ? $trans['member_name'] : "Umum") . "\n";
$wa_text .= "----------------------\n";
foreach ($details as $item) {
    $wa_text .= "{$item['product_name']} x{$item['quantity']} = Rp" . number_format($item['subtotal'], 0, ',', '.') . "\n";
}
$wa_text .= "----------------------\n";

// Tampilkan total asli
$wa_text .= "Total: Rp" . number_format($total_asli, 0, ',', '.') . "\n";

// Jika ada potongan point (point digunakan), tampilkan potongan dan total setelah diskon
if ($potongan_point > 0) {
    $wa_text .= "Potongan Point: -Rp" . number_format($potongan_point, 0, ',', '.') . "\n";
    $wa_text .= "Total Setelah Potongan: Rp" . number_format($trans['total_price'], 0, ',', '.') . "\n";
} 

$wa_text .= "Bayar: Rp" . number_format($trans['amount_paid'], 0, ',', '.') . "\n";
$wa_text .= "Kembalian: Rp" . number_format($trans['change_amount'], 0, ',', '.') . "\n";
$wa_text .= "Metode: " . ucfirst($trans['payment_method']) . "\n";
$wa_text .= "Terima kasih!";

// Format nomor WA (hilangkan 0 depan, ganti 62)
$wa_number = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $trans['member_phone']));

// Kirim ke Fonnte
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.fonnte.com/send',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array(
    'target' => $wa_number,
    'message' => $wa_text,
    'countryCode' => '62',
  ),
  CURLOPT_HTTPHEADER => array(
    'Authorization: kQKhsFov7KATCN3VQ9hb' //ganti dengan token Anda
  ),
));

$response = curl_exec($curl);
if (curl_errno($curl)) {
  $error_msg = curl_error($curl);
}
curl_close($curl);

// Tampilkan hasil dengan SweetAlert
?>
<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
<?php if (isset($error_msg)) { ?>
  Swal.fire({
    icon: 'error',
    title: 'Gagal!',
    text: '<?php echo addslashes($error_msg); ?>'
  }).then(() => {
    window.close(); // Atau redirect ke halaman lain jika perlu
  });
<?php } else { 
  $res = json_decode($response, true);
  if (isset($res['status']) && $res['status'] === true) { ?>
    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Pesan WhatsApp berhasil dikirim!'
    }).then(() => {
      window.close(); // Atau redirect ke halaman lain jika perlu
    });
<?php } else { ?>
    Swal.fire({
      icon: 'error',
      title: 'Gagal!',
      text: 'Pesan WhatsApp gagal dikirim!'
    }).then(() => {
      window.close(); // Atau redirect ke halaman lain jika perlu
    });
<?php } } ?>
</script>
</body>
</html>