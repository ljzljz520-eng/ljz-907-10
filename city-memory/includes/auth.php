<?php
require_once __DIR__ . '/helpers.php';

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    static $cache = null;
    if ($cache !== null && $cache['id'] === $_SESSION['uid']) return $cache;
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['uid']]);
    $cache = $stmt->fetch() ?: null;
    return $cache;
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        flash('warn', '请先登录');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
    return $u;
}

function require_admin(): array {
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') {
        http_response_code(403);
        exit('无权访问后台，请以管理员身份登录。');
    }
    return $u;
}

function login(string $username, string $password): bool {
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id'];
        return true;
    }
    return false;
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
}
