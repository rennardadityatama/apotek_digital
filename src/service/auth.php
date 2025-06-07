<?php
session_start();

include 'utility.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  header('Location: dashboard.php');
}

include 'connection.php';

// now you can access $conn from connection.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $type = $_POST['type'];

  switch ($type) {
    case 'login':
      login();
      $conn->close();
      break;
    case 'logout':
      logout();
      $conn->close();
      break;
    case 'register':
      register();
      $conn->close();
      break;
    case 'find_email':
      find_email();
      $conn->close();
      break;
    case 'edit_password':
      edit_password();
      $conn->close();
      break;
    default:
      header('Location: ../dashboard.php');
      break;
  }
}

function edit_password()
{
  global $conn;

  $reset = htmlspecialchars($_POST['reset']);

  if (!isset($reset)) {
    return redirect("auth/forgot.php", "Something error, please try again from start.", "error");
  }

  $sql = "SELECT * FROM reset_password WHERE reset_token = '$reset'";

  $res = $conn->query($sql)->fetch_array();

  $email = $res['email'];

  if (!isset($res)) {
    return redirect("auth/forgot.php", "Don't do that bro!", "error");
  }

  // $oldPassword = htmlspecialchars($_POST['old_password']);
  $newPassword = htmlspecialchars($_POST['new_password']);
  $confirmNewPassword = htmlspecialchars($_POST['confirm_new_password']);

  if ($newPassword !== $confirmNewPassword) {
    return redirect('auth/forgot.php?email=' . $res['reset_token']);
  }

  //hash new password
  $salt = generateSalt();
  $hashNewPassword = generateHashWithSalt($newPassword, $salt);

  if ($conn->query("UPDATE user SET password = '$salt;$hashNewPassword' WHERE email = '$email'")) {
    $conn->query("DELETE FROM reset_password WHERE reset_token = '" . $res['reset_token'] . "'");
    return redirect("auth/login.php", "Berhasil mengubah password, silahkan login!");
  }else{
    return redirect("auth/forgot.php", "Failed while reset your password.", "error");
  };
}

function find_email()
{
  global $conn;
  $email = htmlspecialchars($_POST['email']);
  $username = htmlspecialchars($_POST['username']);

  $sql = "SELECT * FROM user WHERE email = '$email' AND username = '$username' ";

  $res = $conn->query($sql);

  if ($res->num_rows > 0) {
    // add record reset_password

    $reset = bin2hex(random_bytes(40));
    $email = $res->fetch_array()['email']; // get the email
    $sql = "INSERT INTO reset_password (`reset_token`, `email`) VALUES('$reset', '$email')";

    $conn->query($sql);

    return redirect('auth/change.php?reset=' . $reset);
  } else {
    return redirect("auth/forgot.php", "Username atau password tidak di temukan.", "error");
  }
}

function login()
{
    global $conn;
    
    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        return redirect("auth/login.php", "Harap isi email dan password.", "error");
    }

    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password']; // tidak perlu htmlspecialchars di password, karena akan diverifikasi secara hash

    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $stored_password = $user['password'];

        // Verifikasi password hash
        if (password_verify($password, $stored_password)) {
            // Set session
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_auth'] = true;
            $_SESSION['id'] = $user['id'];
            $_SESSION['level'] = $user['level'];

            // Arahkan berdasarkan level
            if ($user['level'] === 'Admin') {
                return redirect("super_dashboard.php", "Login sebagai Admin berhasil.");
            } elseif ($user['level'] === 'Kasir') {
                return redirect("dashboard.php", "Login sebagai Kasir berhasil.");
            } else {
                return redirect("auth/login.php", "Level pengguna tidak dikenali.", "error");
            }
        } else {
            return redirect("auth/login.php", "Email atau password salah.", "error");
        }
    } else {
        return redirect("auth/login.php", "Email tidak ditemukan.", "error");
    }
}

function logout()
{
    session_start();
    session_destroy();
    return redirect("auth/login.php", "Berhasil logout", "success");
}


function register()
{
    global $conn;

    // Ambil input dari form, lakukan sanitasi
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);
    $c_password = htmlspecialchars($_POST['c_password']);

    // Validasi jika password tidak sama
    if ($password !== $c_password) {
        return redirect("auth/register.php", "Password yang dimasukkan harus sama", "error");
    }

    // Periksa apakah email sudah terdaftar
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        return redirect("auth/register.php", "Email sudah digunakan", "error");
    }

    // Hashing password dengan salt
    $salt = generateSalt();
    $hashPassword = generateHashWithSalt($password, $salt);
    
    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO user (username, email, password) VALUES (?, ?, ?)");
    $password_hash = $salt . ";" . $hashPassword;
    $stmt->bind_param("sss", $username, $email, $password_hash);
    
    if ($stmt->execute()) {
        return redirect("auth/login.php", "Berhasil membuat akun baru, silakan login.");
    } else {
        return redirect("auth/register.php", "Terjadi kesalahan saat mendaftar.", "error");
    }
}


function generateSalt($length = 16)
{
  // Menghasilkan salt acak dengan panjang tertentu
  return bin2hex(random_bytes($length));
}

function generateHashWithSalt($password, $salt)
{
  // Menggabungkan password dengan salt dan menghasilkan hash SHA-256
  return hash('sha256', $salt . $password);
}
