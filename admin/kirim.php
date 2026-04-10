<?php
session_start();
require_once "../config/koneksi.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

$id = (int) $_GET['id'];

mysqli_query($koneksi,
    "UPDATE transaksi 
     SET status='Dikirim' 
     WHERE id='$id'"
);

header("Location: transaksi.php");
exit;
