<?php
require 'includes/init.php';
require 'includes/layout.php';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok() && !isset($_POST['tambah'])) {
    // Perbarui jumlah / hapus item
    if (isset($_POST['qty'])) {
        foreach ($_POST['qty'] as $id => $n) {
            if ((int)$n > 0) $_SESSION['cart'][(int)$id] = (int)$n; else unset($_SESSION['cart'][(int)$id]);
        }
        if (isset($_POST['hapus'])) unset($_SESSION['cart'][(int)$_POST['hapus']]);
        go('keranjang.php');
    }

    // Checkout
    if (isset($_POST['pesan']) && $_SESSION['cart']) {
        requireLogin();
        $d = [];
        foreach (['nama_pembeli', 'email', 'no_hp', 'alamat', 'kota', 'kode_pos', 'metode_pembayaran'] as $k) $d[$k] = trim($_POST[$k] ?? '');
        if (in_array('', $d, true)) {
            $err = 'Semua data pengiriman wajib diisi.';
        } else {
            try {
                $conn->beginTransaction();
                $ids = array_keys($_SESSION['cart']);
                $in = implode(',', array_fill(0, count($ids), '?'));
                $st = $conn->prepare("SELECT id, judul, harga, stok FROM buku WHERE id IN ($in) FOR UPDATE");
                $st->execute($ids);
                $rows = $st->fetchAll();
                if (!$rows) throw new RuntimeException('Keranjang kosong.');
                $total = 0;
                foreach ($rows as $r) {
                    $n = $_SESSION['cart'][$r['id']];
                    if ($n > $r['stok']) throw new RuntimeException('Stok "' . $r['judul'] . '" tidak cukup.');
                    $total += $n * $r['harga'];
                }
                $conn->prepare('INSERT INTO pesanan (user_id, nama_pembeli, email, no_hp, alamat, kota, kode_pos, metode_pembayaran, total_harga) VALUES (?,?,?,?,?,?,?,?,?)')
                     ->execute([$_SESSION['user_id'], ...array_values($d), $total]);
                $oid = $conn->lastInsertId();
                $ins = $conn->prepare('INSERT INTO detail_pesanan (pesanan_id, buku_id, jumlah, harga_satuan, subtotal) VALUES (?,?,?,?,?)');
                $upd = $conn->prepare('UPDATE buku SET stok = stok - ? WHERE id = ?');
                foreach ($rows as $r) {
                    $n = $_SESSION['cart'][$r['id']];
                    $ins->execute([$oid, $r['id'], $n, $r['harga'], $n * $r['harga']]);
                    $upd->execute([$n, $r['id']]);
                }
                $conn->commit();
                $_SESSION['cart'] = [];
                flash("Pesanan #$oid berhasil dibuat. Terima kasih!");
                go('pesanan.php?id=' . $oid);
            } catch (RuntimeException $x) {
                $conn->rollBack();
                $err = $x->getMessage();
            } catch (PDOException $x) {
                $conn->rollBack();
                $err = 'Pesanan gagal diproses. Silakan coba lagi.';
            }
        }
    }
}

$items = [];
$total = 0;
if ($_SESSION['cart']) {
    $ids = array_keys($_SESSION['cart']);
    $st = $conn->prepare('SELECT * FROM buku WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
    $st->execute($ids);
    $items = $st->fetchAll();
    foreach ($items as $i) $total += $i['harga'] * $_SESSION['cart'][$i['id']];
}

head('Keranjang');
?>
<h3 class="fw-bold mb-3">Keranjang</h3>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<?php if (!$items): ?>
    <p class="text-center text-muted py-5">Keranjang masih kosong. <a href="<?= $baseUrl ?>">Lihat koleksi buku</a></p>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-7">
        <form method="post">
            <?= csrf() ?>
            <button class="btn btn-outline-primary btn-sm mb-2">Perbarui jumlah</button>
            <table class="table align-middle bg-white">
                <?php foreach ($items as $i): $n = $_SESSION['cart'][$i['id']]; ?>
                <tr>
                    <td><b><?= e($i['judul']) ?></b><br><small class="text-muted"><?= formatRupiah($i['harga']) ?></small></td>
                    <td><input type="number" name="qty[<?= $i['id'] ?>]" value="<?= $n ?>" min="0" max="<?= (int)$i['stok'] ?>" class="form-control form-control-sm" style="width:75px"></td>
                    <td class="text-end"><?= formatRupiah($i['harga'] * $n) ?></td>
                    <td><button name="hapus" value="<?= $i['id'] ?>" class="btn btn-sm text-danger" aria-label="Hapus"><i class="fas fa-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
                <tr class="fw-bold"><td colspan="2">Total</td><td class="text-end"><?= formatRupiah($total) ?></td><td></td></tr>
            </table>
        </form>
    </div>
    <div class="col-lg-5">
    <?php if (!isLoggedIn()): ?>
        <div class="card card-body"><a href="<?= $baseUrl ?>login.php">Login</a> dulu untuk memesan.</div>
    <?php else:
        $fields = ['nama_pembeli' => 'Nama penerima', 'email' => 'Email', 'no_hp' => 'No. HP', 'kota' => 'Kota', 'kode_pos' => 'Kode pos'];
        $isi = ['nama_pembeli' => $_SESSION['user_name'], 'email' => $_SESSION['user_email'] ?? ''] + $_POST; ?>
        <form method="post" class="card card-body shadow-sm">
            <h5 class="fw-bold">Data pengiriman</h5>
            <?= csrf() ?>
            <?php foreach ($fields as $k => $label): ?>
                <input name="<?= $k ?>" class="form-control mb-2" placeholder="<?= $label ?>" value="<?= e($isi[$k] ?? '') ?>" required>
            <?php endforeach; ?>
            <textarea name="alamat" rows="2" class="form-control mb-2" placeholder="Alamat lengkap" required><?= e($_POST['alamat'] ?? '') ?></textarea>
            <select name="metode_pembayaran" class="form-select mb-3">
                <?php foreach (['Transfer Bank', 'COD', 'E-Wallet'] as $m) echo "<option>$m</option>"; ?>
            </select>
            <button name="pesan" value="1" class="btn btn-primary">Pesan sekarang (<?= formatRupiah($total) ?>)</button>
        </form>
    <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php foot(); ?>
