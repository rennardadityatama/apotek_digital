<?php
session_start();
require '../../service/connection.php';
require '../../service/utility.php';

if (!isset($_SESSION['username'])) {
    header('location: ./auth/login.php');
    exit();
}

$username = $_SESSION['username'];

// Prepared statement untuk admin
$stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    // user tidak ditemukan, redirect logout
    header('location: ./auth/logout.php');
    exit();
}

$sql_update_status = "
    UPDATE member m
    LEFT JOIN (
        SELECT fid_member, MAX(date) as last_trans FROM transactions GROUP BY fid_member
    ) t ON m.id = t.fid_member
    SET m.status = CASE 
        WHEN t.last_trans IS NULL THEN 'non-active' 
        WHEN TIMESTAMPDIFF(HOUR, t.last_trans, NOW()) >= 1 THEN 'non-active' 
        ELSE 'active' 
    END
";
mysqli_query($conn, $sql_update_status);

// Ambil data member
$sql = "SELECT * FROM member";
$result = mysqli_query($conn, $sql);

$no = 1;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Member</title>
    <link href="../../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background: #f4f6f9;
        }

        .sidebar {
            width: 250px;
            background: #ffffff;
            padding: 80px 20px 20px;
            /* Tambahkan jarak atas agar tidak nabrak header */
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar h2 {
            text-align: center;
            color: #4CAF50;
            margin-top: 80px;
            /* sebelumnya 20px */
        }

        .menu-item a {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 10px;
            text-decoration: none;
            color: inherit;
        }

        .menu-item i {
            margin-right: 10px;
        }

        .menu-item a:hover,
        .menu-item.active a {
            background: #e0f2f1;
        }

        .main-content {
            flex-grow: 1;
            margin-left: 250px;
            padding: 100px 20px 20px;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            box-shadow: none;
            border-radius: 0;
            z-index: 1000;
            border-bottom: 1px solid #ccc;
        }

        .header .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }

        .profile-container {
            position: relative;
            display: inline-block;
        }

        .profile-container:hover .dropdown-menu {
            display: block;
        }

        .profile-icon {
            font-size: 24px;
            cursor: pointer;
            color: #4CAF50;
            background: #e0f2f1;
            border-radius: 50%;
            padding: 10px;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background: white;
            padding: 0;
            /* buang padding di sini */
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 200px;
            z-index: 1000;
        }

        .dropdown-header {
            padding: 10px;
            padding-left: 12px;
            /* lebih kiri */
        }

        .dropdown-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .dropdown-item {
            padding: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border-radius: 5px;
        }

        .dropdown-item:hover {
            background: #e0f2f1;
        }

        .data-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .data-container h2 {
            font-size: 24px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f1f1f1;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        .edit {
            background-color: #ffeb99;
            color: #333;
        }

        .delete {
            background-color: #ff9999;
            color: white;
        }

        .act {
            background-color: green;
            color: white;
        }

        .non-act {
            background-color: red;
            color: white;
        }

        .add-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            float: right;
            margin-bottom: 10px;
        }

        .add-button:hover {
            background-color: #45a049;
        }

        .data-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">Healthy Mart</div>
        <!-- Profile Dropdown -->
        <div class="profile-container">
            <i class="fa fa-smile profile-icon" onclick="toggleDropdown()"></i>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-header">
                    <img src="../../assets/img/admin/<?= $admin['image']; ?>" alt="User Image" id="userImage">
                    <div>
                        <strong id="username"><?= $admin['username']; ?></strong>
                        <p id="email" style="font-size: 12px; margin: 0;"><?= $admin['email']; ?></p>
                    </div>
                </div>
                <div class="dropdown-item logout">
                    <a href="./auth/logout.php">
                        <i class="fa fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambahkan id="sidebar" -->
    <div class="sidebar" id="sidebar">
        <div class="menu-item">
            <a href="../../dashboard.php" class="menu-link">
                <i class="fa fa-home"></i> Beranda
            </a>
        </div>
        <div class="menu-item active">
            <a href="member_kasir.php" class="menu-link">
                <i class="fa fa-user"></i> Data Member
            </a>
        </div>
        <div class="menu-item">
            <a href="laporan.php" class="menu-link">
                <i class="fa fa-file-alt"></i> Laporan
            </a>
        </div>
        <div class="menu-item">
            <a href="transaksi.php" class="menu-link">
                <i class="fa fa-plus"></i> Transaksi Baru
            </a>
        </div>
    </div>


    <div class="main-content" id="main-content">
        <div class="data-container">
            <div class="data-header">
                <h2>Data Member</h2>
                <button class="add-button">
                    <a href="../../activities/admin/add_member.php" style="text-decoration: none; color: white; font-weight: bold;">Tambah Data</a>
                </button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Points</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td><?= htmlspecialchars($row['phone']); ?></td>
                            <td><?= (int)$row['point']; ?></td>
                            <td>
                                <?php if ($row['status'] == 'active') : ?>
                                    <button class="btn act">Active</button>
                                <?php else : ?>
                                    <button class="btn non-act">Non-Active</button>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener("click", function(event) {
            var dropdown = document.getElementById("dropdownMenu");
            var profileIcon = document.querySelector(".profile-icon");

            if (!dropdown.contains(event.target) && !profileIcon.contains(event.target)) {
                dropdown.classList.remove("show");
            }
        });
    </script>

    <?php
    if (isset($_SESSION['success'])) {
        echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: " . json_encode($_SESSION['success']) . ",
            showConfirmButton: true
        });
        </script>";
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error'])) {
        echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: " . json_encode($_SESSION['error']) . ",
            showConfirmButton: true
        });
        </script>";
        unset($_SESSION['error']);
    }
    ?>
</body>

</html>