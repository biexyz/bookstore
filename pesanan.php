<?php
require 'includes/init.php';
require 'includes/layout.php';
requireLogin();
$uid = $_SESSION['user_id'];

if (isset($_POST['batal']) && csrf_ok()) {
    batalkanPesanan($conn, (int)$_POST['batal'], $uid);
    flash('Pesanan dibatalkan.');
    go('pesanan.php?id=' . (int)$_POST['batal']);
}

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $st = $conn->prepare('SELECT * FROM pesanan WHERE id = ? AND user_id = ?');
    $st->execute([$id, $uid]);
    if (!($p = $st->fetch())) go('pesanan.php');
    $st = $conn->prepare('SELECT d.*, b.judul FROM detail_pesanan d JOIN buku b ON b.id = d.buku_id WHERE d.pesanan_id = ?');
    $st->execute([$id]);
} else {
    $st = $conn->prepare('SELECT * FROM pesanan WHERE user_id = ? ORDER BY id DESC');
    $st->execute([$uid]);
    $list = $st->fetchAll();
}

head('Pesanan Saya');
if ($id):
?>
    <a href="<?= $baseUrl ?>pesanan.php">&larr; Semua pesanan</a>
    <h3 class="fw-bold my-3">Pesanan #<?= $p['id'] ?> <span class="badge bg-secondary fs-6"><?= e($p['status']) ?></span></h3>
    <p class="text-muted"><?= e($p['nama_pembeli']) ?>, <?= e($p['no_hp']) ?><br>
       <?= e($p['alamat']) ?>, <?= e($p['kota']) ?> <?= e($p['kode_pos']) ?><br>
       Pembayaran: <?= e($p['metode_pembayaran']) ?> &middot; <?= e($p['created_at']) ?></p>
    <table class="table bg-white">
        <?php foreach ($st as $d): ?>
            <tr><td><?= e($d['judul']) ?></td><td><?= $d['jumlah'] ?> &times; <?= formatRupiah($d['harga_satuan']) ?></td><td class="text-end"><?= formatRupiah($d['subtotal']) ?></td></tr>
        <?php endforeach; ?>
        <tr class="fw-bold"><td colspan="2">Total</td><td class="text-end"><?= formatRupiah($p['total_harga']) ?></td></tr>
    </table>
    <?php if ($p['status'] === 'pending'): ?>
        <form method="post" onsubmit="return confirm('Batalkan pesanan ini?')"><?= csrf() ?>
            <button name="batal" value="<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm">Batalkan pesanan</button>
        </form>
    <?php endif; ?>
<?php else: ?>
    <h3 class="fw-bold mb-3">Pesanan Saya</h3>
    <?php if (!$list): ?><p class="text-muted">Belum ada pesanan.</p><?php endif; ?>
    <?php if ($list): ?>
    <table class="table bg-white align-middle">
        <?php foreach ($list as $p): ?>
        <tr>
            <td>#<?= $p['id'] ?></td><td><?= e($p['created_at']) ?></td>
            <td><?= formatRupiah($p['total_harga']) ?></td>
            <td><span class="badge bg-secondary"><?= e($p['status']) ?></span></td>
            <td><a href="?id=<?= $p['id'] ?>">Detail</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
<?php endif; ?>
<?php foot(); ?>