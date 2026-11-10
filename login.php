<?php
require 'includes/init.php';
require 'includes/layout.php';
if (isLoggedIn()) go('');

$daftar = isset($_GET['daftar']);
$err = '';
$f = fn($k) => trim($_POST[$k] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    if ($daftar) {
        $st = $conn->prepare('SELECT 1 FROM users WHERE username = ? OR email = ?');
        $st->execute([$f('username'), $f('email')]);
        if (!$f('nama_lengkap') || !$f('username') || !filter_var($f('email'), FILTER_VALIDATE_EMAIL) || strlen($_POST['password'] ?? '') < 6) {
            $err = 'Lengkapi semua data. Email harus valid dan password minimal 6 karakter.';
        } elseif ($st->fetch()) {
            $err = 'Username atau email sudah terdaftar.';
        } else {
            $conn->prepare("INSERT INTO users (nama_lengkap, username, email, password, role) VALUES (?, ?, ?, ?, 'user')")
                 ->execute([$f('nama_lengkap'), $f('username'), $f('email'), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
            flash('Registrasi berhasil. Silakan login.');
            go('login.php');
        }
    } else {
        $st = $conn->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $st->execute([$f('username'), $f('username')]);
        $u = $st->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['user_name'] = $u['nama_lengkap'];
            $_SESSION['user_email'] = $u['email'];
            $_SESSION['user_role'] = $u['role'];
            $to = $_SESSION['kembali'] ?? $baseUrl;
            unset($_SESSION['kembali']);
            header('Location: ' . $to);
            exit;
        }
        $err = 'Username/email atau password salah.';
    }
}

$fields = $daftar
    ? ['nama_lengkap' => ['Nama lengkap', 'text'], 'username' => ['Username', 'text'], 'email' => ['Email', 'email'], 'password' => ['Password', 'password']]
    : ['username' => ['Username / Email', 'text'], 'password' => ['Password', 'password']];

head($daftar ? 'Daftar' : 'Login');
?>
<form method="post" class="card card-body shadow-sm mx-auto" style="max-width:420px">
    <h4 class="fw-bold mb-3"><?= $daftar ? 'Buat akun' : 'Login ke BookStore' ?></h4>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
    <?= csrf() ?>
    <?php foreach ($fields as $name => [$label, $type]): ?>
        <label class="form-label fw-bold"><?= $label ?></label>
        <input type="<?= $type ?>" name="<?= $name ?>" class="form-control mb-3" required <?= $type === 'password' ? '' : 'value="' . e($_POST[$name] ?? '') . '"' ?>>
    <?php endforeach; ?>
    <button class="btn btn-primary w-100"><?= $daftar ? 'Daftar' : 'Login' ?></button>
    <p class="text-center text-muted mt-3 mb-0">
        <?= $daftar ? 'Sudah punya akun? <a href="login.php">Login</a>' : 'Belum punya akun? <a href="login.php?daftar=1">Daftar</a>' ?>
    </p>
</form>
<?php foot(); ?>
