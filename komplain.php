<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user']['id'];
$transaksi_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

 $cekTransaksi = mysqli_query($koneksi, "
    SELECT * FROM transaksi 
    WHERE id = $transaksi_id AND user_id = $user_id
");
$transaksi = mysqli_fetch_assoc($cekTransaksi);

if (!$transaksi) {
    header("Location: riwayat.php");
    exit;
}

 if (isset($_POST['kirim_pesan'])) {
    $pesan = mysqli_real_escape_string($koneksi, trim($_POST['pesan']));
    
    // Upload lampiran
    $lampiran = null;
    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $max_size = 2 * 1024 * 1024;
        
        if (in_array($_FILES['lampiran']['type'], $allowed) && $_FILES['lampiran']['size'] <= $max_size) {
            $upload_dir = __DIR__ . '/uploads/komplain/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
            $lampiran = 'komplain_' . $transaksi_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_dir . $lampiran);
        }
    }
    
    $query = mysqli_query($koneksi, "
        INSERT INTO komplain (transaksi_id, user_id, pesan, lampiran, pengirim, created_at) 
        VALUES ($transaksi_id, $user_id, '$pesan', '$lampiran', 'user', NOW())
    ");
    
    header("Location: komplain.php?id=$transaksi_id&success=1");
    exit;
}

 $queryPesan = mysqli_query($koneksi, "
    SELECT k.*, 
           CASE WHEN k.pengirim = 'user' THEN u.nama ELSE 'Admin' END as nama_pengirim
    FROM komplain k
    LEFT JOIN users u ON k.user_id = u.id
    WHERE k.transaksi_id = $transaksi_id
    ORDER BY k.created_at ASC
");

 $cartTotalItems = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartTotalItems += (int)$qty;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komplain Pesanan #<?= $transaksi_id ?> | LiteraBooks</title>
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
            --accent: #2d3b5e;
            --accent-light: #3a4a6e;
            --accent-gold: #9b8c6c;
            --success: #10b981;
            --danger: #dc2626;
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
        }

        .container {
            max-width: 1000px;
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
            color: #1a1f2e;
        }

        .logo-text span {
            font-weight: 400;
            color: #8e98a8;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #5a6474;
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
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-left: 1rem;
            border-left: 1px solid var(--border);
        }

         .chat-header {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin: 2rem 0 1rem;
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .chat-header h1 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-badge {
            background: var(--accent-gold);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .chat-box {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .chat-messages {
            height: 450px;
            overflow-y: auto;
            padding: 1.5rem;
            background: #fafbfc;
        }

        .message {
            display: flex;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-user {
            justify-content: flex-end;
        }

        .message-admin {
            justify-content: flex-start;
        }

        .message-bubble {
            max-width: 70%;
            padding: 0.8rem 1rem;
            border-radius: 20px;
            position: relative;
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
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.3rem;
            font-size: 0.7rem;
        }

        .message-user .message-header {
            color: rgba(255,255,255,0.7);
        }

        .message-admin .message-header {
            color: var(--text-muted);
        }

        .message-name {
            font-weight: 600;
        }

        .message-time {
            font-size: 0.65rem;
        }

        .message-text {
            font-size: 0.85rem;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .message-attachment {
            margin-top: 0.5rem;
        }

        .message-attachment a {
            color: inherit;
            text-decoration: none;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .message-user .message-attachment a {
            color: rgba(255,255,255,0.8);
        }

         .chat-input-area {
            padding: 1rem;
            border-top: 1px solid var(--border);
            background: white;
        }

        .chat-form {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .chat-input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.85rem;
            resize: vertical;
        }

        .chat-input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .file-label {
            background: #f0f2f6;
            padding: 0.8rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-label:hover {
            background: var(--border);
        }

        .file-label i {
            color: var(--accent-gold);
        }

        #fileInput {
            display: none;
        }

        .btn-send {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-send:hover {
            background: var(--accent-light);
        }

        .file-name {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            border: 1px solid var(--border);
            color: #1a1f2e;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .empty-chat {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-chat i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            .message-bubble {
                max-width: 85%;
            }
            .chat-form {
                flex-wrap: wrap;
            }
            .chat-input {
                width: 100%;
            }
        }
    </style>
</head>
<body>

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
                <span><?= $_SESSION['user']['nama']; ?></span>
            </div>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="chat-header">
        <h1>
            <i class="fas fa-comment-dots" style="color: var(--accent-gold);"></i>
            Komplain Pesanan #<?= $transaksi_id ?>
        </h1>
        <span class="order-badge">
            <i class="fas fa-receipt"></i> Status: <?= $transaksi['status'] ?>
        </span>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Pesan berhasil dikirim!
        </div>
    <?php endif; ?>

    <div class="chat-box">
        <div class="chat-messages" id="chatMessages">
            <?php if (mysqli_num_rows($queryPesan) > 0): ?>
                <?php while ($pesan = mysqli_fetch_assoc($queryPesan)): ?>
                    <div class="message message-<?= $pesan['pengirim'] ?>">
                        <div class="message-bubble">
                            <div class="message-header">
                                <span class="message-name">
                                    <i class="fas <?= $pesan['pengirim'] == 'user' ? 'fa-user' : 'fa-shield-alt' ?>"></i>
                                    <?= htmlspecialchars($pesan['nama_pengirim']) ?>
                                </span>
                                <span class="message-time">
                                    <?= date('d/m/Y H:i', strtotime($pesan['created_at'])) ?>
                                </span>
                            </div>
                            <div class="message-text">
                                <?= nl2br(htmlspecialchars($pesan['pesan'])) ?>
                            </div>
                            <?php if ($pesan['lampiran']): ?>
                                <div class="message-attachment">
                                    <a href="uploads/komplain/<?= $pesan['lampiran'] ?>" target="_blank">
                                        <i class="fas fa-paperclip"></i> Lihat Lampiran
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-chat">
                    <i class="fas fa-comment"></i>
                    <p>Belum ada pesan</p>
                    <p style="font-size: 0.8rem;">Kirim pesan komplain Anda di sini</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="chat-input-area">
            <form method="POST" enctype="multipart/form-data" class="chat-form" id="chatForm">
                <textarea name="pesan" class="chat-input" rows="2" placeholder="Tulis pesan komplain Anda..." required></textarea>
                <label class="file-label" for="fileInput">
                    <i class="fas fa-paperclip"></i>
                </label>
                <input type="file" name="lampiran" id="fileInput" accept="image/*,application/pdf">
                <button type="submit" name="kirim_pesan" class="btn-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
            <div class="file-name" id="fileName"></div>
        </div>
    </div>

    <div style="margin-top: 1rem;">
        <a href="riwayat.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
        </a>
    </div>
</div>

<script>
     const messagesContainer = document.getElementById('chatMessages');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

     document.getElementById('fileInput').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || '';
        document.getElementById('fileName').innerHTML = fileName ? '<i class="fas fa-paperclip"></i> ' + fileName : '';
    });
</script>

</body>
</html>