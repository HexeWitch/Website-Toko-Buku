<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/config/koneksi.php';

/* ======================
   WAJIB LOGIN
====================== */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user']['id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ambil transaksi
$qTransaksi = mysqli_query(
    $koneksi,
    "SELECT * FROM transaksi
     WHERE id = $id AND user_id = $user_id"
);

$transaksi = mysqli_fetch_assoc($qTransaksi);

if (!$transaksi) {
    die("Pesanan tidak ditemukan");
}

// ambil detail transaksi
$qDetail = mysqli_query(
    $koneksi,
    "SELECT td.*, b.judul, b.gambar
     FROM transaksi_detail td
     JOIN buku b ON td.buku_id = b.id
     WHERE td.transaksi_id = $id"
);

/* ======================
   PROSES KIRIM KOMPLAIN (AJAX)
====================== */
if (isset($_POST['kirim_komplain_ajax'])) {
    $pesan = mysqli_real_escape_string($koneksi, trim($_POST['pesan']));
    
    if (!empty($pesan)) {
        $lampiran = null;
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024;
            
            if (in_array($_FILES['lampiran']['type'], $allowed) && $_FILES['lampiran']['size'] <= $max_size) {
                $upload_dir = __DIR__ . '/uploads/komplain/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
                $lampiran = 'komplain_' . $id . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_dir . $lampiran);
            }
        }
        
        mysqli_query($koneksi, "
            INSERT INTO komplain (transaksi_id, user_id, pesan, lampiran, pengirim, created_at) 
            VALUES ($id, $user_id, '$pesan', '$lampiran', 'user', NOW())
        ");
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Pesan tidak boleh kosong']);
    }
    exit;
}

// Ambil komplain yang sudah ada (untuk ditampilkan di popup)
$qKomplain = mysqli_query($koneksi, "
    SELECT k.*, 
           CASE WHEN k.pengirim = 'user' THEN u.nama ELSE 'Admin' END as nama_pengirim
    FROM komplain k
    LEFT JOIN users u ON k.user_id = u.id
    WHERE k.transaksi_id = $id
    ORDER BY k.created_at ASC
");

// Hitung total item di cart untuk badge
$cartTotalItems = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartTotalItems += (int)$qty;
    }
}

/* ======================
   STATUS PROGRESS
====================== */
$status = strtolower($transaksi['status'] ?? 'menunggu konfirmasi');

$step1 = $step2 = $step3 = $step4 = '';
$progressPercent = 0;

if($status == 'menunggu konfirmasi') {
    $step1 = 'active';
    $progressPercent = 25;
} elseif($status == 'dibayar - menunggu proses' || $status == 'diproses') {
    $step1 = 'done'; 
    $step2 = 'active';
    $progressPercent = 50;
} elseif($status == 'dikirim') {
    $step1 = 'done'; 
    $step2 = 'done'; 
    $step3 = 'active';
    $progressPercent = 75;
} elseif($status == 'selesai') {
    $step1 = 'done'; 
    $step2 = 'done'; 
    $step3 = 'done'; 
    $step4 = 'active';
    $progressPercent = 100;
}

// Mapping status ke badge class
$statusClass = '';
$statusIcon = '';
switch($status) {
    case 'menunggu konfirmasi':
        $statusClass = 'status-menunggu';
        $statusIcon = 'fa-clock';
        break;
    case 'dibayar - menunggu proses':
    case 'diproses':
        $statusClass = 'status-diproses';
        $statusIcon = 'fa-spinner';
        break;
    case 'dikirim':
        $statusClass = 'status-dikirim';
        $statusIcon = 'fa-truck';
        break;
    case 'selesai':
        $statusClass = 'status-selesai';
        $statusIcon = 'fa-check-circle';
        break;
    default:
        $statusClass = 'status-menunggu';
        $statusIcon = 'fa-info-circle';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= $transaksi['id']; ?> | LiteraBooks</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8f9fc;
            color: #1a1f2e;
        }

        :root {
            --bg-dark: #0a0e17;
            --bg-card: #ffffff;
            --text-primary: #1a1f2e;
            --text-secondary: #5a6474;
            --text-muted: #8e98a8;
            --accent: #2d3b5e;
            --accent-light: #3a4a6e;
            --accent-gold: #9b8c6c;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #dc2626;
            --info: #3b82f6;
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* NAVBAR */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar .container-nav {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon i {
            color: white;
            font-size: 1rem;
        }

        .logo-text {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .logo-text span {
            font-weight: 400;
            color: var(--text-muted);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .nav-link:hover {
            color: var(--accent);
        }

        .cart-badge {
            background: var(--accent);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            margin-left: 0.3rem;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-left: 1rem;
            margin-left: 0.5rem;
            border-left: 1px solid var(--border);
        }

        .username {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Page Header */
        .page-header {
            padding: 2rem 0 1rem;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .page-header h1 i {
            color: var(--accent-gold);
        }

        .order-info {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-date {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .order-date i {
            margin-right: 0.3rem;
            color: var(--accent-gold);
        }

        .status-badge-large {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-menunggu { background: #fef3c7; color: #92400e; }
        .status-diproses { background: #fed7aa; color: #92400e; }
        .status-dikirim { background: #bfdbfe; color: #1e40af; }
        .status-selesai { background: #d1fae5; color: #065f46; }

        /* Progress Section */
        .progress-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .progress-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 2rem;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
        }

        .step {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
        }

        .step-icon {
            width: 44px;
            height: 44px;
            background: #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            transition: all 0.3s;
            position: relative;
            z-index: 2;
        }

        .step-icon i { font-size: 1.2rem; color: #9ca3af; }
        .step-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 500; }
        .step.done .step-icon { background: var(--success); }
        .step.done .step-icon i { color: white; }
        .step.active .step-icon { background: var(--accent); transform: scale(1.1); box-shadow: 0 0 0 4px rgba(45,59,94,0.2); }
        .step.active .step-icon i { color: white; }
        .step.active .step-label { color: var(--accent); font-weight: 600; }
        .step.done .step-label { color: var(--success); }

        .progress-bar-container { margin-top: 1rem; }
        .progress-bar-bg { background: #e5e7eb; border-radius: 10px; height: 8px; overflow: hidden; }
        .progress-bar-fill { background: var(--accent); width: 0%; height: 100%; border-radius: 10px; transition: width 0.5s ease; }
        .progress-percent { text-align: right; font-size: 0.75rem; color: var(--accent); margin-top: 0.5rem; font-weight: 500; }

        .estimasi-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #166534;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin: 1.5rem 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8f9fc;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .data-table tr:last-child td { border-bottom: none; }

        .book-thumb {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            background: #f0f2f6;
        }

        .cart-summary {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .cart-summary span { font-size: 1.1rem; color: var(--text-secondary); }
        .cart-summary strong { font-size: 1.5rem; font-weight: 700; color: var(--accent); }

        /* Button Komplain */
        .btn-komplain {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent-gold);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-komplain:hover {
            background: #8a7b5e;
            transform: translateY(-2px);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

        /* MODAL POPUP KOMPLAIN */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 550px;
            max-height: 85vh;
            overflow: hidden;
            animation: modalIn 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
        }

        .modal-header h3 {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-header .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background: #fafbfc;
        }

        /* Chat Messages inside Modal */
        .chat-messages {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .message {
            display: flex;
        }

        .message-user {
            justify-content: flex-end;
        }

        .message-admin {
            justify-content: flex-start;
        }

        .message-bubble {
            max-width: 80%;
            padding: 0.7rem 1rem;
            border-radius: 18px;
        }

        .message-user .message-bubble {
            background: var(--accent);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message-admin .message-bubble {
            background: white;
            border: 1px solid var(--border);
            color: #1a1f2e;
            border-bottom-left-radius: 5px;
        }

        .message-header {
            font-size: 0.65rem;
            margin-bottom: 0.3rem;
            opacity: 0.7;
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .message-text {
            font-size: 0.85rem;
            word-wrap: break-word;
        }

        .message-attachment {
            margin-top: 0.5rem;
        }

        .message-attachment a {
            font-size: 0.7rem;
            color: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .message-attachment img {
            max-width: 150px;
            border-radius: 8px;
            margin-top: 0.3rem;
            display: block;
            cursor: pointer;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .message-user .message-attachment a {
            color: rgba(255,255,255,0.8);
        }

        .empty-chat {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .modal-footer {
            padding: 1rem;
            border-top: 1px solid var(--border);
            background: white;
        }

        .komplain-form textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.85rem;
            resize: vertical;
        }

        .komplain-form textarea:focus {
            outline: none;
            border-color: var(--accent);
        }

        .komplain-tools {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .file-label {
            background: #f0f2f6;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .file-label:hover {
            background: var(--border);
        }

        #fileInputModal {
            display: none;
        }

        .btn-send {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.85rem;
            margin-left: auto;
        }

        .btn-send:hover {
            background: var(--accent-light);
        }

        .file-name {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
        }

        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert-modal {
            padding: 0.7rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
        }

        .alert-success-modal {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error-modal {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Rating Box */
        .rating-box {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            border: 1px solid var(--border);
            margin-top: 1rem;
        }

        .rating-box p {
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .btn-rating {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent-gold);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-rating:hover {
            background: #8a7b5e;
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            background: #0a0e17;
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 3rem;
        }

        .footer .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .footer-logo-icon {
            width: 36px;
            height: 36px;
            background: var(--accent-gold);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-logo-text {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .footer-about p {
            color: #8e98a8;
            font-size: 0.85rem;
            line-height: 1.6;
            margin-top: 1rem;
        }

        .footer h4 {
            font-size: 1rem;
            margin-bottom: 1.2rem;
            color: white;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.7rem;
        }

        .footer-links a {
            text-decoration: none;
            color: #8e98a8;
            font-size: 0.85rem;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--accent-gold);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-link {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .social-link:hover {
            background: var(--accent-gold);
            transform: translateY(-2px);
        }

        .social-link i {
            color: white;
            font-size: 0.9rem;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: #5a6474;
        }

        @media (max-width: 768px) {
            .container, .footer .container { padding: 0 1.5rem; }
            .navbar .container-nav { flex-direction: column; gap: 1rem; padding: 1rem; }
            .nav-links { flex-wrap: wrap; justify-content: center; gap: 0.8rem; }
            .progress-steps { flex-direction: column; gap: 1rem; }
            .progress-steps::before { display: none; }
            .step { display: flex; align-items: center; gap: 1rem; }
            .step-icon { margin: 0; }
            .data-table th { display: none; }
            .data-table tr { display: block; margin-bottom: 1rem; border: 1px solid var(--border); border-radius: 12px; background: white; }
            .data-table td { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); }
            .data-table td:last-child { border-bottom: none; }
            .data-table td::before { content: attr(data-label); font-weight: 600; color: var(--text-secondary); font-size: 0.8rem; }
            .cart-summary { flex-direction: column; gap: 0.5rem; text-align: center; }
            .message-bubble { max-width: 90%; }
            .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container-nav">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-book-open"></i></div>
            <span class="logo-text">litera<span>books</span></span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Beranda</a>
            <a href="buku.php" class="nav-link"><i class="fas fa-book"></i> Buku</a>
            <a href="cart.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Keranjang
                <?php if($cartTotalItems > 0): ?>
                    <span class="cart-badge"><?= $cartTotalItems ?></span>
                <?php endif; ?>
            </a>
            <a href="riwayat.php" class="nav-link"><i class="fas fa-history"></i> Riwayat</a>
            <div class="user-badge">
                <i class="fas fa-user-circle" style="color: var(--accent-gold);"></i>
                <span class="username"><?= htmlspecialchars($_SESSION['user']['nama']); ?></span>
            </div>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container">
    <div class="page-header">
        <h1>
            <i class="fas fa-receipt"></i>
            Detail Pesanan #<?= $transaksi['id']; ?>
        </h1>
    </div>

    <!-- Order Info -->
    <div class="order-info">
        <div class="order-date">
            <i class="fas fa-calendar-alt"></i>
            <?= date('d F Y, H:i', strtotime($transaksi['created_at'])); ?>
        </div>
        <div class="status-badge-large <?= $statusClass; ?>">
            <i class="fas <?= $statusIcon; ?>"></i>
            <?= htmlspecialchars($transaksi['status']); ?>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="progress-section">
        <div class="progress-title">
            <i class="fas fa-chart-line" style="color: var(--accent-gold); margin-right: 0.5rem;"></i>
            Status Pesanan
        </div>
        
        <div class="progress-steps">
            <div class="step <?= $step1 ?>">
                <div class="step-icon"><i class="fas fa-clock"></i></div>
                <div class="step-label">Menunggu Konfirmasi</div>
            </div>
            <div class="step <?= $step2 ?>">
                <div class="step-icon"><i class="fas fa-box"></i></div>
                <div class="step-label">Diproses</div>
            </div>
            <div class="step <?= $step3 ?>">
                <div class="step-icon"><i class="fas fa-truck"></i></div>
                <div class="step-label">Dikirim</div>
            </div>
            <div class="step <?= $step4 ?>">
                <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                <div class="step-label">Sampai</div>
            </div>
        </div>

        <div class="progress-bar-container">
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?= $progressPercent ?>%;"></div>
            </div>
            <div class="progress-percent"><?= $progressPercent ?>% selesai</div>
        </div>

        <?php if($status == 'dikirim'): ?>
            <div class="estimasi-box">
                <i class="fas fa-truck-fast"></i>
                Estimasi tiba: <strong>2–4 hari kerja</strong>
            </div>
        <?php endif; ?>
    </div>

    <!-- Detail Produk -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr><th>Buku</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($qDetail)): ?>
                <tr>
                    <td data-label="Buku">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <img src="images/<?= $row['gambar']; ?>" class="book-thumb" alt="<?= htmlspecialchars($row['judul']); ?>" onerror="this.src='https://placehold.co/50x70?text=No+Image'">
                            <strong><?= htmlspecialchars($row['judul']); ?></strong>
                        </div>
                    </td>
                    <td data-label="Harga">Rp <?= number_format($row['harga'],0,',','.'); ?></td>
                    <td data-label="Qty"><?= $row['qty']; ?></td>
                    <td data-label="Subtotal"><strong style="color: var(--accent);">Rp <?= number_format($row['subtotal'],0,',','.'); ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Total -->
    <div class="cart-summary">
        <span>Total Belanja</span>
        <strong>Rp <?= number_format($transaksi['total'],0,',','.'); ?></strong>
    </div>

    <!-- Tombol Komplain -->
    <div style="margin: 1rem 0;">
        <button class="btn-komplain" id="btnKomplain">
            <i class="fas fa-comment-dots"></i> Komplain / Tanya Admin
        </button>
    </div>

    <!-- Rating Box (hanya jika selesai) -->
    <?php if($status == 'selesai'): ?>
    <div class="rating-box">
        <p><i class="fas fa-star" style="color: var(--accent-gold);"></i> Pesanan sudah selesai! Bagikan pengalamanmu</p>
        <a href="rating.php?id=<?= $transaksi['id']; ?>" class="btn-rating"><i class="fas fa-star"></i> Beri Rating & Review</a>
    </div>
    <?php endif; ?>

    <div style="margin-top: 1rem;">
        <a href="riwayat.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Riwayat</a>
    </div>
</div>

<div id="komplainModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-comment-dots" style="color: var(--accent-gold);"></i> Komplain Pesanan #<?= $id ?></h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="chat-messages" id="chatMessagesModal">
                <?php if (mysqli_num_rows($qKomplain) > 0): ?>
                    <?php while ($pesan = mysqli_fetch_assoc($qKomplain)): ?>
                        <div class="message message-<?= $pesan['pengirim'] ?>">
                            <div class="message-bubble">
                                <div class="message-header">
                                    <span><?= htmlspecialchars($pesan['nama_pengirim']) ?></span>
                                    <span><?= date('d/m/Y H:i', strtotime($pesan['created_at'])) ?></span>
                                </div>
                                <div class="message-text"><?= nl2br(htmlspecialchars($pesan['pesan'])) ?></div>
                                <?php if ($pesan['lampiran']): ?>
                                    <div class="message-attachment">
                                        <?php 
                                            $ext = pathinfo($pesan['lampiran'], PATHINFO_EXTENSION);
                                            $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        ?>
                                        <?php if ($isImage): ?>
                                            <img src="uploads/komplain/<?= $pesan['lampiran'] ?>" alt="Lampiran" onclick="window.open(this.src)">
                                            <a href="uploads/komplain/<?= $pesan['lampiran'] ?>" target="_blank" style="display: inline-block; margin-top: 0.3rem;">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <a href="uploads/komplain/<?= $pesan['lampiran'] ?>" target="_blank">
                                                <i class="fas fa-file"></i> Lihat Lampiran
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-chat">
                        <i class="fas fa-comment"></i>
                        <p>Belum ada pesan komplain</p>
                        <p style="font-size: 0.8rem;">Kirim pesan jika ada kendala dengan pesanan Anda</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal-footer">
            <div id="modalAlert"></div>
            <form id="komplainForm" enctype="multipart/form-data">
                <textarea name="pesan" id="pesanKomplain" rows="3" placeholder="Tulis komplain atau pertanyaan Anda..."></textarea>
                <div class="komplain-tools">
                    <label class="file-label" for="fileInputModal">
                        <i class="fas fa-paperclip"></i> Lampirkan Foto
                    </label>
                    <input type="file" name="lampiran" id="fileInputModal" accept="image/*">
                    <button type="button" class="btn-send" id="btnSendKomplain">
                        <i class="fas fa-paper-plane"></i> Kirim
                    </button>
                </div>
                <div class="file-name" id="fileNameModal"></div>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('komplainModal');
    const btnKomplain = document.getElementById('btnKomplain');
    
    btnKomplain.onclick = function() {
        modal.style.display = 'flex';
        const chatContainer = document.getElementById('chatMessagesModal');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }
    
    function closeModal() {
        modal.style.display = 'none';
    }
    
    window.onclick = function(e) {
        if (e.target === modal) {
            closeModal();
        }
    }
    
    document.getElementById('fileInputModal').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || '';
        document.getElementById('fileNameModal').innerHTML = fileName ? '<i class="fas fa-paperclip"></i> ' + fileName : '';
    });
    
    document.getElementById('btnSendKomplain').addEventListener('click', async function() {
        const pesan = document.getElementById('pesanKomplain').value.trim();
        const fileInput = document.getElementById('fileInputModal');
        const alertDiv = document.getElementById('modalAlert');
        
        if (!pesan) {
            alertDiv.innerHTML = '<div class="alert-modal alert-error-modal"><i class="fas fa-exclamation-circle"></i> Pesan tidak boleh kosong!</div>';
            setTimeout(() => { alertDiv.innerHTML = ''; }, 3000);
            return;
        }
        
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="loading"></span> Mengirim...';
        btn.disabled = true;
        
        const formData = new FormData();
        formData.append('kirim_komplain_ajax', '1');
        formData.append('pesan', pesan);
        if (fileInput.files[0]) {
            formData.append('lampiran', fileInput.files[0]);
        }
        
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('pesanKomplain').value = '';
                fileInput.value = '';
                document.getElementById('fileNameModal').innerHTML = '';
                
                alertDiv.innerHTML = '<div class="alert-modal alert-success-modal"><i class="fas fa-check-circle"></i> Pesan berhasil dikirim!</div>';
                
                await loadChatMessages();
                
                setTimeout(() => { alertDiv.innerHTML = ''; }, 2000);
            } else {
                alertDiv.innerHTML = '<div class="alert-modal alert-error-modal"><i class="fas fa-exclamation-circle"></i> ' + (result.error || 'Gagal mengirim pesan') + '</div>';
                setTimeout(() => { alertDiv.innerHTML = ''; }, 3000);
            }
        } catch (error) {
            alertDiv.innerHTML = '<div class="alert-modal alert-error-modal"><i class="fas fa-exclamation-circle"></i> Terjadi kesalahan</div>';
            setTimeout(() => { alertDiv.innerHTML = ''; }, 3000);
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
    
    async function loadChatMessages() {
        try {
            const response = await fetch('get_komplain.php?id=<?= $id ?>');
            const html = await response.text();
            document.getElementById('chatMessagesModal').innerHTML = html;
            const chatContainer = document.getElementById('chatMessagesModal');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        } catch (error) {
            console.error('Error loading chat:', error);
        }
    }
    
    setInterval(() => {
        if (modal.style.display === 'flex') {
            loadChatMessages();
        }
    }, 10000);
</script>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-logo">
                    <div class="footer-logo-icon"><i class="fas fa-book-open"></i></div>
                    <span class="footer-logo-text">litera<span style="font-weight:400;">books</span></span>
                </div>
                <p>Menyediakan koleksi buku berkualitas untuk menemani perjalanan literasimu. Baca, belajar, dan tumbuh bersama kami.</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div>
                <h4>Menu</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="buku.php">Semua Buku</a></li>
                    <li><a href="cart.php">Keranjang</a></li>
                    <li><a href="riwayat.php">Riwayat</a></li>
                </ul>
            </div>
            <div>
                <h4>Bantuan</h4>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Kontak Kami</a></li>
                </ul>
            </div>
            <div>
                <h4>Kontak</h4>
                <ul class="footer-links">
                    <li><i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> hello@literabooks.com</li>
                    <li><i class="fas fa-phone" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> +62 812 3456 7890</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y'); ?> LiteraBooks. All rights reserved. Dibuat dengan <i class="fas fa-heart"></i> untuk para pembaca</p>
        </div>
    </div>
</footer>

</body>
</html>