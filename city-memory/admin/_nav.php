<?php
// 后台导航（要求已在页面中 require_admin()）
$adminPage = $adminPage ?? '';
$pendingClues = (int)db()->query("SELECT COUNT(*) FROM clues WHERE status='pending'")->fetchColumn();
$pendingComments = (int)db()->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn();
$tabs = [
    'index'  => ['index.php', '概览'],
    'import' => ['import.php', '导入影像'],
    'media'  => ['media.php', '影像管理'],
    'review' => ['review.php', '审核中心' . (($pendingClues + $pendingComments) ? " (" . ($pendingClues + $pendingComments) . ")" : '')],
];
?>
<div class="admin-nav">
  <?php foreach ($tabs as $key => [$url, $label]): ?>
    <a class="<?= $adminPage === $key ? 'on' : '' ?>" href="<?= $url ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
  <a href="../index.php">← 返回前台</a>
</div>
