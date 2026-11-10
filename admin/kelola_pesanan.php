<?php
require '../includes/init.php';
require '../includes/layout.php';
requireAdmin();
$status = ['pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];

if (isset($_POST['id'], $_POST['status']) && csrf_ok() && in_array($_POST['status'], $status)) {
    if ($_POST['status'] === 'dibatalkan') {
        batalkanPesanan($conn, (int)$_POST['id']);   // stok dikembalikan
    } else {
        $conn->prepare("UPDATE pesanan SET status = ? WHERE id = ? AND status <> 'dibatalkan'")->execute([$_POST['status'], (int)$_POST['id']]);
    }
    flash('Status pesanan diperbarui.');
    go('admin/kelola_pesanan.php');
}

$list = $conn->query("SELECT p.*, u.username,
        GROUP_CONCAT(CONCAT(b.judul, ' x', d.jumlah) SEPARATOR ', ') AS isi
    FROM pesanan p JOIN users u ON u.id = p.user_id
    JOIN detail_pesanan d ON d.pesanan_id = p.id JOIN buku b ON b.id = d.buku_id
    GROUP BY p.id ORDER BY p.id DESC")->fetchAll();

head('Admin - Pesanan');
?>
<h3 class="fw-bold mb-3">Kelola Pesanan</h3>
<?php if (!$list) echo '<p class="text-muted">Belum ada pesanan.</p>'; ?>
<table class="table table-sm bg-white align-middle">
    <?php foreach ($list as $p): ?>
    <tr>
        <td>#<?= $p['id'] ?><br><small class="text-muted"><?= e($p['created_at']) ?></small></td>
        <td><b><?= e($p['nama_pembeli']) ?></b> (<?= e($p['username']) ?>)<br><small><?= e($p['isi']) ?></small></td>
        <td><?= formatRupiah($p['total_harga']) ?></td>
        <td>
            <form method="post" class="d-flex gap-1"><?= csrf() ?>
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <select name="status" class="form-select form-select-sm" <?= $p['status'] === 'dibatalkan' ? 'disabled' : '' ?>>
                    <?php foreach ($status as $s) echo "<option" . ($s === $p['status'] ? ' selected' : '') . ">$s</option>"; ?>
                </select>
                <button class="btn btn-sm btn-primary" <?= $p['status'] === 'dibatalkan' ? 'disabled' : '' ?>>OK</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php foot(); ?>