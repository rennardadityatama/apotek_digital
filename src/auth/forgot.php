<?php
session_start();
include "../service/utility.php";

include '../service/connection.php';
require '../../vendor/autoload.php'; // Pastikan Composer dan PHPMailer sudah terinstal

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$show_reset_form = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    // Cek apakah email ada di database
    $sql = "SELECT * FROM admin WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc(); // Ambil data email
        $_SESSION['reset_email'] = $row['email']; // Simpan email ke session

        // Buat token unik untuk reset password
        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token berlaku 1 jam

        // Simpan token ke database
        $sql = "UPDATE admin SET reset_token = ?, reset_expiry = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();

        // Buat link reset password
        $reset_link = "http://localhost/kasir_digital/src/auth/change.php?token=" . $token;

        // Kirim email menggunakan PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Gunakan SMTP Gmail
            $mail->SMTPAuth = true;
            $mail->Username = 'rennardadit@gmail.com'; // Ganti dengan email Anda
            $mail->Password = 'ijcq ogzo fcsr foim'; // Ganti dengan password aplikasi Gmail Anda
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('rennardadit@gmail.com', 'BatokMart');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password';
            $mail->Body    = "Klik link berikut untuk mereset password Anda: <a href='$reset_link'>$reset_link</a>";

            if ($mail->send()) {
                $message = "Link reset password telah dikirim ke email Anda.";
            } else {
                $message = "Gagal mengirim email!";
            }
        } catch (Exception $e) {
            $message = "Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        $message = "Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot BatokMart</title>
    <link href="css/output.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="flex items-center justify-center min-h-screen bg-green-500">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-[32rem] text-center flex flex-col items-center space-y-6">
        <!-- Logo dan Judul -->
        <div class="flex flex-col items-center">
            <div class="flex items-center justify-center mb-3">
                <div class="bg-green-500 text-white text-2xl font-bold w-12 h-12 flex items-center justify-center rounded-lg">B</div>
                <span class="text-green-500 text-2xl font-semibold ml-2">atokMart</span>
            </div>
            <h2 class="text-gray-700 text-lg font-semibold">Selamat Datang di BatokMart</h2>
            <p class="text-gray-500 text-sm">Silahkan masuk terlebih dahulu!</p>
        </div>

        <!-- Form Login -->
        <form action="" method="POST" class="w-full flex flex-col items-center space-y-4">
            <input type="email" name="email" placeholder="Email" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            <button type="submit" name="type" value="login" class="w-full bg-green-500 text-white font-semibold p-3 rounded-md hover:bg-green-600 transition">Forgot</button>
        </form>
    </div>
</body>

</html>