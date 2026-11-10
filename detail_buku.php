<?php
require 'includes/init.php';
require 'includes/layout.php';

$st = $conn->prepare('SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
if (!($b = $st->fetch())) go('');

head($b['judul']);
?>
<div class="row g-4">
    <div class="col-md-4">
        <img src="<?= $baseUrl ?>assets/img/<?= e($b['gambar']) ?>" class="img-fluid rounded shadow-sm" alt="<?= e($b['judul']) ?>" onerror="this.src='<?= $baseUrl ?>assets/img/default.svg'">
    </div>
    <div class="col-md-8">
        <small class="text-muted"><?= e($b['nama_kategori']) ?></small>
        <h2 class="fw-bold"><?= e($b['judul']) ?></h2>
        <p class="text-muted"><?= e($b['penulis']) ?> &middot; <?= e($b['penerbit']) ?> <?= e($b['tahun_terbit']) ?> &middot; ISBN <?= e($b['isbn']) ?></p>
        <h3 class="fw-bold"><?= formatRupiah($b['harga']) ?> <small class="fs-6 text-muted">Stok <?= (int)$b['stok'] ?></small></h3>
        <p><?= nl2br(e($b['deskripsi'])) ?></p>
        <?php if ($b['stok'] > 0): ?>
            <form method="post" class="d-flex gap-2"><?= csrf() ?>
                <input type="number" name="jumlah" value="1" min="1" max="<?= (int)$b['stok'] ?>" class="form-control" style="width:90px">
                <button name="tambah" value="<?= $b['id'] ?>" class="btn btn-primary"><i class="fas fa-cart-plus me-1"></i>Tambah ke keranjang</button>
            </form>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>Stok habis</button>
        <?php endif; ?>
    </div>
</div>
<?php foot(); ?>
