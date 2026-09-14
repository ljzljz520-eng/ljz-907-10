<?php
$base = '../';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pageTitle = '影像管理';
$adminPage = 'media';

// ---------- 操作：上下架 / 删除 / 保存编辑 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['act'] ?? '';

    if ($act === 'toggle') {
        db()->prepare("UPDATE media SET status = CASE status WHEN 'published' THEN 'draft' ELSE 'published' END WHERE id=?")->execute([$id]);
        flash('ok', '已切换公开状态');
    } elseif ($act === 'delete') {
        $stmt = db()->prepare('SELECT file_path FROM media WHERE id=?');
        $stmt->execute([$id]);
        if ($f = $stmt->fetchColumn()) {
            db()->prepare('DELETE FROM media WHERE id=?')->execute([$id]);
            $path = UPLOAD_PATH . '/' . $f;
            if (is_file($path)) unlink($path);
            flash('ok', '已删除影像及其关联线索、留言');
        }
    } elseif ($act === 'save') {
        $year = ($_POST['year'] ?? '') !== '' ? (int)$_POST['year'] : null;
        $license = in_array($_POST['license_status'] ?? '', LICENSE_OPTIONS, true) ? $_POST['license_status'] : '待授权';
        db()->prepare('UPDATE media SET title=?, district=?, address=?, year=?, decade=?, narrator=?, license_status=?, description=?, source=? WHERE id=?')
            ->execute([
                trim($_POST['title'] ?? '') ?: '未命名',
                trim($_POST['district'] ?? '') ?: '未标注街区',
                trim($_POST['address'] ?? ''),
                $year, decade_of($year),
                trim($_POST['narrator'] ?? ''),
                $license,
                trim($_POST['description'] ?? ''),
                trim($_POST['source'] ?? ''),
                $id,
            ]);
        flash('ok', '已保存修改');
    }
    redirect('media.php' . (isset($_POST['back_qs']) && $_POST['back_qs'] !== '' ? '?' . $_POST['back_qs'] : ''));
}

// ---------- 编辑视图 ----------
$editId = (int)($_GET['edit'] ?? 0);

// ---------- 列表筛选 ----------
$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$where = ['1=1']; $args = [];
if (in_array($status, ['published', 'draft'], true)) { $where[] = 'status=?'; $args[] = $status; }
if ($q !== '') { $where[] = '(title LIKE ? OR district LIKE ? OR narrator LIKE ?)'; $like = "%$q%"; array_push($args, $like, $like, $like); }
$cond = implode(' AND ', $where);
$stmt = db()->prepare("SELECT * FROM media WHERE $cond ORDER BY id DESC");
$stmt->execute($args);
$items = $stmt->fetchAll();
$qs = http_build_query(array_filter(['status' => $status, 'q' => $q]));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_nav.php';

if ($editId) {
    $stmt = db()->prepare('SELECT * FROM media WHERE id=?');
    $stmt->execute([$editId]);
    $m = $stmt->fetch();
    if (!$m) { echo '<div class="flash flash-err">记录不存在</div>'; include __DIR__ . '/../includes/footer.php'; exit; }
    ?>
    <h1 class="page-title">编辑影像 #<?= (int)$m['id'] ?></h1>
    <form method="post" class="panel wide">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <div class="form-cols">
        <div class="form-row"><label>标题</label><input type="text" name="title" value="<?= e($m['title']) ?>" required></div>
        <div class="form-row"><label>街区</label><input type="text" name="district" value="<?= e($m['district']) ?>" required></div>
        <div class="form-row"><label>拍摄地点</label><input type="text" name="address" value="<?= e($m['address']) ?>"></div>
        <div class="form-row"><label>拍摄年份</label><input type="number" name="year" value="<?= e((string)$m['year']) ?>" min="1840" max="<?= date('Y') ?>"></div>
        <div class="form-row"><label>讲述人</label><input type="text" name="narrator" value="<?= e($m['narrator']) ?>"></div>
        <div class="form-row"><label>授权状态</label>
          <select name="license_status">
            <?php foreach (LICENSE_OPTIONS as $l): ?>
              <option <?= $m['license_status'] === $l ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row"><label>资料来源</label><input type="text" name="source" value="<?= e($m['source']) ?>"></div>
      <div class="form-row"><label>简介</label><textarea name="description"><?= e($m['description']) ?></textarea></div>
      <button class="btn" type="submit">保存</button>
      <a class="btn btn-gray" href="media.php">取消</a>
    </form>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}
?>
<h1 class="page-title">影像管理</h1>
<p class="page-sub">共 <?= count($items) ?> 条。点击标题可编辑元数据。</p>

<form method="get" class="filter-bar">
  <div class="filter-row">
    <span class="filter-label">状态</span>
    <a class="chip <?= $status === '' ? 'on' : '' ?>" href="media.php">全部</a>
    <a class="chip <?= $status === 'published' ? 'on' : '' ?>" href="?status=published">已公开</a>
    <a class="chip <?= $status === 'draft' ? 'on' : '' ?>" href="?status=draft">草稿</a>
    <span style="flex:1"></span>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="搜索标题/街区/讲述人" style="padding:6px 10px;border:1px solid #d8cbb4;border-radius:6px">
    <button class="btn btn-sm">搜索</button>
  </div>
</form>

<table class="list">
  <tr><th>ID</th><th>标题</th><th>类型</th><th>街区 / 年代</th><th>讲述人</th><th>授权</th><th>状态</th><th>待审</th><th>操作</th></tr>
  <?php foreach ($items as $m):
    $pend = db()->prepare("SELECT (SELECT COUNT(*) FROM clues WHERE media_id=? AND status='pending') + (SELECT COUNT(*) FROM comments WHERE media_id=? AND status='pending')");
    $pend->execute([$m['id'], $m['id']]);
    $pendCount = (int)$pend->fetchColumn();
  ?>
  <tr>
    <td><?= (int)$m['id'] ?></td>
    <td><a href="media.php?edit=<?= (int)$m['id'] ?>"><?= e($m['title']) ?></a></td>
    <td><?= media_type_label($m['type']) ?></td>
    <td><?= e($m['district']) ?><br><?= $m['year'] ? (int)$m['year'] . '年' : '—' ?></td>
    <td><?= e($m['narrator'] ?: '—') ?></td>
    <td><span class="badge badge-license"><?= e($m['license_status']) ?></span></td>
    <td><?= $m['status'] === 'published' ? '已公开' : '<span class="badge badge-draft">草稿</span>' ?></td>
    <td><?= $pendCount ? "<a href='review.php?media_id={$m['id']}'>{$pendCount} 条</a>" : '—' ?></td>
    <td class="actions">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="back_qs" value="<?= e($qs) ?>">
        <button class="btn btn-sm <?= $m['status'] === 'published' ? 'btn-gray' : 'btn-green' ?>"><?= $m['status'] === 'published' ? '下架' : '公开' ?></button>
      </form>
      <form method="post" onsubmit="return confirm('确定删除？关联线索与留言将一并删除')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="back_qs" value="<?= e($qs) ?>">
        <button class="btn btn-sm btn-red">删除</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
