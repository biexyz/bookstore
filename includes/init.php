<?php
// includes/init.php - database, session, helper, login, dan keranjang
session_start();

try {
    $conn = new PDO('mysql:host=localhost;dbname=bookstore_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal. Pastikan MySQL berjalan dan bookstore_db sudah diimpor.');
}

// Alamat folder aplikasi, mis. "/lsp/" (otomatis)
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$baseUrl = substr(str_replace('\\', '/', dirname(__DIR__)), strlen($docRoot)) . '/';

// ---- Helper ----
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function formatRupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function go($path) { global $baseUrl; header('Location: ' . $baseUrl . $path); exit; }
function flash($msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return; }
    $m = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $m;
}
function csrf() {
    $_SESSION['t'] = $_SESSION['t'] ?? bin2hex(random_bytes(16));
    return '<input type="hidden" name="t" value="' . $_SESSION['t'] . '">';
}
function csrf_ok() { return isset($_POST['t'], $_SESSION['t']) && hash_equals($_SESSION['t'], $_POST['t']); }

// ---- Login ----
function isLoggedIn() { return isset($_SESSION['user_id']); }
function isAdmin() { return ($_SESSION['user_role'] ?? '') === 'admin'; }
function requireLogin() {
    if (!isLoggedIn()) { $_SESSION['kembali'] = $_SERVER['REQUEST_URI']; go('login.php'); }
}
function requireAdmin() { requireLogin(); if (!isAdmin()) go(''); }

// ---- Keranjang: $_SESSION['cart'] = [id_buku => jumlah] ----
$_SESSION['cart'] = $_SESSION['cart'] ?? [];
function cartCount() { return array_sum($_SESSION['cart']); }

// Tombol <button name="tambah" value="ID_BUKU"> di halaman mana pun
if (isset($_POST['tambah']) && csrf_ok()) {
    $id = (int)$_POST['tambah'];
    $st = $conn->prepare('SELECT stok FROM buku WHERE id = ?');
    $st->execute([$id]);
    if (($stok = $st->fetchColumn()) !== false) {
        $n = min((int)$stok, ($_SESSION['cart'][$id] ?? 0) + max(1, (int)($_POST['jumlah'] ?? 1)));
        if ($n > 0) $_SESSION['cart'][$id] = $n;
        flash($n > 0 ? 'Buku ditambahkan ke keranjang.' : 'Maaf, stok habis.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Batalkan pesanan + kembalikan stok. User hanya boleh membatalkan yang masih pending.
function batalkanPesanan($conn, $oid, $uid = null) {
    $conn->beginTransaction();
    $st = $conn->prepare("UPDATE pesanan SET status = 'dibatalkan' WHERE id = ? AND status IN ("
        . ($uid ? "'pending'" : "'pending','diproses'") . ')' . ($uid ? ' AND user_id = ?' : ''));
    $st->execute($uid ? [$oid, $uid] : [$oid]);
    if ($st->rowCount()) {
        $conn->prepare('UPDATE buku b JOIN detail_pesanan d ON d.buku_id = b.id SET b.stok = b.stok + d.jumlah WHERE d.pesanan_id = ?')->execute([$oid]);
    }
    $conn->commit();
}
