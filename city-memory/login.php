<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = '登录';
$next = $_GET['next'] ?? ($_POST['next'] ?? 'index.php');
if (!preg_match('/^[\w.\/?#=&%-]+$/', $next)) $next = 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
        flash('ok', '登录成功');
        redirect($next);
    }
    flash('err', '用户名或密码错误');
}
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">登录</h1>
<p class="page-sub">登录后可提交补充线索与留言。</p>
<form method="post" class="panel">
  <?= csrf_field() ?>
  <input type="hidden" name="next" value="<?= e($next) ?>">
  <div class="form-row">
    <label>用户名</label>
    <input type="text" name="username" required autofocus>
  </div>
  <div class="form-row">
    <label>密码</label>
    <input type="password" name="password" required>
  </div>
  <button class="btn" type="submit">登录</button>
  <span class="text-muted"> 还没有账号？<a href="register.php">注册</a></span>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
