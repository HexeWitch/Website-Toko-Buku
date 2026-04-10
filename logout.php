<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}

if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}

header("Location: index.php");
exit;
