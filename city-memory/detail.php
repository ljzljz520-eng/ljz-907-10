<?php
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM media WHERE id = ? AND status='published'");
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) { http_response_code(404); exit('影像不存在或未公开'); }

// 提交留言（需登录，审核后公开）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    csrf_check();
    $u = require_login();
    $content = trim($_POST['comment']);
    if ($content === '' || mb_strlen($content) > 500) {
        flash('err', '留言不能为空且不超过 500 字');
    } else {
        db()->prepare('INSERT INTO comments (media_id, user_id, content) VALUES (?,?,?)')
            ->execute([$id, $u['id'], $content]);
        flash('ok', '留言已提交，审核通过后将公开展示');
    }
    redirect('detail.php?id=' . $id . '#comments');
}

db()->prepare('UPDATE media SET views = views + 1 WHERE id = ?')->execute([$id]);

// 已审核公开的线索与留言
$clues = db()->prepare("SELECT c.*, u.display_name FROM clues c JOIN users u ON u.id=c.user_id
                        WHERE c.media_id=? AND c.status='approved' ORDER BY c.id DESC");
$clues->execute([$id]);
$clues = $clues->fetchAll();

$comments = db()->prepare("SELECT c.*, u.display_name FROM comments c JOIN users u ON u.id=c.user_id
                           WHERE c.media_id=? AND c.status='approved' ORDER BY c.id DESC");
$comments->execute([$id]);
$comments = $comments->fetchAll();

$pageTitle = $m['title'];
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title"><?= e($m['title']) ?></h1>
<p class="page-sub">
  <span class="badge badge-<?= $m['type'] ?>"><?= media_type_label($m['type']) ?></span>
  <span class="badge badge-license"><?= e($m['license_status']) ?></span>
  浏览 <?= (int)$m['views'] + 1 ?> 次
</p>

<div class="detail-media">
<?php if ($m['type'] === 'photo'): ?>
  <img src="<?= UPLOAD_URL . '/' . e($m['file_path']) ?>" alt="<?= e($m['title']) ?>">
<?php else: ?>
  <video controls preload="metadata" src="<?= UPLOAD_URL . '/' . e($m['file_path']) ?>"></video>
<?php endif; ?>
</div>

<table class="meta-table">
  <tr><th>拍摄地点</th><td><?= e($m['district']) ?><?= $m['address'] ? ' · ' . e($m['address']) : '' ?></td></tr>
  <tr><th>年代</th><td><?= $m['year'] ? (int)$m['year'] . ' 年（' . (int)$m['decade'] . ' 年代）' : '待考证' ?></td></tr>
  <tr><th>讲述人</th><td><?= e($m['narrator'] ?: '佚名') ?></td></tr>
  <tr><th>授权状态</th><td><?= e($m['license_status']) ?></td></tr>
  <tr><th>资料来源</th><td><?= e($m['source'] ?: '—') ?></td></tr>
  <tr><th>简介</th><td><?= nl2br(e($m['description'])) ?></td></tr>
</table>

<div class="section">
  <h2>公开线索（<?= count($clues) ?>）</h2>
  <?php if (!$clues): ?>
    <div class="empty">暂无公开线索。如果您认识照片中的人、地点或事件，欢迎补充。</div>
  <?php endif; ?>
  <?php foreach ($clues as $c): ?>
    <div class="clue-item">
      <div class="who">【<?= e($c['clue_type']) ?>】<?= e($c['display_name']) ?> · <?= e($c['created_at']) ?></div>
      <?= nl2br(e($c['content'])) ?>
    </div>
  <?php endforeach; ?>
  <p class="mt">
    <?php if (current_user()): ?>
      <a class="btn" href="clue.php?media_id=<?= (int)$id ?>">我要补充线索</a>
    <?php else: ?>
      <a class="btn" href="login.php?next=<?= urlencode('clue.php?media_id=' . $id) ?>">登录后补充线索</a>
    <?php endif; ?>
  </p>
</div>

<div class="section" id="comments">
  <h2>留言（<?= count($comments) ?>）</h2>
  <?php if (!$comments): ?><div class="empty">还没有留言，来说几句吧。</div><?php endif; ?>
  <?php foreach ($comments as $c): ?>
    <div class="comment-item">
      <div class="who"><?= e($c['display_name']) ?> · <?= e($c['created_at']) ?></div>
      <?= nl2br(e($c['content'])) ?>
    </div>
  <?php endforeach; ?>

  <?php if (current_user()): ?>
  <form method="post" class="panel mt">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="comment">写下您的记忆或感想（审核后公开）</label>
      <textarea name="comment" id="comment" maxlength="500" required></textarea>
    </div>
    <button class="btn" type="submit">提交留言</button>
  </form>
  <?php else: ?>
    <p class="mt"><a class="btn" href="login.php?next=<?= urlencode('detail.php?id=' . $id . '#comments') ?>">登录后留言</a></p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
