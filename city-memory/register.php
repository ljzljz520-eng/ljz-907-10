<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = '注册';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $display  = trim($_POST['display_name'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        flash('err', '用户名需为 3-20 位字母、数字或下划线');
    } elseif ($display === '' || mb_strlen($display) > 20) {
        flash('err', '请填写昵称（20 字以内）');
    } elseif (strlen($pass) < 6) {
        flash('err', '密码至少 6 位');
    } elseif ($pass !== $pass2) {
        flash('err', '两次输入的密码不一致');
    } else {
        try {
            db()->prepare('INSERT INTO users (username, password_hash, display_name) VALUES (?,?,?)')
                ->execute([$username, password_hash($pass, PASSWORD_DEFAULT), $display]);
            flash('ok', '注册成功，请登录');
            redirect('login.php');
        } catch (PDOException $ex) {
            flash('err', '用户名已被占用');
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">注册</h1>
<form method="post" class="panel">
  <?= csrf_field() ?>
  <div class="form-row">
    <label>用户名（登录用）</label>
    <input type="text" name="username" required pattern="[a-zA-Z0-9_]{3,20}">
  </div>
  <div class="form-row">
    <label>昵称（公开展示）</label>
    <input type="text" name="display_name" required maxlength="20">
  </div>
  <div class="form-row">
    <label>密码（至少 6 位）</label>
    <input type="password" name="password" required minlength="6">
  </div>
  <div class="form-row">
    <label>确认密码</label>
    <input type="password" name="password2" required>
  </div>
  <button class="btn" type="submit">注册</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
