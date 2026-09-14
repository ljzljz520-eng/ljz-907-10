<?php
require_once __DIR__ . '/auth.php';
$u = current_user();
$base = $base ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? APP_NAME) ?> · <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= $base ?>assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="logo" href="<?= $base ?>index.php">🏙️ <?= APP_NAME ?></a>
    <nav>
      <a href="<?= $base ?>index.php">片库</a>
      <?php if ($u): ?>
        <?php if ($u['role'] === 'admin'): ?><a href="<?= $base ?>admin/index.php">后台管理</a><?php endif; ?>
        <span class="hello">你好，<?= e($u['display_name']) ?></span>
        <a href="<?= $base ?>logout.php">退出</a>
      <?php else: ?>
        <a href="<?= $base ?>login.php">登录</a>
        <a href="<?= $base ?>register.php">注册</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap">
<?php foreach (flashes() as $f): ?>
  <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
