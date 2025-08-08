<?php
session_start();
include '../service/connection.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$currentId = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username']);
    $newEmail = trim($_POST['email']);

    if (empty($newUsername) || empty($newEmail)) {
        header("Location: index.php?empty=1");
        exit();
    }

    // Cek apakah username/email sudah dipakai user lain
    $check = $conn->prepare("SELECT * FROM admin WHERE (username = ? OR email = ?) AND id != ?");
    $check->bind_param("ssi", $newUsername, $newEmail, $currentId);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        header("Location: index.php?used=1");
        exit();
    }

    // Lakukan update
    $stmt = $conn->prepare("UPDATE admin SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $newUsername, $newEmail, $currentId);

    if ($stmt->execute()) {
        $_SESSION['username'] = $newUsername;
        $_SESSION['email'] = $newEmail;
        header("Location: index.php?success=1");
        exit();
    } else {
        header("Location: index.php?error=1");
        exit();
    }
    $stmt->close();
    $check->close();
    $conn->close();
}
