<?php
require '../includes/init.php';
require '../includes/layout.php';
requireAdmin();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    if (isset($_POST['hapus'])) {
        $st = $conn->prepare('SELECT COUNT(*) FROM detail_pesanan WHERE buku_id = ?');
        $st->execute([(int)$_POST['hapus']]);
        if ($st->fetchColumn()) {
            flash('Buku ini sudah pernah dipesan, jadi tidak dihapus. Ubah stoknya menjadi 0 saja.');
        } else {
            $conn->prepare('DELETE FROM buku WHERE id = ?')->execute([(int)$_POST['hapus']]);
            flash('Buku dihapus.');
        }
        go('admin/buku.php');
    }

    $v = [];
    foreach (['judul', 'penulis', 'penerbit', 'tahun_terbit', 'isbn', 'deskripsi'] as $k) $v[$k] = trim($_POST[$k] ?? '');
    $gambar = $_POST['gambar_lama'] ?? 'default.svg';
    if (!empty($_FILES['gambar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $_FILES['gambar']['size'] < 2000000 && @getimagesize($_FILES['gambar']['tmp_name'])) {
            $gambar = uniqid('buku_') . ".$ext";
            move_uploaded_file($_FILES['gambar']['tmp_name'], __DIR__ . "/../assets/img/$gambar");
        } else {
            $err = 'Gambar harus JPG/PNG/WEBP dan maksimal 2 MB.';
        }
    }
    if (!$err && ($v['judul'] === '' || $v['penulis'] === '')) $err = 'Judul dan penulis wajib diisi.';
    if (!$err) {
        $data = [(int)$_POST['kategori_id'], $v['judul'], $v['penulis'], $v['penerbit'], (int)$v['tahun_terbit'] ?: null,
                 $v['isbn'], (float)$_POST['harga'], (int)$_POST['stok'], $gambar, $v['deskripsi']];
        if (!empty($_POST['id'])) {
            $conn->prepare('UPDATE buku SET kategori_id=?, judul=?, penulis=?, penerbit=?, tahun_terbit=?, isbn=?, harga=?, stok=?, gambar=?, deskripsi=? WHERE id=?')
                 ->execute([...$data, (int)$_POST['id']]);
        } else {
            $conn->prepare('INSERT INTO buku (kategori_id, judul, penulis, penerbit, tahun_terbit, isbn, harga, stok, gambar, deskripsi) VALUES (?,?,?,?,?,?,?,?,?,?)')
                 ->execute($data);
        }
        flash('Buku disimpan.');
        go('admin/buku.php');
    }
}

$ed = ['id' => '', 'kategori_id' => '', 'judul' => '', 'penulis' => '', 'penerbit' => '', 'tahun_terbit' => '', 'isbn' => '', 'harga' => '', 'stok' => 0, 'gambar' => 'default.svg', 'deskripsi' => ''];
if (isset($_GET['edit'])) {
    $st = $conn->prepare('SELECT * FROM buku WHERE id = ?');
    $st->execute([(int)$_GET['edit']]);
    $ed = $st->fetch() ?: $ed;
}
$kats = $conn->query('SELECT * FROM kategori ORDER BY nama_kategori')->fetchAll();
$buku = $conn->query('SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON k.id = b.kategori_id ORDER BY b.id DESC')->fetchAll();

head('Admin - Buku');
?>
<h3 class="fw-bold mb-3">Kelola Buku</h3>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card card-body shadow-sm mb-4">
    <h5 class="fw-bold"><?= $ed['id'] ? 'Edit buku' : 'Tambah buku' ?></h5>
    <?= csrf() ?>
    <input type="hidden" name="id" value="<?= e($ed['id']) ?>">
    <input type="hidden" name="gambar_lama" value="<?= e($ed['gambar']) ?>">
    <div class="row g-2">
        <div class="col-md-6"><input name="judul" class="form-control" placeholder="Judul" value="<?= e($ed['judul']) ?>" required></div>
        <div class="col-md-6"><input name="penulis" class="form-control" placeholder="Penulis" value="<?= e($ed['penulis']) ?>" required></div>
        <div class="col-md-4"><input name="penerbit" class="form-control" placeholder="Penerbit" value="<?= e($ed['penerbit']) ?>"></div>
        <div class="col-md-2"><input name="tahun_terbit" class="form-control" placeholder="Tahun" value="<?= e($ed['tahun_terbit']) ?>"></div>
        <div class="col-md-3"><input name="isbn" class="form-control" placeholder="ISBN" value="<?= e($ed['isbn']) ?>"></div>
        <div class="col-md-3">
            <select name="kategori_id" class="form-select">
                <?php foreach ($kats as $k) echo '<option value="' . $k['id'] . '"' . ($k['id'] == $ed['kategori_id'] ? ' selected' : '') . '>' . e($k['nama_kategori']) . '</option>'; ?>
            </select>
        </div>
        <div class="col-md-3"><input type="number" name="harga" class="form-control" placeholder="Harga" min="0" value="<?= e($ed['harga']) ?>" required></div>
        <div class="col-md-3"><input type="number" name="stok" class="form-control" placeholder="Stok" min="0" value="<?= e($ed['stok']) ?>" required></div>
        <div class="col-md-6"><input type="file" name="gambar" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
        <div class="col-12"><textarea name="deskripsi" rows="2" class="form-control" placeholder="Deskripsi"><?= e($ed['deskripsi']) ?></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Simpan</button>
        <?php if ($ed['id']): ?><a href="<?= $baseUrl ?>admin/buku.php" class="btn btn-link">Batal</a><?php endif; ?></div>
</form>

<table class="table table-sm bg-white align-middle">
    <tr><th>Judul</th><th>Kategori</th><th>Harga</th><th>Stok</th><th></th></tr>
    <?php foreach ($buku as $b): ?>
    <tr>
        <td><?= e($b['judul']) ?></td><td><?= e($b['nama_kategori']) ?></td>
        <td><?= formatRupiah($b['harga']) ?></td><td><?= (int)$b['stok'] ?></td>
        <td class="text-end">
            <a href="?edit=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus buku ini?')"><?= csrf() ?>
                <button name="hapus" value="<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger">Hapus</button></form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php foot(); ?>