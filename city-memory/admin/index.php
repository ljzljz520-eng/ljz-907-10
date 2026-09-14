<?php
$base = '../';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pageTitle = '后台管理';
$adminPage = 'index';

$stats = [
    '已公开影像' => db()->query("SELECT COUNT(*) FROM media WHERE status='published'")->fetchColumn(),
    '待公开(草稿)' => db()->query("SELECT COUNT(*) FROM media WHERE status='draft'")->fetchColumn(),
    '待审核线索' => db()->query("SELECT COUNT(*) FROM clues WHERE status='pending'")->fetchColumn(),
    '待审核留言' => db()->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(),
    '注册用户'   => db()->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
];
$recent = db()->query("SELECT m.*, u.display_name AS creator FROM media m LEFT JOIN users u ON u.id=m.created_by ORDER BY m.id DESC LIMIT 8")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_nav.php';
?>
<h1 class="page-title">馆务概览</h1>
<p class="page-sub">影像馆藏与审核工作一览。</p>

<div class="stat-cards">
  <?php foreach ($stats as $label => $num): ?>
  <div class="stat-card"><div class="num"><?= (int)$num ?></div><div class="lbl"><?= e($label) ?></div></div>
  <?php endforeach; ?>
</div>

<h2 class="page-title" style="font-size:20px">最新导入</h2>
<table class="list mt">
  <tr><th>ID</th><th>标题</th><th>类型</th><th>街区</th><th>年代</th><th>授权状态</th><th>状态</th><th>导入时间</th></tr>
  <?php foreach ($recent as $m): ?>
  <tr>
    <td><?= (int)$m['id'] ?></td>
    <td><a href="../detail.php?id=<?= (int)$m['id'] ?>"><?= e($m['title']) ?></a></td>
    <td><?= media_type_label($m['type']) ?></td>
    <td><?= e($m['district']) ?></td>
    <td><?= $m['year'] ? (int)$m['year'] : '—' ?></td>
    <td><span class="badge badge-license"><?= e($m['license_status']) ?></span></td>
    <td><?= $m['status'] === 'published' ? '已公开' : '<span class="badge badge-draft">草稿</span>' ?></td>
    <td><?= e($m['created_at']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
