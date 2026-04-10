<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$buku_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($buku_id > 0) {
    $cek = mysqli_query($koneksi, "SELECT id FROM wishlist WHERE user_id = $user_id AND buku_id = $buku_id");
    
    if (mysqli_num_rows($cek) > 0) {
        // Hapus dari wishlist
        mysqli_query($koneksi, "DELETE FROM wishlist WHERE user_id = $user_id AND buku_id = $buku_id");
    } else {
        // Tambah ke wishlist
        mysqli_query($koneksi, "INSERT INTO wishlist (user_id, buku_id, created_at) VALUES ($user_id, $buku_id, NOW())");
    }
}

$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'buku.php';
header("Location: $redirect");
exit;
?>