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

<body class="bg-[#dbeafe] flex items-center justify-center min-h-screen">
    <div class="w-[900px] h-[550px] bg-white shadow-lg rounded-xl overflow-hidden flex">
        <!-- Left: Form -->
        <div class="w-1/2 bg-white flex flex-col justify-center px-10">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Healthy<span class="text-green-400">Mart</span></h1>
            </div>

            <form action="" method="POST" class="space-y-5">
                <div>
                    <label class="block text-gray-600 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="Enter your new Password"
                            class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <i id="togglePassword" class="fa fa-eye-slash cursor-pointer absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new your Password"
                            class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <i id="toggleConfirmPassword" class="fa fa-eye-slash cursor-pointer absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <button type="submit" name="type" value="login"
                    class="w-full bg-gray-800 text-white py-2 rounded-md hover:bg-gray-900 transition">Reset Password</button>
            </form>
        </div>

        <!-- Right: Image -->
        <div class="w-1/2 relative">
            <img src="../assets/log_img.jpg" alt="Login Image" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-white bg-opacity-20"></div>
        </div>
    </div>

    <script>
        // Toggle untuk password utama
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Toggle untuk confirm password
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm_password');

        toggleConfirmPassword.addEventListener('click', function() {
            const isHidden = confirmPasswordInput.type === 'password';
            confirmPasswordInput.type = isHidden ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>