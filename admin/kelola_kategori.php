<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Proses tambah kategori
if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    
    $cek = mysqli_query($koneksi, "SELECT id FROM kategori WHERE nama_kategori = '$nama_kategori'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Kategori sudah ada!";
    } else {
        $query = mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')");
        if ($query) {
            $success = "Kategori berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan kategori!";
        }
    }
}

// Proses edit kategori (AJAX)
if (isset($_POST['edit_kategori_ajax'])) {
    $id = (int)$_POST['id'];
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    
    $cek = mysqli_query($koneksi, "SELECT id FROM kategori WHERE nama_kategori = '$nama_kategori' AND id != $id");
    if (mysqli_num_rows($cek) > 0) {
        echo json_encode(['success' => false, 'error' => 'Kategori sudah ada!']);
        exit;
    } else {
        $query = mysqli_query($koneksi, "UPDATE kategori SET nama_kategori = '$nama_kategori' WHERE id = $id");
        if ($query) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal mengupdate kategori!']);
        }
        exit;
    }
}

// Ambil data kategori dengan jumlah buku dan total stok
$query = mysqli_query($koneksi, "
    SELECT 
        k.id,
        k.nama_kategori,
        COUNT(b.id) as jumlah_buku,
        COALESCE(SUM(b.stok), 0) as total_stok
    FROM kategori k
    LEFT JOIN buku b ON k.id = b.kategori_id
    GROUP BY k.id
    ORDER BY k.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori | LiteraBooks Admin</title>
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
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            background: #f0f2f6;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.5rem;
            color: var(--accent);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
        }

        .stat-info p {
            font-size: 0.8rem;
            color: #8e98a8;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 1px solid var(--border);
        }

        .form-card h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .form-group input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
        }

        .form-group button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            border: 1px solid var(--border);
            margin: 1.5rem 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        .data-table th {
            background: #f8f9fc;
            padding: 1rem;
            text-align: left;
            font-size: 0.85rem;
            color: #5a6474;
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Inline Edit */
        .editable-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-input {
            padding: 0.4rem 0.6rem;
            border: 1px solid var(--accent);
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            width: 180px;
        }

        .edit-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(45,59,94,0.2);
        }

        .save-edit {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.7rem;
        }

        .cancel-edit {
            background: #9ca3af;
            color: white;
            border: none;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.7rem;
        }

        .edit-btn {
            background: var(--accent-gold);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            cursor: pointer;
            border: none;
        }

        .edit-btn:hover {
            background: #8a7b5e;
        }

        .btn-hapus {
            background: var(--danger);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-hapus:hover {
            background: #b91c1c;
        }

        .btn-disabled {
            background: #9ca3af;
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            cursor: not-allowed;
        }

        .stok-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .stok-aman {
            background: #d1fae5;
            color: #065f46;
        }

        .stok-berbahaya {
            background: #fef3c7;
            color: #92400e;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
        }

        .loading {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .footer {
            background: #0a0e17;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
            text-align: center;
        }

        .footer p {
            color: #8e98a8;
            font-size: 0.8rem;
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
            .form-group {
                flex-direction: column;
            }
            .form-group button {
                width: 100%;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
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
            <a href="kelola_kategori.php" class="nav-link active"><i class="fas fa-tags"></i> Kategori</a>
            <a href="user.php" class="nav-link"><i class="fas fa-users"></i> User</a>
            <a href="transaksi.php" class="nav-link"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-tags"></i> Kelola Kategori</h1>
        <p>Tambah, edit, atau hapus kategori buku</p>
    </div>

    <!-- STATS CARDS -->
    <?php 
        $totalKategori = mysqli_num_rows($query);
        $totalBuku = 0;
        $totalStok = 0;
        mysqli_data_seek($query, 0);
        while($k = mysqli_fetch_assoc($query)) {
            $totalBuku += $k['jumlah_buku'];
            $totalStok += $k['total_stok'];
        }
        mysqli_data_seek($query, 0);
    ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tags"></i></div>
            <div class="stat-info">
                <h3><?= $totalKategori ?></h3>
                <p>Total Kategori</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-info">
                <h3><?= $totalBuku ?></h3>
                <p>Total Buku</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-info">
                <h3><?= number_format($totalStok) ?></h3>
                <p>Total Stok</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3><?= $totalKategori - mysqli_num_rows(mysqli_query($koneksi, "SELECT DISTINCT kategori_id FROM buku WHERE stok > 0")) ?></h3>
                <p>Kategori Kosong</p>
            </div>
        </div>
    </div>

    <!-- Form Tambah Kategori -->
    <div class="form-card" id="form-tambah">
        <h3><i class="fas fa-plus-circle"></i> Tambah Kategori Baru</h3>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="nama_kategori" placeholder="Nama kategori..." required>
                <button type="submit" name="tambah_kategori">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Kategori -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Kategori</th>
                    <th>Jumlah Buku</th>
                    <th>Total Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php while ($kategori = mysqli_fetch_assoc($query)): ?>
                        <?php 
                            $bisa_hapus = ($kategori['total_stok'] == 0 && $kategori['jumlah_buku'] == 0);
                        ?>
                        <tr id="row-<?= $kategori['id'] ?>">
                            <td>#<?= $kategori['id'] ?></td>
                            <td class="kategori-name-cell">
                                <span class="kategori-text-<?= $kategori['id'] ?>"><?= htmlspecialchars($kategori['nama_kategori']) ?></span>
                                <div class="edit-input-group-<?= $kategori['id'] ?>" style="display: none;">
                                    <input type="text" class="edit-input" id="edit-input-<?= $kategori['id'] ?>" value="<?= htmlspecialchars($kategori['nama_kategori']) ?>">
                                    <button class="save-edit" onclick="saveEdit(<?= $kategori['id'] ?>)">Simpan</button>
                                    <button class="cancel-edit" onclick="cancelEdit(<?= $kategori['id'] ?>)">Batal</button>
                                </div>
                            </td>
                            <td><?= $kategori['jumlah_buku'] ?> buku</td>
                            <td>
                                <?php if ($kategori['total_stok'] > 0): ?>
                                    <span class="stok-badge stok-berbahaya">
                                        <i class="fas fa-box"></i> <?= $kategori['total_stok'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="stok-badge stok-aman">
                                        <i class="fas fa-check-circle"></i> Stok Habis
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="edit-btn" onclick="showEdit(<?= $kategori['id'] ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($bisa_hapus): ?>
                                    <a href="hapus_kategori.php?id=<?= $kategori['id'] ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus kategori <?= htmlspecialchars($kategori['nama_kategori']) ?>?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                <?php else: ?>
                                    <span class="btn-disabled" title="Tidak bisa dihapus karena masih ada buku atau stok">
                                        <i class="fas fa-ban"></i> Terkunci
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 3rem;">
                            <i class="fas fa-folder-open" style="font-size: 2rem; color: #8e98a8;"></i>
                            <p style="margin-top: 0.5rem;">Belum ada kategori. Silakan tambah kategori terlebih dahulu.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> LiteraBooks Admin Panel. All rights reserved.</p>
    </div>
</footer>

<script>
    let currentEditId = null;
    
    function showEdit(id) {
        // Sembunyikan semua edit input yang terbuka
        document.querySelectorAll('[class^="edit-input-group-"]').forEach(el => {
            el.style.display = 'none';
        });
        document.querySelectorAll('[class^="kategori-text-"]').forEach(el => {
            el.style.display = 'inline';
        });
        
        // Tampilkan edit input untuk baris yang dipilih
        document.querySelector('.kategori-text-' + id).style.display = 'none';
        document.querySelector('.edit-input-group-' + id).style.display = 'flex';
        document.getElementById('edit-input-' + id).focus();
        currentEditId = id;
    }
    
    function cancelEdit(id) {
        document.querySelector('.kategori-text-' + id).style.display = 'inline';
        document.querySelector('.edit-input-group-' + id).style.display = 'none';
        // Reset value
        document.getElementById('edit-input-' + id).value = document.querySelector('.kategori-text-' + id).innerText;
    }
    
    async function saveEdit(id) {
        const newNama = document.getElementById('edit-input-' + id).value.trim();
        const originalNama = document.querySelector('.kategori-text-' + id).innerText;
        
        if (newNama === originalNama) {
            cancelEdit(id);
            return;
        }
        
        if (newNama === '') {
            alert('Nama kategori tidak boleh kosong!');
            return;
        }
        
        const saveBtn = document.querySelector('#row-' + id + ' .save-edit');
        const originalHtml = saveBtn.innerHTML;
        saveBtn.innerHTML = '<span class="loading"></span>';
        saveBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('edit_kategori_ajax', '1');
            formData.append('id', id);
            formData.append('nama_kategori', newNama);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                // Update tampilan
                document.querySelector('.kategori-text-' + id).innerText = newNama;
                cancelEdit(id);
                
                // Tampilkan alert sukses
                showAlert('success', 'Kategori berhasil diupdate!');
            } else {
                alert(result.error || 'Gagal mengupdate kategori');
                cancelEdit(id);
            }
        } catch (error) {
            alert('Terjadi kesalahan: ' + error.message);
            cancelEdit(id);
        } finally {
            saveBtn.innerHTML = originalHtml;
            saveBtn.disabled = false;
        }
    }
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type;
        alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
        
        const container = document.querySelector('.container');
        const pageHeader = document.querySelector('.page-header');
        container.insertBefore(alertDiv, pageHeader.nextSibling);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }
</script>

</body>
</html>