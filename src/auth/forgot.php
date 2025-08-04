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

<body class="bg-[#dbeafe] flex items-center justify-center min-h-screen">
    <div class="w-[900px] h-[550px] bg-white shadow-lg rounded-xl overflow-hidden flex">
        <!-- Left: Form -->
        <div class="w-1/2 bg-white flex flex-col justify-center px-10">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Healthy<span class="text-green-400">Mart</span></h1>
            </div>

            <form action="" method="POST" class="space-y-5">
                <div>
                    <label class="block text-gray-600 mb-1">Email</label>
                    <div class="relative">
                        <input type="email" name="email" placeholder="Enter your Email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <i class="fa fa-user absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <button type="submit" name="type" value="login"
                    class="w-full bg-gray-800 text-white py-2 rounded-md hover:bg-gray-900 transition">Forgot Password</button>
            </form>
        </div>

        <!-- Right: Image -->
        <div class="w-1/2 relative">
            <img src="../assets/log_img.jpg" alt="Login Image" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-white bg-opacity-20"></div>
        </div>
    </div>

    <?php
    if (isset($_SESSION['success'])) {
        if (strlen($_SESSION['success']) > 3) {
            echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '" . $_SESSION['success'] . "',
                showConfirmButton: true
            });
        </script>";
        }
        unset($_SESSION['success']); // Clear the session variable
    }

    if (isset($_SESSION['error'])) {
        if (strlen($_SESSION['error']) > 3) {
            echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '" . $_SESSION['error'] . "',
                showConfirmButton: true
            });
        </script>";
        }
        unset($_SESSION['error']); // Clear the session variable
    }
    ?>
</body>

</html>