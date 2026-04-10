<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    exit;
}

$nama = isset($_GET['nama']) ? mysqli_real_escape_string($koneksi, $_GET['nama']) : '';

$query = mysqli_query($koneksi, "
    SELECT * FROM chat 
    WHERE nama = '$nama'
    ORDER BY created_at ASC
");

if (mysqli_num_rows($query) > 0) {
    while ($pesan = mysqli_fetch_assoc($query)) {
        ?>
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
        <?php
    }
} else {
    echo '<div class="empty-chat"><p>Belum ada pesan</p></div>';
}
?>