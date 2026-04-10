<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

// Cek admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Ambil ID
if (!isset($_GET['id'])) {
    header("Location: total_buku.php");
    exit;
}

$id = (int) $_GET['id'];

// Ambil data buku (buat hapus gambar juga)
$q = mysqli_query($koneksi, "SELECT gambar FROM buku WHERE id=$id");
$data = mysqli_fetch_assoc($q);

if ($data) {
    $gambar = $data['gambar'];

    // Hapus file gambar kalau ada
    $path = __DIR__ . '/../images/' . $gambar;
    if ($gambar && file_exists($path)) {
        unlink($path);
    }

    // Hapus dari database
    mysqli_query($koneksi, "DELETE FROM buku WHERE id=$id");
}

// Balik ke halaman buku
header("Location: total_buku.php");
exit;