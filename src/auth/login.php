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

<body class="flex items-center justify-center min-h-screen bg-green-500">
    <div class="bg-white p-10 rounded-2xl shadow-lg w-[35rem] h-[30rem] text-center flex flex-col items-center justify-between">
        <div class="flex flex-col items-center mt-4">
            <div class="flex items-center justify-center mb-4">
                <div class="bg-green-500 text-white text-2xl font-bold w-12 h-12 flex items-center justify-center rounded-lg">B</div>
                <span class="text-green-500 text-2xl font-semibold ml-2">atokMart</span>
            </div>
            <h2 class="text-gray-700 text-lg font-semibold">Selamat Datang Di BatokMart</h2>
            <p class="text-gray-500 text-sm">Silahkan Masuk Terlebih Dahulu!</p>
        </div>

        <form action="../service/auth.php" method="POST" class="w-full flex flex-col items-center mb-4" >
            <div class="mb-4 w-full">
                <input type="email" name="email" placeholder="Masukkan Email Anda" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="mb-4 w-full">
                <input type="password" name="password" placeholder="Masukkan Password Anda" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <button type="submit" name="type" value="login" class="w-full bg-green-500 text-white font-semibold p-3 rounded-md hover:bg-green-600 transition">Masuk</button>
            <div class="w-full text-left mb-6">
                <a href="./forgot.php" class="text-green-500 text-sm hover:underline">Lupa Kata Sandi?</a>
            </div>
        </form>
    </div>

    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>

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
