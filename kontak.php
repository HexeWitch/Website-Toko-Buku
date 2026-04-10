<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/koneksi.php';

$user_logged_in = isset($_SESSION['user']);
$user_id = $user_logged_in ? (int)$_SESSION['user']['id'] : 0;
$user_nama = $user_logged_in ? ($_SESSION['user']['nama'] ?? '') : '';

if (!$user_logged_in) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['kirim_pesan_ajax'])) {
    $pesan = mysqli_real_escape_string($koneksi, trim($_POST['pesan'] ?? ''));
    $cart_data = $_POST['cart_data'] ?? null;
    
    if (!empty($pesan) || !empty($cart_data)) {
        $lampiran = null;
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'application/pdf'];
            $max_size = 2 * 1024 * 1024;
            
            if (in_array($_FILES['lampiran']['type'], $allowed) && $_FILES['lampiran']['size'] <= $max_size) {
                $upload_dir = __DIR__ . '/uploads/chat/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
                $lampiran = 'chat_' . $user_id . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_dir . $lampiran);
            }
        }
        
        mysqli_query($koneksi, "
            INSERT INTO chat (user_id, nama, pesan, lampiran, cart_data, pengirim, created_at) 
            VALUES ($user_id, '$user_nama', '$pesan', '$lampiran', " . ($cart_data ? "'$cart_data'" : "NULL") . ", 'user', NOW())
        ");
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Pesan tidak boleh kosong']);
    }
    exit;
}

$chatHistory = [];
$username_escaped = mysqli_real_escape_string($koneksi, $user_nama);
$query = mysqli_query($koneksi, "
    SELECT * FROM chat 
    WHERE nama = '$username_escaped'
    ORDER BY created_at ASC
");
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $chatHistory[] = $row;
    }
}

$cartItems = [];
$cartTotal = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $qty) {
        $id = (int)$id;
        $qty = (int)$qty;
        $qBuku = mysqli_query($koneksi, "SELECT id, judul, harga FROM buku WHERE id = $id");
        $buku = mysqli_fetch_assoc($qBuku);
        if ($buku) {
            $subtotal = $buku['harga'] * $qty;
            $cartItems[] = [
                'id' => $buku['id'],
                'judul' => $buku['judul'],
                'harga' => $buku['harga'],
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
            $cartTotal += $subtotal;
        }
    }
}

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
    <title>Chat Support | LiteraBooks</title>
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
        .cart-badge { background: var(--accent); color: white; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 20px; margin-left: 0.3rem; }
        .user-badge { display: flex; align-items: center; gap: 0.5rem; padding-left: 1rem; margin-left: 0.5rem; border-left: 1px solid var(--border); }
        
        .page-header { margin: 2rem 0 1rem; }
        .page-header h1 { font-size: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .chat-wrapper {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin: 1rem 0;
        }
        .chat-header {
            background: #f8f9fc;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .support-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, var(--accent), var(--accent-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .support-avatar i { font-size: 1.3rem; color: white; }
        .support-info h3 { font-size: 1rem; font-weight: 600; margin-bottom: 0.2rem; }
        .support-info p { font-size: 0.75rem; color: #10b981; }
        
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            padding: 1.5rem;
            background: #fafbfc;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message { display: flex; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .message-user { justify-content: flex-end; }
        .message-admin { justify-content: flex-start; }
        .message-bubble {
            max-width: 60%;
            padding: 0.7rem 1rem;
            border-radius: 18px;
        }
        .message-user .message-bubble {
            background: var(--accent);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .message-admin .message-bubble {
            background: white;
            border: 1px solid var(--border);
            color: #1a1f2e;
            border-bottom-left-radius: 4px;
        }
        .message-header { font-size: 0.6rem; margin-bottom: 0.2rem; opacity: 0.7; display: flex; justify-content: space-between; gap: 0.8rem; }
        .message-text { font-size: 0.85rem; line-height: 1.4; word-wrap: break-word; }
        
        .cart-preview {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 0.6rem;
            margin-top: 0.5rem;
        }
        .cart-preview h4 { font-size: 0.7rem; margin-bottom: 0.3rem; color: #065f46; }
        .cart-preview-item { font-size: 0.7rem; margin: 0.2rem 0; }
        .cart-preview-total { font-size: 0.7rem; font-weight: 600; margin-top: 0.3rem; color: var(--accent); }
        
        .message-attachment { margin-top: 0.5rem; }
        .message-attachment img { max-width: 120px; border-radius: 8px; cursor: pointer; }
        .message-time { font-size: 0.55rem; margin-top: 0.3rem; opacity: 0.6; text-align: right; }
        
        .chat-input-area { padding: 1rem 1.5rem; background: white; border-top: 1px solid var(--border); }
        .input-group { display: flex; gap: 0.5rem; align-items: flex-end; }
        .input-wrapper {
            flex: 1;
            background: #f0f2f6;
            border-radius: 24px;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .input-wrapper textarea {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.3rem 0;
            font-family: inherit;
            font-size: 0.85rem;
            resize: none;
            max-height: 80px;
        }
        .input-wrapper textarea:focus { outline: none; }
        .tool-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #8e98a8;
            font-size: 1rem;
            padding: 0.3rem;
            border-radius: 50%;
        }
        .tool-btn:hover { color: var(--accent-gold); background: rgba(0,0,0,0.05); }
        .send-btn {
            background: var(--accent);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .send-btn:hover { background: var(--accent-light); transform: scale(1.02); }
        .file-preview { margin-top: 0.5rem; font-size: 0.7rem; color: var(--accent-gold); }
        
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
            border-radius: 20px;
            width: 90%;
            max-width: 450px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 1.5rem;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
        .cart-item { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border); font-size: 0.8rem; }
        .btn-send-cart { width: 100%; background: var(--accent); color: white; border: none; padding: 0.8rem; border-radius: 12px; margin-top: 1rem; cursor: pointer; font-weight: 600; }
        .empty-chat { text-align: center; padding: 2rem; color: #8e98a8; }
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
        #fileInput { display: none; }
        .btn-back { display: inline-flex; align-items: center; gap: 0.5rem; background: transparent; border: 1px solid var(--border); color: #1a1f2e; padding: 0.5rem 1rem; border-radius: 10px; text-decoration: none; font-size: 0.8rem; margin-top: 1rem; }
        
        .footer { background: #0a0e17; color: white; padding: 2rem 0; margin-top: 3rem; text-align: center; }
        .footer p { color: #8e98a8; font-size: 0.8rem; }
        
        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .navbar .container-nav { flex-direction: column; gap: 0.8rem; padding: 0.8rem; }
            .nav-links { flex-wrap: wrap; justify-content: center; gap: 0.8rem; }
            .message-bubble { max-width: 85%; }
            .chat-messages { height: 350px; }
        }
        .footer {
            background: #0a0e17;
            color: white;
            padding: 4rem 0 2rem;
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

        @media (max-width: 1024px) {
            .team-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .values-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1.5rem;
            }
            
            .navbar .container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            
            .about-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .vm-grid {
                grid-template-columns: 1fr;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .values-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
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
                <span><?= htmlspecialchars($user_nama); ?></span>
            </div>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-comment-dots" style="color: var(--accent-gold);"></i> Chat Customer Support</h1>
        <p>Ada pertanyaan? Silakan chat dengan admin kami</p>
    </div>

    <div class="chat-wrapper">
        <div class="chat-header">
            <div class="support-avatar"><i class="fas fa-headset"></i></div>
            <div class="support-info">
                <h3>Customer Support</h3>
                <p><i class="fas fa-circle" style="font-size: 0.5rem;"></i> Online - Siap membantu</p>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <?php if (!empty($chatHistory)): ?>
                <?php foreach ($chatHistory as $pesan): ?>
                    <div class="message message-<?= $pesan['pengirim'] ?>">
                        <div class="message-bubble">
                            <div class="message-header">
                                <span><?= $pesan['pengirim'] == 'user' ? 'Anda' : 'Admin' ?></span>
                                <span><?= date('H:i', strtotime($pesan['created_at'])) ?></span>
                            </div>
                            <?php if (!empty($pesan['pesan'])): ?>
                                <div class="message-text"><?= nl2br(htmlspecialchars($pesan['pesan'])) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($pesan['cart_data'])): 
                                $cartData = json_decode($pesan['cart_data'], true);
                            ?>
                                <div class="cart-preview">
                                    <h4><i class="fas fa-shopping-cart"></i> Keranjang Belanja</h4>
                                    <?php foreach ($cartData as $item): ?>
                                        <div class="cart-preview-item">• <?= htmlspecialchars($item['judul']) ?> x<?= $item['qty'] ?></div>
                                    <?php endforeach; ?>
                                    <div class="cart-preview-total">Total: Rp <?= number_format(array_sum(array_column($cartData, 'subtotal')),0,',','.') ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($pesan['lampiran'])): ?>
                                <div class="message-attachment">
                                    <?php 
                                        $ext = pathinfo($pesan['lampiran'], PATHINFO_EXTENSION);
                                        $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    ?>
                                    <?php if ($isImage): ?>
                                        <img src="uploads/chat/<?= $pesan['lampiran'] ?>" alt="Lampiran" onclick="window.open(this.src)">
                                    <?php else: ?>
                                        <a href="uploads/chat/<?= $pesan['lampiran'] ?>" target="_blank">Lihat Lampiran</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="message-time"><?= date('d/m/Y H:i', strtotime($pesan['created_at'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-chat">
                    <i class="fas fa-comment-dots" style="font-size: 2rem; color: #8e98a8;"></i>
                    <p style="margin-top: 0.5rem;">Belum ada pesan</p>
                    <p style="font-size: 0.8rem;">Kirim pesan pertama Anda ke admin</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="chat-input-area">
            <div class="input-group">
                <div class="input-wrapper">
                    <textarea id="pesanInput" rows="1" placeholder="Tulis pesan..."></textarea>
                    <button class="tool-btn" id="cartBtn" title="Kirim Keranjang"><i class="fas fa-shopping-cart"></i></button>
                    <button class="tool-btn" id="fileBtn" title="Lampirkan File"><i class="fas fa-paperclip"></i></button>
                    <input type="file" id="fileInput" accept="image/*,application/pdf">
                </div>
                <button class="send-btn" id="sendBtn"><i class="fas fa-paper-plane"></i></button>
            </div>
            <div class="file-preview" id="filePreview"></div>
        </div>
    </div>

    <div style="text-align: right;">
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
    </div>
</div>

 <div id="cartModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-shopping-cart"></i> Kirim Keranjang</h3>
            <button class="tool-btn" onclick="closeCartModal()" style="font-size: 1.2rem;">&times;</button>
        </div>
        <div id="cartItemsList">
            <?php if (!empty($cartItems)): ?>
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <span><?= htmlspecialchars($item['judul']) ?> x<?= $item['qty'] ?></span>
                        <span>Rp <?= number_format($item['subtotal'],0,',','.') ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="cart-item" style="font-weight: 600; border-top: 2px solid var(--border); margin-top: 0.5rem; padding-top: 0.8rem;">
                    <span>Total</span>
                    <span>Rp <?= number_format($cartTotal,0,',','.') ?></span>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #8e98a8;">Keranjang Anda kosong</p>
            <?php endif; ?>
        </div>
        <?php if (!empty($cartItems)): ?>
            <button class="btn-send-cart" id="sendCartBtn"><i class="fas fa-paper-plane"></i> Kirim ke Admin</button>
        <?php endif; ?>
    </div>
</div>

<script>
    const chatMessages = document.getElementById('chatMessages');
    const pesanInput = document.getElementById('pesanInput');
    const sendBtn = document.getElementById('sendBtn');
    const fileInput = document.getElementById('fileInput');
    const fileBtn = document.getElementById('fileBtn');
    const filePreview = document.getElementById('filePreview');
    const cartBtn = document.getElementById('cartBtn');
    const cartModal = document.getElementById('cartModal');
    
    let selectedFile = null;
    
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    pesanInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });
    
    fileBtn.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            selectedFile = e.target.files[0];
            filePreview.innerHTML = '<i class="fas fa-paperclip"></i> ' + selectedFile.name;
        } else {
            selectedFile = null;
            filePreview.innerHTML = '';
        }
    });
    
    cartBtn.addEventListener('click', () => cartModal.style.display = 'flex');
    
    function closeCartModal() { cartModal.style.display = 'none'; }
    
    window.onclick = function(e) { if (e.target === cartModal) closeCartModal(); }
    
    document.getElementById('sendCartBtn')?.addEventListener('click', async function() {
        const cartData = <?= json_encode($cartItems) ?>;
        if (cartData.length === 0) return;
        
        const btn = this;
        btn.innerHTML = '<span class="loading"></span> Mengirim...';
        btn.disabled = true;
        
        const formData = new FormData();
        formData.append('kirim_pesan_ajax', '1');
        formData.append('cart_data', JSON.stringify(cartData));
        
        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                closeCartModal();
                await loadChatMessages();
            } else {
                alert('Gagal mengirim keranjang');
            }
        } catch (error) { alert('Terjadi kesalahan'); }
        finally {
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim ke Admin';
            btn.disabled = false;
        }
    });
    
    sendBtn.addEventListener('click', async function() {
        const pesan = pesanInput.value.trim();
        if (!pesan && !selectedFile) return;
        
        const btn = this;
        btn.innerHTML = '<span class="loading"></span>';
        btn.disabled = true;
        
        const formData = new FormData();
        formData.append('kirim_pesan_ajax', '1');
        formData.append('pesan', pesan);
        if (selectedFile) formData.append('lampiran', selectedFile);
        
        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                pesanInput.value = '';
                pesanInput.style.height = 'auto';
                selectedFile = null;
                fileInput.value = '';
                filePreview.innerHTML = '';
                await loadChatMessages();
            } else {
                alert(result.error || 'Gagal mengirim pesan');
            }
        } catch (error) { alert('Terjadi kesalahan'); }
        finally {
            btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
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
            const response = await fetch('get_chat.php');
            const html = await response.text();
            chatMessages.innerHTML = html;
            chatMessages.scrollTop = chatMessages.scrollHeight;
        } catch (error) {}
    }
    
    setInterval(loadChatMessages, 5000);
</script>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-logo">
                    <div class="footer-logo-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
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
                    <li><a href="about.php">Tentang Kami</a></li>
                </ul>
            </div>
            
            <div>
                <h4>Bantuan</h4>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="kontak.php">Kontak Kami</a></li>
                </ul>
            </div>
            
            <div>
                <h4>Kontak</h4>
                <ul class="footer-links">
                    <li><i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> hello@literabooks.com</li>
                    <li><i class="fas fa-phone" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> +62 812 3456 7890</li>
                    <li><i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> Jakarta, Indonesia</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 LiteraBooks. All rights reserved. Dibuat dengan <i class="fas fa-heart" style="color: var(--accent-gold);"></i> untuk para pembaca</p>
        </div>
    </div>
</footer>

</body>
</html>