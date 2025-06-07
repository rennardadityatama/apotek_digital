<?php
require "../../service/connection.php";
$id = $_GET['id'];

$sql = "DELETE FROM member WHERE id = $id";
if (mysqli_query($conn, $sql)) {
    echo "<script>alert('Data berhasil dihapus'); window.location.href='../../pages/admin/member.php';</script>";
} else {
    echo "Error: " . mysqli_error($conn);
}
