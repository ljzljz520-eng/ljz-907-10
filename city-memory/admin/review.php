<?php
$base = '../';
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pageTitle = '审核中心';
$adminPage = 'review';

// ---------- 审核操作 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $kind = $_POST['kind'] ?? '';           // clue | comment
    $id   = (int)($_POST['id'] ?? 0);
    $to   = ($_POST['to'] ?? '') === 'approved' ? 'approved' : 'rejected';
    $note = trim($_POST['admin_note'] ?? '');

    if ($kind === 'clue') {
        db()->prepare('UPDATE clues SET status=?, admin_note=?, reviewed_by=?, reviewed_at=datetime("now","localtime") WHERE id=?')
            ->execute([$to, $note, $admin['id'], $id]);
    } elseif ($kind === 'comment') {
        db()->prepare('UPDATE comments SET status=? WHERE id=?')->execute([$to, $id]);
    }
    flash('ok', '已完成审核：' . ($to === 'approved' ? '通过并公开' : '驳回'));
    redirect('review.php?tab=' . urlencode($_GET['tab'] ?? 'clues'));
}

$tab = $_GET['tab'] ?? 'clues';
$mediaFilter = (int)($_GET['media_id'] ?? 0);

$clueWhere = $mediaFilter ? 'WHERE c.media_id=' . $mediaFilter : '';
$clues = db()->query("SELECT c.*, u.display_name, m.title AS media_title FROM clues c
                      JOIN users u ON u.id=c.user_id JOIN media m ON m.id=c.media_id
                      $clueWhere ORDER BY c.status='pending' DESC, c.id DESC LIMIT 100")->fetchAll();
$comments = db()->query("SELECT c.*, u.display_name, m.title AS media_title FROM comments c
                         JOIN users u ON u.id=c.user_id JOIN media m ON m.id=c.media_id
                         ORDER BY c.status='pending' DESC, c.id DESC LIMIT 100")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_nav.php';
?>
<h1 class="page-title">审核中心</h1>
<p class="page-sub">访客提交的补充线索与留言，审核通过后才会在前台公开。</p>

<div class="filter-row" style="margin-bottom:18px">
  <a class="chip <?= $tab === 'clues' ? 'on' : '' ?>" href="?tab=clues">补充线索</a>
  <a class="chip <?= $tab === 'comments' ? 'on' : '' ?>" href="?tab=comments">留言</a>
</div>

<?php if ($tab === 'clues'): ?>
  <?php if (!$clues): ?><div class="empty">暂无线索</div><?php endif; ?>
  <?php foreach ($clues as $c): ?>
  <div class="clue-item">
    <div class="who">
      <span class="badge badge-<?= $c['status'] ?>"><?= status_label($c['status']) ?></span>
      【<?= e($c['clue_type']) ?>】<?= e($c['display_name']) ?> 提交于 <?= e($c['created_at']) ?>
      · 关联影像：<a href="../detail.php?id=<?= (int)$c['media_id'] ?>"><?= e($c['media_title']) ?></a>
    </div>
    <p><?= nl2br(e($c['content'])) ?></p>
    <?php if ($c['status'] === 'pending'): ?>
    <form method="post" class="mt" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <?= csrf_field() ?>
      <input type="hidden" name="kind" value="clue">
      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
      <input type="text" name="admin_note" placeholder="审核备注（可选）" style="flex:1;min-width:200px;padding:6px 10px;border:1px solid #d8cbb4;border-radius:6px">
      <button class="btn btn-sm btn-green" name="to" value="approved">通过并公开</button>
      <button class="btn btn-sm btn-red" name="to" value="rejected">驳回</button>
    </form>
    <?php elseif ($c['admin_note']): ?>
      <p class="text-muted">审核备注：<?= e($c['admin_note']) ?></p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

<?php else: ?>
  <?php if (!$comments): ?><div class="empty">暂无留言</div><?php endif; ?>
  <?php foreach ($comments as $c): ?>
  <div class="comment-item">
    <div class="who">
      <span class="badge badge-<?= $c['status'] ?>"><?= status_label($c['status']) ?></span>
      <?= e($c['display_name']) ?> 提交于 <?= e($c['created_at']) ?>
      · 关联影像：<a href="../detail.php?id=<?= (int)$c['media_id'] ?>"><?= e($c['media_title']) ?></a>
    </div>
    <p><?= nl2br(e($c['content'])) ?></p>
    <?php if ($c['status'] === 'pending'): ?>
    <form method="post" class="mt" style="display:flex;gap:8px">
      <?= csrf_field() ?>
      <input type="hidden" name="kind" value="comment">
      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
      <button class="btn btn-sm btn-green" name="to" value="approved">通过并公开</button>
      <button class="btn btn-sm btn-red" name="to" value="rejected">驳回</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
