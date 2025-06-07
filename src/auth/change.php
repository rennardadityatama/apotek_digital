<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include '../service/connection.php';

    if (!isset($_SESSION['reset_email'])) {
        echo "<script>
                Swal.fire('Error!', 'Session tidak valid atau telah kadaluarsa.', 'error').then(() => {
                    window.location.href='../auth/login.php';
                });
              </script>";
        exit;
    }

    $email = $_SESSION['reset_email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Cek apakah password dan konfirmasi cocok
    if ($password !== $confirm_password) {
        echo "<script>
                Swal.fire('Error!', 'Password dan konfirmasi tidak cocok!', 'error');
              </script>";
        exit;
    }

    // Hash password sebelum menyimpan ke database
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Update password di database
    $sql = "UPDATE admin SET password = ?, reset_token = NULL WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $hashed_password, $email);

    if ($stmt->execute()) {
        session_destroy(); // Hapus session setelah reset berhasil
    
        // Redirect ke halaman login
        header("Location: ../auth/login.php");
        exit;
    } else {
        echo "<script>
                Swal.fire('Error!', 'Gagal mengubah password: " . $stmt->error . "', 'error');
              </script>";
    }  

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="../css/output.css" rel="stylesheet">
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
            <h2 class="text-gray-700 text-lg font-semibold">Silahkan Isi Password Baru!!</h2>
        </div>

        <!-- Form Login -->
        <form action="" method="POST" class="w-full flex flex-col items-center space-y-4">
            <input type="text" name="password" placeholder="Password" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            <input type="text" name="confirm_password" placeholder="Confirm Password" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            <button type="submit" name="type" value="login" class="w-full bg-green-500 text-white font-semibold p-3 rounded-md hover:bg-green-600 transition">Reset</button>
        </form>
    </div>
</body>

</html>