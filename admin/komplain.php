<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

$admin_id = 1; // ID admin default

// Proses balas pesan (AJAX)
if (isset($_POST['balas_komplain_ajax'])) {
    $transaksi_id = (int)$_POST['transaksi_id'];
    $user_id = (int)$_POST['user_id'];
    $pesan = mysqli_real_escape_string($koneksi, trim($_POST['pesan']));
    
    if (!empty($pesan)) {
        $lampiran = null;
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            $max_size = 2 * 1024 * 1024;
            
            if (in_array($_FILES['lampiran']['type'], $allowed) && $_FILES['lampiran']['size'] <= $max_size) {
                $upload_dir = __DIR__ . '/../uploads/komplain/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
                $lampiran = 'komplain_admin_' . $transaksi_id . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_dir . $lampiran);
            }
        }
        
        mysqli_query($koneksi, "
            INSERT INTO komplain (transaksi_id, user_id, admin_id, pesan, lampiran, pengirim, created_at) 
            VALUES ($transaksi_id, $user_id, $admin_id, '$pesan', '$lampiran', 'admin', NOW())
        ");
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Pesan tidak boleh kosong']);
    }
    exit;
}

// Tandai semua komplain sebagai dibaca untuk transaksi tertentu
if (isset($_POST['mark_read'])) {
    $transaksi_id = (int)$_POST['transaksi_id'];
    mysqli_query($koneksi, "UPDATE komplain SET status = 'dibaca' WHERE transaksi_id = $transaksi_id AND pengirim = 'user'");
    echo json_encode(['success' => true]);
    exit;
}

// Ambil daftar komplain per transaksi
$query = mysqli_query($koneksi, "
    SELECT 
        t.id as transaksi_id,
        t.status as transaksi_status,
        t.total,
        t.created_at as tgl_transaksi,
        u.id as user_id,
        u.nama as user_nama,
        u.email as user_email,
        COUNT(k.id) as jumlah_pesan,
        MAX(k.created_at) as terakhir_pesan,
        (SELECT COUNT(*) FROM komplain WHERE transaksi_id = t.id AND status = 'belum_dibaca' AND pengirim = 'user') as belum_dibaca,
        (SELECT pesan FROM komplain WHERE transaksi_id = t.id ORDER BY created_at DESC LIMIT 1) as pesan_terakhir,
        (SELECT pengirim FROM komplain WHERE transaksi_id = t.id ORDER BY created_at DESC LIMIT 1) as pengirim_terakhir,
        (SELECT lampiran FROM komplain WHERE transaksi_id = t.id ORDER BY created_at DESC LIMIT 1) as lampiran_terakhir
    FROM transaksi t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN komplain k ON t.id = k.transaksi_id
    GROUP BY t.id
    HAVING jumlah_pesan > 0
    ORDER BY terakhir_pesan DESC
");

$detail_id = isset($_GET['transaksi_id']) ? (int)$_GET['transaksi_id'] : 0;

// Ambil detail chat jika ada
$chatMessages = [];
$selectedUser = null;
$selectedTransaksi = null;

if ($detail_id > 0) {
    $chatQuery = mysqli_query($koneksi, "
        SELECT k.*, u.nama as user_nama
        FROM komplain k
        LEFT JOIN users u ON k.user_id = u.id
        WHERE k.transaksi_id = $detail_id
        ORDER BY k.created_at ASC
    ");
    while ($row = mysqli_fetch_assoc($chatQuery)) {
        $chatMessages[] = $row;
    }
    
    // Ambil info user dan transaksi
    $infoQuery = mysqli_query($koneksi, "
        SELECT t.*, u.nama, u.email 
        FROM transaksi t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = $detail_id
    ");
    $selectedTransaksi = mysqli_fetch_assoc($infoQuery);
    $selectedUser = $selectedTransaksi;
    
    // Mark as read
    mysqli_query($koneksi, "UPDATE komplain SET status = 'dibaca' WHERE transaksi_id = $detail_id AND pengirim = 'user'");
}

// Hitung total komplain belum dibaca
$totalBelumDibaca = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(DISTINCT transaksi_id) as total 
    FROM komplain 
    WHERE status = 'belum_dibaca' AND pengirim = 'user'
"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komplain Pelanggan | LiteraBooks Admin</title>
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
            --warning: #f59e0b;
            --info: #3b82f6;
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
        }

        .container {
            max-width: 1400px;
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
            max-width: 1400px;
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

        .nav-link:hover, .nav-link.active {
            color: var(--accent);
        }

        .admin-badge {
            background: var(--accent-gold);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        /* Page Header */
        .page-header {
            margin: 2rem 0 1rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header h1 i {
            color: var(--accent-gold);
        }

        /* Komplain Grid */
        .komplain-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 1.5rem;
            margin: 1.5rem 0;
            min-height: 600px;
        }

        /* List Komplain */
        .komplain-list {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .list-header {
            padding: 1rem 1.2rem;
            background: #f8f9fc;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .list-header span:first-child i {
            margin-right: 0.5rem;
            color: var(--accent-gold);
        }

        .badge-total {
            background: var(--danger);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
        }

        .komplain-items {
            flex: 1;
            overflow-y: auto;
            max-height: 600px;
        }

        .komplain-item {
            padding: 1rem 1.2rem;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .komplain-item:hover {
            background: #fafbfc;
        }

        .komplain-item.active {
            background: #f0f2f6;
            border-left: 3px solid var(--accent);
        }

        .komplain-user {
            font-weight: 600;
            margin-bottom: 0.3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .komplain-user-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .komplain-pesan {
            font-size: 0.8rem;
            color: #5a6474;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.3rem;
        }

        .komplain-time {
            font-size: 0.7rem;
            color: #8e98a8;
            display: flex;
            justify-content: space-between;
        }

        .badge-new {
            background: var(--danger);
            color: white;
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
        }

        .badge-admin-reply {
            background: var(--info);
            color: white;
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
        }

        /* Chat Area */
        .chat-container {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 650px;
        }

        .chat-header {
            padding: 1rem 1.2rem;
            background: #f8f9fc;
            border-bottom: 1px solid var(--border);
        }

        .chat-header h3 {
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-info {
            font-size: 0.75rem;
            color: #5a6474;
            margin-top: 0.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .order-info span {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background: #fafbfc;
        }

        .message {
            display: flex;
            margin-bottom: 1rem;
        }

        .message-user {
            justify-content: flex-start;
        }

        .message-admin {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 75%;
            padding: 0.7rem 1rem;
            border-radius: 18px;
        }

        .message-user .message-bubble {
            background: #f0f2f6;
            color: #1a1f2e;
            border-bottom-left-radius: 5px;
        }

        .message-admin .message-bubble {
            background: var(--accent);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message-header {
            font-size: 0.65rem;
            margin-bottom: 0.3rem;
            opacity: 0.7;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
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
            border: 1px solid rgba(0,0,0,0.1);
        }

        .message-user .message-attachment a {
            color: var(--accent);
        }

        .message-admin .message-attachment a {
            color: rgba(255,255,255,0.8);
        }

        .empty-chat {
            text-align: center;
            padding: 2rem;
            color: #8e98a8;
        }

        /* Chat Input */
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
            padding: 0.7rem;
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
            padding: 0.7rem;
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
            padding: 0.7rem 1.2rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-send:hover {
            background: var(--accent-light);
        }

        .file-name {
            font-size: 0.7rem;
            color: #8e98a8;
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

        .alert-chat {
            padding: 0.5rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Footer */
        .footer {
            background: #0a0e17;
            color: white;
            padding: 2rem 0;
            margin-top: 2rem;
            text-align: center;
        }

        .footer p {
            color: #8e98a8;
            font-size: 0.8rem;
        }

        @media (max-width: 900px) {
            .komplain-grid {
                grid-template-columns: 1fr;
            }
            .komplain-list {
                max-height: 400px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            .navbar .container-nav {
                flex-direction: column;
                gap: 1rem;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container-nav">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-book-open"></i></div>
            <span class="logo-text">litera<span>admin</span></span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="total_buku.php" class="nav-link"><i class="fas fa-book"></i> Buku</a>
            <a href="kelola_kategori.php" class="nav-link"><i class="fas fa-tags"></i> Kategori</a>
            <a href="user.php" class="nav-link"><i class="fas fa-users"></i> User</a>
            <a href="transaksi.php" class="nav-link"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="komplain.php" class="nav-link active"><i class="fas fa-comment-dots"></i> Komplain</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-comment-dots"></i> Komplain Pelanggan</h1>
    </div>

    <div class="komplain-grid">
        <!-- Left: List Komplain -->
        <div class="komplain-list">
            <div class="list-header">
                <span><i class="fas fa-list"></i> Daftar Komplain</span>
                <?php if($totalBelumDibaca > 0): ?>
                    <span class="badge-total"><?= $totalBelumDibaca ?> baru</span>
                <?php endif; ?>
            </div>
            <div class="komplain-items">
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <a href="komplain.php?transaksi_id=<?= $row['transaksi_id'] ?>" class="komplain-item <?= ($detail_id == $row['transaksi_id']) ? 'active' : '' ?>">
                            <div class="komplain-user">
                                <div class="komplain-user-name">
                                    <i class="fas fa-user-circle" style="color: var(--accent-gold);"></i>
                                    <?= htmlspecialchars($row['user_nama']) ?>
                                </div>
                                <?php if($row['belum_dibaca'] > 0): ?>
                                    <span class="badge-new">Baru</span>
                                <?php elseif($row['pengirim_terakhir'] == 'admin'): ?>
                                    <span class="badge-admin-reply"><i class="fas fa-reply"></i> Dibalas</span>
                                <?php endif; ?>
                            </div>
                            <div class="komplain-pesan">
                                <?= strlen($row['pesan_terakhir']) > 50 ? substr($row['pesan_terakhir'], 0, 50) . '...' : $row['pesan_terakhir'] ?>
                            </div>
                            <div class="komplain-time">
                                <span><i class="fas fa-receipt"></i> #<?= $row['transaksi_id'] ?> - Rp <?= number_format($row['total'],0,',','.') ?></span>
                                <span><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($row['terakhir_pesan'])) ?></span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; color: #8e98a8;">
                        <i class="fas fa-inbox" style="font-size: 2rem;"></i>
                        <p style="margin-top: 0.5rem;">Belum ada komplain</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Chat Area -->
        <div class="chat-container">
            <?php if ($detail_id > 0 && $selectedTransaksi): ?>
                <div class="chat-header">
                    <h3>
                        <i class="fas fa-comment"></i> 
                        Komplain dari <?= htmlspecialchars($selectedTransaksi['nama']) ?>
                    </h3>
                    <div class="order-info">
                        <span><i class="fas fa-receipt"></i> Pesanan #<?= $detail_id ?></span>
                        <span><i class="fas fa-money-bill-wave"></i> Total: Rp <?= number_format($selectedTransaksi['total'],0,',','.') ?></span>
                        <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($selectedTransaksi['email']) ?></span>
                    </div>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php if (!empty($chatMessages)): ?>
                        <?php foreach ($chatMessages as $pesan): ?>
                            <div class="message message-<?= $pesan['pengirim'] ?>">
                                <div class="message-bubble">
                                    <div class="message-header">
                                        <span><i class="fas <?= $pesan['pengirim'] == 'user' ? 'fa-user' : 'fa-shield-alt' ?>"></i> <?= $pesan['pengirim'] == 'user' ? htmlspecialchars($pesan['user_nama']) : 'Admin' ?></span>
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
                                                <img src="../uploads/komplain/<?= $pesan['lampiran'] ?>" alt="Lampiran" onclick="window.open(this.src)">
                                                <a href="../uploads/komplain/<?= $pesan['lampiran'] ?>" target="_blank" style="display: inline-block; margin-top: 0.3rem;">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <a href="../uploads/komplain/<?= $pesan['lampiran'] ?>" target="_blank">
                                                    <i class="fas fa-file-pdf"></i> Lihat Lampiran (PDF)
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-chat">
                            <i class="fas fa-comment"></i>
                            <p>Belum ada pesan</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="chat-input-area">
                    <div id="chatAlert"></div>
                    <div class="chat-form">
                        <textarea id="pesanBalasan" class="chat-input" rows="2" placeholder="Tulis balasan untuk pelanggan..."></textarea>
                        <label class="file-label" for="fileInput">
                            <i class="fas fa-paperclip"></i>
                        </label>
                        <input type="file" id="fileInput" accept="image/*,application/pdf">
                        <button class="btn-send" id="btnSend">
                            <i class="fas fa-paper-plane"></i> Kirim
                        </button>
                    </div>
                    <div class="file-name" id="fileName"></div>
                </div>
            <?php else: ?>
                <div class="empty-chat" style="display: flex; flex-direction: column; justify-content: center; height: 100%;">
                    <i class="fas fa-comment-dots" style="font-size: 3rem; color: #8e98a8;"></i>
                    <p style="margin-top: 1rem;">Pilih komplain dari daftar di samping</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> LiteraBooks Admin Panel. All rights reserved.</p>
    </div>
</footer>

<?php if ($detail_id > 0 && $selectedTransaksi): ?>
<script>
    const transaksiId = <?= $detail_id ?>;
    const userId = <?= $selectedTransaksi['user_id'] ?>;
    
    // Auto scroll to bottom
    const messagesContainer = document.getElementById('chatMessages');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // File name display
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || '';
        document.getElementById('fileName').innerHTML = fileName ? '<i class="fas fa-paperclip"></i> ' + fileName : '';
    });
    
    // Mark as read
    async function markAsRead() {
        const formData = new FormData();
        formData.append('mark_read', '1');
        formData.append('transaksi_id', transaksiId);
        
        await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
    }
    markAsRead();
    
    // Send reply
    document.getElementById('btnSend').addEventListener('click', async function() {
        const pesan = document.getElementById('pesanBalasan').value.trim();
        const fileInput = document.getElementById('fileInput');
        const alertDiv = document.getElementById('chatAlert');
        
        if (!pesan) {
            alertDiv.innerHTML = '<div class="alert-chat alert-error"><i class="fas fa-exclamation-circle"></i> Pesan tidak boleh kosong!</div>';
            setTimeout(() => { alertDiv.innerHTML = ''; }, 3000);
            return;
        }
        
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="loading"></span>';
        btn.disabled = true;
        
        const formData = new FormData();
        formData.append('balas_komplain_ajax', '1');
        formData.append('transaksi_id', transaksiId);
        formData.append('user_id', userId);
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
                // Clear form
                document.getElementById('pesanBalasan').value = '';
                fileInput.value = '';
                document.getElementById('fileName').innerHTML = '';
                
                // Reload chat messages
                await loadChatMessages();
                
                alertDiv.innerHTML = '<div class="alert-chat alert-success"><i class="fas fa-check-circle"></i> Pesan berhasil dikirim!</div>';
                setTimeout(() => { alertDiv.innerHTML = ''; }, 2000);
            } else {
                alertDiv.innerHTML = '<div class="alert-chat alert-error"><i class="fas fa-exclamation-circle"></i> ' + (result.error || 'Gagal mengirim pesan') + '</div>';
                setTimeout(() => { alertDiv.innerHTML = ''; }, 3000);
            }
        } catch (error) {
            alertDiv.innerHTML = '<div class="alert-chat alert-error"><i class="fas fa-exclamation-circle"></i> Terjadi kesalahan</div>';
            setTimeout(() => { alertDiv.innerHTML = ''; }, 3000);
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
    
    // Load chat messages
    async function loadChatMessages() {
        try {
            const response = await fetch('get_komplain.php?transaksi_id=<?= $detail_id ?>');
            const html = await response.text();
            document.getElementById('chatMessages').innerHTML = html;
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        } catch (error) {
            console.error('Error loading chat:', error);
        }
    }
    
    // Auto refresh every 5 seconds
    setInterval(() => {
        loadChatMessages();
    }, 5000);
</script>
<?php endif; ?>

</body>
</html>