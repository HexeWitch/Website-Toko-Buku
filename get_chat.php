<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user'])) {
    exit;
}

$username = $_SESSION['user']['nama'] ?? '';
$username_escaped = mysqli_real_escape_string($koneksi, $username);

$query = mysqli_query($koneksi, "
    SELECT * FROM chat 
    WHERE nama = '$username_escaped'
    ORDER BY created_at ASC
");

if (mysqli_num_rows($query) > 0) {
    while ($pesan = mysqli_fetch_assoc($query)) {
        ?>
        <div class="message message-<?= $pesan['pengirim'] ?>">
            <div class="message-bubble">
                <div class="message-header">
                    <span><?= $pesan['pengirim'] == 'user' ? 'Anda' : 'Admin' ?></span>
                    <span><?= date('H:i', strtotime($pesan['created_at'])) ?></span>
                </div>
                <div class="message-text"><?= nl2br(htmlspecialchars($pesan['pesan'])) ?></div>
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
        <?php
    }
} else {
    echo '<div class="empty-chat">
            <i class="fas fa-comment-dots" style="font-size: 2rem; color: #8e98a8;"></i>
            <p style="margin-top: 0.5rem;">Belum ada pesan</p>
            <p style="font-size: 0.8rem;">Kirim pesan pertama Anda ke admin</p>
          </div>';
}
?>