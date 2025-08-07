<?php
session_start();
require '../../service/connection.php';
require '../../service/utility.php';

if (!isset($_SESSION['username'])) {
    header('location: auth/login.php');
    exit();
}

$loggedInAdminId = $_SESSION['id'] ?? null;

$sql = "SELECT * FROM admin WHERE level = 'kasir'";
$result = mysqli_query($conn, $sql);

$admins = []; // simpan data admin + status dan style

while ($row = mysqli_fetch_assoc($result)) {
    if ((int)$row['id'] === (int)$loggedInAdminId) {
        $row['status_display'] = "Active";
        $row['status_style'] = "background-color: #28a745; color: white; padding: 2px 12px; border-radius: 20px; text-align: center; font-sixe: 12px; display: inline-block;";
    } else {
        $row['status_display'] = "Non-Active";
        $row['status_style'] = "background-color: #dc3545; color: white; padding: 2px 12px; border-radius: 20px; text-align: center; font-size: 12px; display: inline-block;";
    }
    $admins[] = $row;
}


$levelOptions = [];
$levelQuery = "SHOW COLUMNS FROM admin LIKE 'level'";
$levelResult = mysqli_query($conn, $levelQuery);
$row = mysqli_fetch_assoc($levelResult);
if ($row) {
    preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
    if (isset($matches[1])) {
        $levelOptions = explode("','", $matches[1]);
    }
}

$username = $_SESSION['username'];
$query = "SELECT * FROM admin WHERE username = '$username' ";
$result = mysqli_query($conn, $query);
$admin = mysqli_fetch_assoc($result);

// Proses edit admin
if (isset($_POST['edit_admin'])) {
    $id = $_POST['id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $level = "Kasir"; // Paksa level selalu Kasir
    $new_password = $_POST['password'];

    // Ambil data lama
    $query = "SELECT password, image FROM admin WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_password, $old_image);
    $stmt->fetch();
    $stmt->close();

    // Password baru atau lama
    $final_password = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : $old_password;

    // Upload gambar jika ada
    $new_image = $old_image;
    if (!empty($_FILES['image']['name'])) {
        $file_name = $_FILES['image']['name'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_extensions)) {
            if ($old_image && file_exists("../../assets/img/admin/" . $old_image)) {
                unlink("../../assets/img/admin/" . $old_image);
            }
            $new_image = uniqid('admin_', true) . "." . $file_ext;
            move_uploaded_file($file_tmp, "../../assets/img/admin/" . $new_image);
        }
    }

    // Update ke database
    $updateQuery = $conn->prepare("UPDATE admin SET username = ?, email = ?, password = ?, image = ?, level = ? WHERE id = ?");
    $updateQuery->bind_param("sssssi", $username, $email, $final_password, $new_image, $level, $id);

    if ($updateQuery->execute()) {
        $_SESSION['success'] = 'Data admin berhasil diperbarui!';
    } else {
        $_SESSION['error'] = "Gagal memperbarui data admin: " . $conn->error;
    }
    $updateQuery->close();
    header("Location: admin.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Admin</title>
    <link href="../../css/output.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
            padding: 20px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 80px;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            gap: 20px;
            /* jarak antar elemen dalam sidebar */
        }

        .sidebar h2 {
            text-align: center;
            color: #4CAF50;
            margin-top: 20px;
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

        .data-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
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
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">HealthyMart</div>

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
                    <a href="../../auth/logout.php">
                        <i class="fa fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambahkan id="sidebar" -->
    <div class="sidebar" id="sidebar">
        <div class="menu-item">
            <a href="../../super_dashboard.php" class="menu-link">
                <i class="fa fa-home"></i> Beranda
            </a>
        </div>
        <div class="menu-item active">
            <a href="admin.php" class="menu-link">
                <i class="fa fa-user"></i> Data Kasir
            </a>
        </div>
        <div class="menu-item">
            <a href="member.php" class="menu-link">
                <i class="fa fa-users"></i> Data Member
            </a>
        </div>
        <div class="menu-item">
            <a href="kategori.php" class="menu-link">
                <i class="fa fa-list"></i> Data Kategori Barang
            </a>
        </div>
        <div class="menu-item">
            <a href="produk.php" class="menu-link">
                <i class="fa fa-box"></i> Data Barang
            </a>
        </div>
        <div class="menu-item">
            <a href="laporan.php" class="menu-link">
                <i class="fa fa-file-alt"></i> Laporan
            </a>
        </div>
    </div>


    <div class="main-content" id="main-content">
        <div class="data-container">
            <div class="data-header">
                <h2>Data Admin</h2>
                <!-- Tombol tambah -->
                <button class="add-button" type="button" data-bs-toggle="modal" data-bs-target="#modalTambahAdmin">Tambah Data</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>id</th>
                        <th>Foto</th>
                        <th>Nama Admin</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Level</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin) : ?>
                        <tr>
                            <td><?= $admin['id']; ?></td>
                            <td style="text-align: center;">
                                <?php
                                $imagePath = "../../assets/img/admin/" . $admin['image'];
                                if (!empty($admin['image']) && file_exists($imagePath)) {
                                    echo '<img src="' . $imagePath . '" alt="Foto Admin" class="admin-image" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; display: block; margin: 0 auto;">';
                                } else {
                                    echo '<span>Foto tidak ditemukan</span>';
                                }
                                ?>
                            </td>
                            <td><?= $admin['username']; ?></td>
                            <td><?= $admin['email']; ?></td>
                            <td style="text-align: center; vertical-align: middle;">
                                <span style="<?= $admin['status_style'] ?>">
                                    <?= $admin['status_display'] ?>
                                </span>
                            </td>
                            <td><?= $admin['level']; ?></td>
                            <td>
                                <a href="javascript:void(0);" class="btn edit"
                                    onclick="showEditModal(
                                        '<?= $admin['id']; ?>',
                                        '<?= htmlspecialchars($admin['username'], ENT_QUOTES); ?>',
                                        '<?= htmlspecialchars($admin['email'], ENT_QUOTES); ?>',
                                        '<?= $admin['level']; ?>'
                                    )">Edit</a>
                                <a href="../../activities/admin/delete_admin.php?id=<?= $admin['id']; ?>" class="btn delete" onclick="return confirm('Yakin ingin menghapus admin ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Admin -->
    <div class="modal fade" id="modalTambahAdmin" tabindex="-1" aria-labelledby="modalTambahAdminLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="../../activities/admin/tambah_admin.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahAdminLabel">Tambah Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto</label>
                            <input type="file" name="image" accept="image/*" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Level</label>
                            <input type="text" name="level" id="edit_level" class="form-control" value="Kasir" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Admin -->
    <div class="modal fade" id="modalEditAdmin" tabindex="-1" aria-labelledby="modalEditAdminLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditAdminLabel">Edit Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password (kosongkan jika tidak diubah)</label>
                            <input type="password" name="password" id="edit_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto</label>
                            <input type="file" name="image" id="edit_image" accept="image/*" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Level</label>
                            <input type="text" name="level" id="edit_level" class="form-control" value="Kasir" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="edit_admin" class="btn btn-success">Update</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var mainContent = document.getElementById('main-content');
            var toggleIcon = document.querySelector('.toggle-btn i'); // Ambil ikon dari tombol toggle

            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                mainContent.classList.remove('full-width');
                toggleIcon.classList.remove('fa-chevron-right'); // Ganti ikon jadi panah kiri
                toggleIcon.classList.add('fa-chevron-left');
            } else {
                sidebar.classList.add('hidden');
                mainContent.classList.add('full-width');
                toggleIcon.classList.remove('fa-chevron-left'); // Ganti ikon jadi panah kanan
                toggleIcon.classList.add('fa-chevron-right');
            }
        }

        function toggleDropdown() {
            document.getElementById("dropdownMenu").classList.toggle("show");
        }

        document.addEventListener("click", function(event) {
            var dropdown = document.getElementById("dropdownMenu");
            var profileIcon = document.querySelector(".profile-icon");

            if (!dropdown.contains(event.target) && !profileIcon.contains(event.target)) {
                dropdown.classList.remove("show");
            }
        });

        // Modal Tambah
        function openModalTambah() {
            document.getElementById('modalTambahAdmin').style.display = 'flex';
        }

        function closeModalTambah() {
            document.getElementById('modalTambahAdmin').style.display = 'none';
        }

        // Modal Edit
        function openModalEdit(id, username, email, level) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_level').value = level;
            document.getElementById('modalEditAdmin').style.display = 'flex';
        }

        function closeModalEdit() {
            document.getElementById('modalEditAdmin').style.display = 'none';
        }

        function showEditModal(id, username, email, level) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_level').value = level;
            var modal = new bootstrap.Modal(document.getElementById('modalEditAdmin'));
            modal.show();
        }
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