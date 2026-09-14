<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();

$mediaId = (int)($_GET['media_id'] ?? $_POST['media_id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM media WHERE id=? AND status='published'");
$stmt->execute([$mediaId]);
$m = $stmt->fetch();
if (!$m) { http_response_code(404); exit('影像不存在'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $type = in_array($_POST['clue_type'] ?? '', CLUE_TYPES, true) ? $_POST['clue_type'] : '其他补充';
    $content = trim($_POST['content'] ?? '');
    if ($content === '' || mb_strlen($content) > 1000) {
        flash('err', '线索内容不能为空且不超过 1000 字');
    } else {
        db()->prepare('INSERT INTO clues (media_id, user_id, clue_type, content) VALUES (?,?,?,?)')
            ->execute([$mediaId, $u['id'], $type, $content]);
        flash('ok', '线索已提交，馆方审核通过后将公开展示');
        redirect('detail.php?id=' . $mediaId);
    }
}
$pageTitle = '补充线索';
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">补充线索</h1>
<p class="page-sub">为《<?= e($m['title']) ?>》提供线索，提交后由馆方审核，通过后公开展示。</p>
<form method="post" class="panel">
  <?= csrf_field() ?>
  <input type="hidden" name="media_id" value="<?= (int)$mediaId ?>">
  <div class="form-row">
    <label>线索类型</label>
    <select name="clue_type">
      <?php foreach (CLUE_TYPES as $t): ?><option><?= e($t) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="form-row">
    <label>线索内容</label>
    <textarea name="content" maxlength="1000" required placeholder="例如：照片中左侧人物是我的邻居张师傅；这栋楼 1995 年拆除……"></textarea>
    <div class="hint">请尽量写明依据（亲历、家中档案、同期照片等），便于馆方核实。</div>
  </div>
  <button class="btn" type="submit">提交线索</button>
  <a class="btn btn-gray" href="detail.php?id=<?= (int)$mediaId ?>">返回详情</a>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
