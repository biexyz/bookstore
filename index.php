<?php
require 'includes/init.php';
require 'includes/layout.php';

$q = trim($_GET['q'] ?? '');
$kat = (int)($_GET['kategori'] ?? 0);
$urut = ['harga_asc' => 'harga ASC', 'harga_desc' => 'harga DESC'][$_GET['sort'] ?? ''] ?? 'id DESC';
$hal = max(1, (int)($_GET['hal'] ?? 1));
$per = 12;

$where = 'WHERE 1';
$p = [];
if ($q !== '') { $where .= ' AND (judul LIKE ? OR penulis LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
if ($kat) { $where .= ' AND kategori_id = ?'; $p[] = $kat; }

$st = $conn->prepare("SELECT COUNT(*) FROM buku $where");
$st->execute($p);
$jmlHal = max(1, (int)ceil($st->fetchColumn() / $per));
$st = $conn->prepare("SELECT * FROM buku $where ORDER BY $urut LIMIT $per OFFSET " . (($hal - 1) * $per));
$st->execute($p);
$buku = $st->fetchAll();
$kats = $conn->query('SELECT * FROM kategori ORDER BY nama_kategori')->fetchAll();
$qs = fn($o = []) => '?' . http_build_query(array_filter(array_merge($_GET, $o)));

head();
?>
<div class="rounded-4 p-4 mb-4 text-white bg-primary">
    <h1 class="fw-bold mb-1">Temukan Buku Favoritmu</h1>
    <p class="mb-0">Koleksi lengkap buku berkualitas dengan harga terjangkau, dikirim ke seluruh Indonesia.</p>
</div>

<div class="d-flex flex-wrap gap-2 align-items-center mb-4">
    <a href="<?= $baseUrl ?>" class="btn btn-sm <?= $kat ? 'btn-outline-primary' : 'btn-primary' ?>">Semua</a>
    <?php foreach ($kats as $k): ?>
        <a href="<?= $qs(['kategori' => $k['id'], 'hal' => 1]) ?>" class="btn btn-sm <?= $kat === (int)$k['id'] ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e($k['nama_kategori']) ?></a>
    <?php endforeach; ?>
    <form class="ms-auto">
        <?php foreach (['q', 'kategori'] as $f) if (!empty($_GET[$f])) echo '<input type="hidden" name="' . $f . '" value="' . e($_GET[$f]) . '">'; ?>
        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Terbaru</option>
            <option value="harga_asc" <?= ($_GET['sort'] ?? '') === 'harga_asc' ? 'selected' : '' ?>>Harga terendah</option>
            <option value="harga_desc" <?= ($_GET['sort'] ?? '') === 'harga_desc' ? 'selected' : '' ?>>Harga tertinggi</option>
        </select>
    </form>
</div>

<?php if (!$buku): ?><p class="text-center text-muted py-5">Tidak ada buku yang cocok.</p><?php endif; ?>

<div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-4">
<?php foreach ($buku as $b): ?>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <a href="<?= $baseUrl ?>detail_buku.php?id=<?= $b['id'] ?>">
                <img src="<?= $baseUrl ?>assets/img/<?= e($b['gambar']) ?>" class="card-img-top object-fit-cover" style="height:220px" alt="<?= e($b['judul']) ?>" onerror="this.src='<?= $baseUrl ?>assets/img/default.svg'">
            </a>
            <div class="card-body d-flex flex-column">
                <h6 class="fw-bold mb-0"><?= e($b['judul']) ?></h6>
                <small class="text-muted"><?= e($b['penulis']) ?></small>
                <div class="mt-auto pt-2">
                    <div class="fw-bold mb-2"><?= formatRupiah($b['harga']) ?></div>
                    <?php if ($b['stok'] > 0): ?>
                        <form method="post"><?= csrf() ?><button name="tambah" value="<?= $b['id'] ?>" class="btn btn-primary btn-sm w-100"><i class="fas fa-cart-plus me-1"></i>Tambah</button></form>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm w-100" disabled>Habis</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if ($jmlHal > 1): ?>
<nav class="mt-4"><ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $jmlHal; $i++): ?>
        <li class="page-item <?= $i === $hal ? 'active' : '' ?>"><a class="page-link" href="<?= $qs(['hal' => $i]) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php foot(); ?>
