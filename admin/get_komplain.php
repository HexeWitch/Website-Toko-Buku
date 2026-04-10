<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    exit;
}

$transaksi_id = isset($_GET['transaksi_id']) ? (int)$_GET['transaksi_id'] : 0;

$query = mysqli_query($koneksi, "
    SELECT k.*, u.nama as user_nama
    FROM komplain k
    LEFT JOIN users u ON k.user_id = u.id
    WHERE k.transaksi_id = $transaksi_id
    ORDER BY k.created_at ASC
");

if (mysqli_num_rows($query) > 0) {
    while ($pesan = mysqli_fetch_assoc($query)) {
        ?>
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
                            <img src="../uploads/komplain/<?= $pesan['lampiran'] ?>" alt="Lampiran" onclick="window.open(this.src)" style="cursor: pointer;">
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
        <?php
    }
} else {
    echo '<div class="empty-chat"><i class="fas fa-comment"></i><p>Belum ada pesan</p></div>';
}
?>