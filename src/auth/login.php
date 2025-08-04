<?php

session_start();
include "../service/utility.php";

if (isset($_SESSION['email'])) {
    return redirect("dashboard.php");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login BatokMart </title>
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

            <form action="../service/auth.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-gray-600 mb-1">Email</label>
                    <div class="relative">
                        <input type="email" name="email" placeholder="Enter your Email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" />
                        <i class="fa fa-user absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="Enter your Password"
                            class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" />
                        <i id="togglePassword" class="fa fa-eye-slash cursor-pointer absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <button type="submit" name="type" value="login"
                    class="w-full bg-gray-800 text-white py-2 rounded-md hover:bg-gray-900 transition">Sign In</button>
                <div class="text-right">
                    <a href="./forgot.php" class="text-sm text-green-500 hover:underline">Forgot Password?</a>
                </div>
            </form>
        </div>

        <!-- Right: Image -->
        <div class="w-1/2 relative">
            <img src="../assets/log_img.jpg" alt="Login Image" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-white bg-opacity-20"></div>
        </div>
    </div>

    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>

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