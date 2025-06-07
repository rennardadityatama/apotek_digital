<?php
session_start();

if(!isset($_SESSION['username'])){
    header('location: auth/login.php');
    exit();
}

header('Location: dashboard.php');