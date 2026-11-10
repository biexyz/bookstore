<?php
// includes/layout.php - kerangka halaman (hanya Bootstrap, tanpa CSS kustom)
function head($title = 'BookStore - Toko Buku Online') {
    global $baseUrl;
    $now = basename($_SERVER['SCRIPT_NAME']);
    $admin = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
    $menu = ['index.php' => 'Home'];
    if (isLoggedIn()) $menu['pesanan.php'] = 'Pesanan Saya';
    $flash = flash(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary fs-4" href="<?= $baseUrl ?>"><i class="fas fa-book me-2"></i>BookStore</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <?php foreach ($menu as $file => $label): ?>
                    <li class="nav-item"><a class="nav-link <?= $now === $file && !$admin ? 'active fw-bold' : '' ?>" href="<?= $baseUrl . $file ?>"><?= $label ?></a></li>
                <?php endforeach; ?>
                <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $admin ? 'active fw-bold' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>admin/buku.php">Kelola Buku</a></li>
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>admin/kelola_pesanan.php">Kelola Pesanan</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <form class="d-flex me-lg-3 my-2 my-lg-0" action="<?= $baseUrl ?>index.php" role="search">
                <input class="form-control me-2" type="search" name="q" placeholder="Cari buku..." aria-label="Cari" value="<?= e($_GET['q'] ?? '') ?>">
                <button class="btn btn-outline-primary" aria-label="Cari"><i class="fas fa-search"></i></button>
            </form>

            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item me-lg-2">
                    <a class="nav-link" href="<?= $baseUrl ?>keranjang.php" aria-label="Keranjang">
                        <i class="fas fa-shopping-bag fa-lg"></i>
                        <span class="badge rounded-pill bg-danger ms-1"><?= cartCount() ?></span>
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i><?= e($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item text-danger" href="<?= $baseUrl ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item me-2"><a class="btn btn-outline-primary" href="<?= $baseUrl ?>login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-primary" href="<?= $baseUrl ?>login.php?daftar=1">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
<?php if ($flash): ?>
    <div class="alert alert-info alert-dismissible fade show"><?= e($flash) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif;
}

function foot() { ?>
</main>
<footer class="text-center text-muted small py-4">&copy; <?= date('Y') ?> BookStore</footer>
<!-- Ganti nomor WhatsApp di bawah (format 62...) -->
<a href="https://wa.me/85188320725" class="btn btn-success rounded-circle position-fixed bottom-0 end-0 m-4 p-3 lh-1 fs-4 shadow" target="_blank" rel="noopener" aria-label="Chat WhatsApp"><i class="fab fa-whatsapp"></i></a>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php }
