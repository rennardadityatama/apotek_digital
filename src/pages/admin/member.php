<?php
session_start();
require '../../service/connection.php';
require '../../service/utility.php';

if (!isset($_SESSION['username'])) {
    header('location: ./auth/login.php');
    exit();
}

$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['level'];

if (isset($_SESSION['image'])) {
    $profileImage = "../../assets/img/admin/" . $_SESSION['image'];
} else {
    $profileImage = "../../assets/img/admin/default.jpg";
}

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

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Cek status member
    $stmt = $conn->prepare("SELECT status FROM member WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        $_SESSION['error'] = 'Member tidak ditemukan';
    } elseif ($res['status'] === 'active') {
        $_SESSION['error'] = 'Member yang aktif tidak bisa dihapus!';
    } else {
        $stmt = $conn->prepare("DELETE FROM member WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Data berhasil dihapus';
        } else {
            $_SESSION['error'] = 'Gagal menghapus member';
        }
        $stmt->close();
    }
    header("Location: member.php");
    exit();
}

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../css/main.css">
    <style>
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
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Healthy Mart</h2>
        <a href="../../super_dashboard.php"><i class="fas fa-home"></i> Beranda</a>
        <a href="../admin/admin.php"><i class="fas fa-user"></i> Data Kasir</a>
        <a class="active" href="../admin/member.php"><i class="fas fa-users"></i> Data Member</a>
        <a href="../admin/kategori.php"><i class="fas fa-list"></i> Data Kategori</a>
        <a href="../admin/produk.php"><i class="fas fa-box"></i> Data Produk</a>
        <a href="../admin/laporan.php"><i class="fas fa-clipboard"></i> Laporan</a>
    </div>

    <!-- Header -->
    <div class="header">
        <a href="../../profile/index.php" style="text-decoration: none; color: inherit;">
            <div class="profile-box">
                <img src="<?= $profileImage ?>" alt="Profile">
                <div>
                    <?= $username ?><br>
                    <small>Role: <?= $role ?></small>
                </div>
            </div>
        </a>
    </div>

    <div class="main-content" id="main-content">
        <div class="data-container">
            <div class="data-header">
                <h2>Data Member</h2>
                <button class="add-button" data-bs-toggle="modal" data-bs-target="#memberModal">
                    Add Member
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
                        <th>Action</th>
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
                            <td>
                                <button type="button" class="btn btn-warning btn-sm edit-member"
                                    data-id="<?= $row['id'] ?>"
                                    data-name="<?= htmlspecialchars($row['name']) ?>"
                                    data-phone="<?= htmlspecialchars($row['phone']) ?>"
                                    data-point="<?= (int)$row['point'] ?>">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-danger btn-sm delete-member"
                                    data-id="<?= $row['id'] ?>"
                                    data-name="<?= htmlspecialchars($row['name']) ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah/Edit Member -->
    <div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="../../activities/admin/add_member.php" id="formMember">
                <div class="modal-header">
                    <h5 class="modal-title" id="memberModalLabel">Tambah Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="member_id">
                    <div class="mb-3">
                        <label for="member_name" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="member_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="member_phone" class="form-label">No. HP</label>
                        <input type="text" class="form-control" id="member_phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <input type="text" class="form-control" value="non-active" readonly>
                        <input type="hidden" name="status" value="non-active">
                    </div>
                    <div class="mb-3">
                        <label for="member_point" class="form-label">Point</label>
                        <input type="number" class="form-control" id="member_point" name="point" value="0" min="0" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ambil role dari PHP
        const userRole = "<?= strtolower($role) ?>";

        // Tombol edit member
        document.querySelectorAll('.edit-member').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('memberModalLabel').textContent = 'Edit Member';
                document.getElementById('member_id').value = this.dataset.id;
                document.getElementById('member_name').value = this.dataset.name;
                document.getElementById('member_phone').value = this.dataset.phone;
                document.getElementById('member_point').value = this.dataset.point;
                document.getElementById('formMember').action =
                    userRole === 'admin' ?
                    '../../activities/admin/edit_member.php' :
                    '../../activities/kasir/edit_member.php';
                document.getElementById('member_point').readOnly = true;
                var modal = new bootstrap.Modal(document.getElementById('memberModal'));
                modal.show();
            });
        });

        document.querySelectorAll('.delete-member').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var memberId = this.dataset.id;
                var memberName = this.dataset.name;
                Swal.fire({
                    title: 'Hapus Member?',
                    text: "Yakin ingin menghapus member '" + memberName + "'?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "member.php?delete_id=" + memberId;
                    }
                });
            });
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