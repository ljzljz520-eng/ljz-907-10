<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = '片库';

$district   = trim($_GET['district'] ?? '');
$decade   = (int)($_GET['decade'] ?? 0);
$type     = $_GET['type'] ?? '';
$keyword  = trim($_GET['q'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;

// 筛选维度数据
$districts = db()->query("SELECT DISTINCT district FROM media WHERE status='published' ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);
$decades   = db()->query("SELECT DISTINCT decade FROM media WHERE status='published' AND decade IS NOT NULL ORDER BY decade")->fetchAll(PDO::FETCH_COLUMN);

// 组装查询
$where = ["status='published'"];
$args  = [];
if ($district !== '') { $where[] = 'district = ?'; $args[] = $district; }
if ($decade)          { $where[] = 'decade = ?';   $args[] = $decade; }
if (in_array($type, ['photo', 'video'], true)) { $where[] = 'type = ?'; $args[] = $type; }
if ($keyword !== '') {
    $where[] = '(title LIKE ? OR description LIKE ? OR address LIKE ? OR narrator LIKE ?)';
    $like = "%$keyword%";
    array_push($args, $like, $like, $like, $like);
}
$cond = implode(' AND ', $where);

$stmt = db()->prepare("SELECT COUNT(*) c FROM media WHERE $cond");
$stmt->execute($args);
$total = (int)$stmt->fetch()['c'];
[$page, $pages, $offset] = paginate($total, $page, $perPage);

$stmt = db()->prepare("SELECT * FROM media WHERE $cond ORDER BY year IS NULL, year, id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($args);
$items = $stmt->fetchAll();

// 构造保留筛选条件的链接
function filter_url(array $override): string {
    $p = array_merge([
        'district' => $_GET['district'] ?? '',
        'decade'   => $_GET['decade'] ?? '',
        'type'     => $_GET['type'] ?? '',
        'q'        => $_GET['q'] ?? '',
    ], $override);
    return 'index.php?' . http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null));
}

include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">影像片库</h1>
<p class="page-sub">按街区或年代，翻阅这座城市的老照片与旧影像。共 <?= $total ?> 条记录。</p>

<div class="filter-bar">
  <form method="get" class="filter-row search-box">
    <input type="text" name="q" value="<?= e($keyword) ?>" placeholder="搜索标题、地点、讲述人…">
    <button class="btn btn-sm" type="submit">搜索</button>
    <?php if ($keyword !== ''): ?><a class="chip" href="<?= e(filter_url(['q' => ''])) ?>">清除</a><?php endif; ?>
  </form>
  <div class="filter-row">
    <span class="filter-label">街区</span>
    <a class="chip <?= $district === '' ? 'on' : '' ?>" href="<?= e(filter_url(['district' => ''])) ?>">全部</a>
    <?php foreach ($districts as $d): ?>
      <a class="chip <?= $district === $d ? 'on' : '' ?>" href="<?= e(filter_url(['district' => $d])) ?>"><?= e($d) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="filter-row">
    <span class="filter-label">年代</span>
    <a class="chip <?= !$decade ? 'on' : '' ?>" href="<?= e(filter_url(['decade' => ''])) ?>">全部</a>
    <?php foreach ($decades as $d): ?>
      <a class="chip <?= $decade === (int)$d ? 'on' : '' ?>" href="<?= e(filter_url(['decade' => $d])) ?>"><?= (int)$d ?>年代</a>
    <?php endforeach; ?>
  </div>
  <div class="filter-row">
    <span class="filter-label">类型</span>
    <a class="chip <?= $type === '' ? 'on' : '' ?>" href="<?= e(filter_url(['type' => ''])) ?>">全部</a>
    <a class="chip <?= $type === 'photo' ? 'on' : '' ?>" href="<?= e(filter_url(['type' => 'photo'])) ?>">照片</a>
    <a class="chip <?= $type === 'video' ? 'on' : '' ?>" href="<?= e(filter_url(['type' => 'video'])) ?>">影像</a>
  </div>
</div>

<?php if (!$items): ?>
  <div class="empty">没有找到符合条件的影像，换个筛选条件试试。</div>
<?php else: ?>
<div class="grid">
  <?php foreach ($items as $m): ?>
  <a class="card" href="detail.php?id=<?= (int)$m['id'] ?>">
    <?php if ($m['type'] === 'photo'): ?>
      <img class="thumb" src="<?= UPLOAD_URL . '/' . e($m['file_path']) ?>" alt="<?= e($m['title']) ?>" loading="lazy">
    <?php else: ?>
      <div class="thumb-ph">▶</div>
    <?php endif; ?>
    <div class="card-body">
      <div class="card-title"><?= e($m['title']) ?></div>
      <div class="card-meta">
        <span class="badge badge-<?= $m['type'] ?>"><?= media_type_label($m['type']) ?></span>
        <?= e($m['district']) ?> · <?= $m['year'] ? (int)$m['year'] . '年' : '年代不详' ?><br>
        讲述人：<?= e($m['narrator'] ?: '佚名') ?>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($pages > 1): ?>
<div class="pager">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <?php if ($i === $page): ?><span class="cur"><?= $i ?></span>
    <?php else: ?><a href="<?= e(filter_url(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
