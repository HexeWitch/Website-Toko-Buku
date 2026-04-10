<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

// Proses balas pesan (AJAX)
if (isset($_POST['balas_pesan_ajax'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $user_id = (int)$_POST['user_id'];
    $pesan = mysqli_real_escape_string($koneksi, trim($_POST['pesan']));
    
    if (!empty($pesan)) {
        $query = mysqli_query($koneksi, "
            INSERT INTO chat (user_id, nama, pesan, pengirim, created_at) 
            VALUES ($user_id, '$nama', '$pesan', 'admin', NOW())
        ");
        
        if ($query) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => mysqli_error($koneksi)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Pesan tidak boleh kosong']);
    }
    exit;
}

// Ambil semua percakapan berdasarkan nama
$query = mysqli_query($koneksi, "
    SELECT 
        user_id,
        nama,
        pesan as pesan_terakhir,
        created_at as terakhir_pesan,
        COUNT(CASE WHEN pengirim = 'user' AND status = 'belum_dibaca' THEN 1 END) as belum_dibaca
    FROM chat
    GROUP BY nama
    ORDER BY created_at DESC
");

$detail_nama = isset($_GET['nama']) ? mysqli_real_escape_string($koneksi, $_GET['nama']) : '';

$chatMessages = [];
$selectedUser = null;
if ($detail_nama) {
    $chatQuery = mysqli_query($koneksi, "SELECT * FROM chat WHERE nama = '$detail_nama' ORDER BY created_at ASC");
    while ($row = mysqli_fetch_assoc($chatQuery)) {
        $chatMessages[] = $row;
    }
    $userInfo = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT user_id, nama FROM chat WHERE nama = '$detail_nama' LIMIT 1"));
    $selectedUser = $userInfo;
    mysqli_query($koneksi, "UPDATE chat SET status = 'dibaca' WHERE nama = '$detail_nama' AND pengirim = 'user'");
}

$totalBelumDibaca = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM chat WHERE status = 'belum_dibaca' AND pengirim = 'user'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Pelanggan | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8f9fc;
            color: #1a1f2e;
        }
        :root {
            --accent: #2d3b5e;
            --accent-light: #3a4a6e;
            --accent-gold: #9b8c6c;
            --border: #e8ecf2;
        }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 2rem; }
        
        .navbar {
            background: rgba(255,255,255,0.98);
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
        .logo { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .logo-icon { width: 32px; height: 32px; background: var(--accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .logo-icon i { color: white; font-size: 1rem; }
        .logo-text { font-size: 1.3rem; font-weight: 700; color: #1a1f2e; }
        .logo-text span { font-weight: 400; color: #8e98a8; }
        .nav-links { display: flex; gap: 2rem; align-items: center; }
        .nav-link { text-decoration: none; color: #5a6474; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 0.3rem; }
        .nav-link:hover, .nav-link.active { color: var(--accent); }
        .admin-badge { background: var(--accent-gold); color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.75rem; }
        
        .page-header { margin: 2rem 0 1rem; }
        .page-header h1 { font-size: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .chat-layout {
            display: flex;
            gap: 1.5rem;
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin: 1rem 0;
            min-height: 550px;
        }
        
        .chat-sidebar {
            width: 300px;
            background: white;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 1rem;
            background: #f8f9fc;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
        }
        .chat-list { flex: 1; overflow-y: auto; }
        .chat-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            text-decoration: none;
            display: block;
            color: inherit;
            transition: background 0.2s;
        }
        .chat-item:hover { background: #fafbfc; }
        .chat-item.active { background: #f0f2f6; border-left: 3px solid var(--accent); }
        .chat-name { font-weight: 600; display: flex; justify-content: space-between; margin-bottom: 0.3rem; }
        .badge-new { background: #dc2626; color: white; font-size: 0.6rem; padding: 0.15rem 0.4rem; border-radius: 20px; }
        .chat-nama { font-size: 0.7rem; color: #8e98a8; margin-bottom: 0.3rem; }
        .chat-preview { font-size: 0.75rem; color: #5a6474; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .chat-room { flex: 1; display: flex; flex-direction: column; }
        .chat-room-header {
            padding: 1rem;
            background: #f8f9fc;
            border-bottom: 1px solid var(--border);
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background: #fafbfc;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message { display: flex; }
        .message-user { justify-content: flex-start; }
        .message-admin { justify-content: flex-end; }
        .message-bubble {
            max-width: 60%;
            padding: 0.7rem 1rem;
            border-radius: 18px;
        }
        .message-user .message-bubble {
            background: white;
            border: 1px solid var(--border);
            border-bottom-left-radius: 4px;
        }
        .message-admin .message-bubble {
            background: var(--accent);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .message-header { font-size: 0.6rem; margin-bottom: 0.2rem; opacity: 0.7; display: flex; justify-content: space-between; gap: 0.8rem; }
        .message-text { font-size: 0.85rem; word-wrap: break-word; }
        
        .cart-preview {
            background: #f0fdf4;
            border-radius: 8px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.7rem;
        }
        
        .chat-input-area {
            padding: 1rem;
            border-top: 1px solid var(--border);
            background: white;
        }
        .chat-form { display: flex; gap: 0.5rem; align-items: flex-end; }
        .input-wrapper {
            flex: 1;
            background: #f0f2f6;
            border-radius: 24px;
            padding: 0.5rem 1rem;
        }
        .input-wrapper textarea {
            width: 100%;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.85rem;
            resize: none;
        }
        .input-wrapper textarea:focus { outline: none; }
        .send-btn {
            background: var(--accent);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .send-btn:hover { background: var(--accent-light); transform: scale(1.02); }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .empty-chat { text-align: center; padding: 2rem; color: #8e98a8; }
        
        .footer { background: #0a0e17; color: white; padding: 2rem 0; margin-top: 3rem; text-align: center; }
        .footer p { color: #8e98a8; font-size: 0.8rem; }
        
        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .chat-layout { flex-direction: column; }
            .chat-sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--border); max-height: 300px; }
            .message-bubble { max-width: 85%; }
            .navbar .container-nav { flex-direction: column; gap: 0.8rem; }
            .nav-links { flex-wrap: wrap; justify-content: center; }
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
            <a href="user.php" class="nav-link"><i class="fas fa-users"></i> User</a>
            <a href="transaksi.php" class="nav-link"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="komplain.php" class="nav-link"><i class="fas fa-comment-dots"></i> Komplain</a>
            <a href="chat.php" class="nav-link active"><i class="fas fa-comments"></i> Chat</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="admin-badge">Admin</span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-comments" style="color: var(--accent-gold);"></i> Chat Pelanggan</h1>
    </div>

    <div class="chat-layout">
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <i class="fas fa-users"></i> Percakapan
                <?php if($totalBelumDibaca > 0): ?>
                    <span style="background: #dc2626; color: white; padding: 0.1rem 0.4rem; border-radius: 20px; font-size: 0.7rem; margin-left: 0.5rem;"><?= $totalBelumDibaca ?> baru</span>
                <?php endif; ?>
            </div>
            <div class="chat-list">
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <a href="chat.php?nama=<?= urlencode($row['nama']) ?>" class="chat-item <?= ($detail_nama == $row['nama']) ? 'active' : '' ?>">
                            <div class="chat-name">
                                <?= htmlspecialchars($row['nama']) ?>
                                <?php if($row['belum_dibaca'] > 0): ?>
                                    <span class="badge-new"><?= $row['belum_dibaca'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="chat-preview"><?= strlen($row['pesan_terakhir']) > 45 ? substr($row['pesan_terakhir'], 0, 45) . '...' : $row['pesan_terakhir'] ?></div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; color: #8e98a8;">
                        <i class="fas fa-inbox"></i>
                        <p>Belum ada percakapan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-room">
            <?php if ($detail_nama && $selectedUser): ?>
                <div class="chat-room-header">
                    <strong><i class="fas fa-user-circle"></i> <?= htmlspecialchars($detail_nama) ?></strong>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($chatMessages as $pesan): ?>
                        <div class="message message-<?= $pesan['pengirim'] ?>">
                            <div class="message-bubble">
                                <div class="message-header">
                                    <span><?= $pesan['pengirim'] == 'user' ? htmlspecialchars($pesan['nama']) : 'Admin' ?></span>
                                    <span><?= date('H:i', strtotime($pesan['created_at'])) ?></span>
                                </div>
                                <div class="message-text"><?= nl2br(htmlspecialchars($pesan['pesan'])) ?></div>
                                <?php if (!empty($pesan['cart_data'])): 
                                    $cartData = json_decode($pesan['cart_data'], true);
                                ?>
                                    <div class="cart-preview">
                                        <strong>🛒 Keranjang Belanja:</strong><br>
                                        <?php foreach ($cartData as $item): ?>
                                            • <?= htmlspecialchars($item['judul']) ?> x<?= $item['qty'] ?> = Rp <?= number_format($item['subtotal'],0,',','.') ?><br>
                                        <?php endforeach; ?>
                                        <strong>Total: Rp <?= number_format(array_sum(array_column($cartData, 'subtotal')),0,',','.') ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($pesan['lampiran'])): ?>
                                    <div class="message-attachment">
                                        <a href="../uploads/chat/<?= $pesan['lampiran'] ?>" target="_blank" style="color: inherit;">
                                            <i class="fas fa-paperclip"></i> Lihat Lampiran
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="chat-input-area">
                    <div class="chat-form">
                        <div class="input-wrapper">
                            <textarea id="pesanInput" rows="1" placeholder="Tulis balasan untuk <?= htmlspecialchars($detail_nama) ?>..."></textarea>
                        </div>
                        <button class="send-btn" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div style="display: flex; justify-content: center; align-items: center; height: 100%; color: #8e98a8;">
                    <div style="text-align: center;">
                        <i class="fas fa-inbox" style="font-size: 2rem;"></i>
                        <p style="margin-top: 0.5rem;">Pilih percakapan dari daftar samping</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($detail_nama && $selectedUser): ?>
<script>
    const nama = '<?= $detail_nama ?>';
    const userId = <?= $selectedUser['user_id'] ?? 0 ?>;
    const messagesContainer = document.getElementById('chatMessages');
    const pesanInput = document.getElementById('pesanInput');
    const sendBtn = document.getElementById('sendBtn');
    
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    pesanInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });
    
    sendBtn.addEventListener('click', async function() {
        const pesan = pesanInput.value.trim();
        if (!pesan) return;
        
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="loading"></span>';
        btn.disabled = true;
        
        const formData = new FormData();
        formData.append('balas_pesan_ajax', '1');
        formData.append('nama', nama);
        formData.append('user_id', userId);
        formData.append('pesan', pesan);
        
        try {
            const response = await fetch(window.location.href, { 
                method: 'POST', 
                body: formData 
            });
            const result = await response.json();
            
            if (result.success) {
                pesanInput.value = '';
                pesanInput.style.height = 'auto';
                await loadChatMessages();
            } else {
                alert('Gagal mengirim: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengirim pesan');
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            pesanInput.focus();
        }
    });
    
    pesanInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendBtn.click();
        }
    });
    
    async function loadChatMessages() {
        try {
            const response = await fetch('get_chat.php?nama=' + encodeURIComponent(nama));
            const html = await response.text();
            messagesContainer.innerHTML = html;
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        } catch (error) {
            console.error('Error loading chat:', error);
        }
    }
    
    setInterval(loadChatMessages, 5000);
</script>
<?php endif; ?>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> LiteraBooks Admin Panel.</p>
    </div>
</footer>

</body>
</html>