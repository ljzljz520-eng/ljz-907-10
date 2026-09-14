<?php
$base = '../';
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pageTitle = '导入影像';
$adminPage = 'import';

// ---------- 单条导入 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'single') {
    csrf_check();
    try {
        [$file, $type] = handle_upload($_FILES['media_file'] ?? []);
        $title = trim($_POST['title'] ?? '');
        if ($title === '') throw new RuntimeException('请填写标题');
        $year = ($_POST['year'] ?? '') !== '' ? (int)$_POST['year'] : null;
        $license = in_array($_POST['license_status'] ?? '', LICENSE_OPTIONS, true) ? $_POST['license_status'] : '待授权';
        $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

        db()->prepare('INSERT INTO media (title, type, file_path, district, address, year, decade, narrator, license_status, description, source, status, created_by)
                       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $title, $type, $file,
                trim($_POST['district'] ?? '') ?: '未标注街区',
                trim($_POST['address'] ?? ''),
                $year, decade_of($year),
                trim($_POST['narrator'] ?? ''),
                $license,
                trim($_POST['description'] ?? ''),
                trim($_POST['source'] ?? ''),
                $status, $admin['id'],
            ]);
        flash('ok', '导入成功：' . $title);
        redirect('import.php');
    } catch (RuntimeException $ex) {
        flash('err', $ex->getMessage());
    }
}

// ---------- CSV 批量导入 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'batch') {
    csrf_check();
    $ok = 0; $fail = [];
    try {
        // 1) 先把多选的媒体文件存入 uploads，按原始文件名索引
        $fileMap = [];
        if (!empty($_FILES['batch_files']['name'][0])) {
            foreach ($_FILES['batch_files']['name'] as $i => $origName) {
                $one = [
                    'name' => $origName,
                    'tmp_name' => $_FILES['batch_files']['tmp_name'][$i],
                    'error' => $_FILES['batch_files']['error'][$i],
                    'size' => $_FILES['batch_files']['size'][$i],
                ];
                try {
                    [$saved] = handle_upload($one);
                    $fileMap[basename($origName)] = $saved;
                } catch (RuntimeException $ex) {
                    $fail[] = "文件 {$origName}：{$ex->getMessage()}";
                }
            }
        }
        // 2) 解析 CSV
        $csvTmp = $_FILES['csv_file']['tmp_name'] ?? null;
        if (!$csvTmp || ($_FILES['csv_file']['error'] ?? 1) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('请上传 CSV 文件');
        }
        $fh = fopen($csvTmp, 'r');
        // 去 BOM
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($fh);
        $header = fgetcsv($fh);
        if (!$header) throw new RuntimeException('CSV 为空');
        $header = array_map('trim', $header);
        $need = ['file', 'title', 'district', 'year', 'narrator', 'license_status'];
        foreach ($need as $col) {
            if (!in_array($col, $header, true)) throw new RuntimeException("CSV 缺少必需列：$col");
        }
        $idx = array_flip($header);
        $stmt = db()->prepare('INSERT INTO media (title, type, file_path, district, address, year, decade, narrator, license_status, description, source, status, created_by)
                               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $rowNo = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $rowNo++;
            $get = fn($k) => trim($row[$idx[$k]] ?? '');
            $orig = basename($get('file'));
            if (!isset($fileMap[$orig])) { $fail[] = "第{$rowNo}行：找不到文件 {$orig}"; continue; }
            if ($get('title') === '') { $fail[] = "第{$rowNo}行：标题为空"; continue; }
            $year = $get('year') !== '' ? (int)$get('year') : null;
            $license = in_array($get('license_status'), LICENSE_OPTIONS, true) ? $get('license_status') : '待授权';
            $ext = strtolower(pathinfo($fileMap[$orig], PATHINFO_EXTENSION));
            $type = in_array($ext, ALLOWED_VIDEO_EXT, true) ? 'video' : 'photo';
            $stmt->execute([
                $get('title'), $type, $fileMap[$orig],
                $get('district') ?: '未标注街区',
                $get('address'), $year, decade_of($year),
                $get('narrator'), $license,
                $get('description'), $get('source'),
                $get('status') === 'draft' ? 'draft' : 'published',
                $admin['id'],
            ]);
            $ok++;
        }
        fclose($fh);
        flash($fail ? 'warn' : 'ok', "批量导入完成：成功 {$ok} 条" . ($fail ? '，失败 ' . count($fail) . ' 条：' . implode('；', $fail) : ''));
        redirect('import.php');
    } catch (RuntimeException $ex) {
        flash('err', $ex->getMessage());
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_nav.php';
?>
<h1 class="page-title">导入影像</h1>
<p class="page-sub">支持老照片（jpg/png/gif/webp）与影像（mp4/webm/mov），单个文件不超过 50MB。</p>

<form method="post" enctype="multipart/form-data" class="panel wide">
  <?= csrf_field() ?>
  <input type="hidden" name="mode" value="single">
  <h2 style="margin-bottom:14px;font-size:18px">单条导入</h2>
  <div class="form-cols">
    <div class="form-row">
      <label>影像文件 *</label>
      <input type="file" name="media_file" required accept="image/*,video/*">
    </div>
    <div class="form-row">
      <label>标题 *</label>
      <input type="text" name="title" required maxlength="100">
    </div>
    <div class="form-row">
      <label>街区 *</label>
      <input type="text" name="district" required placeholder="如：南门街区" list="district-list">
      <datalist id="district-list">
        <?php foreach (db()->query('SELECT DISTINCT district FROM media ORDER BY district')->fetchAll(PDO::FETCH_COLUMN) as $d): ?>
          <option value="<?= e($d) ?>">
        <?php endforeach; ?>
      </datalist>
    </div>
    <div class="form-row">
      <label>拍摄地点</label>
      <input type="text" name="address" placeholder="如：人民路 12 号门前">
    </div>
    <div class="form-row">
      <label>拍摄年份</label>
      <input type="number" name="year" min="1840" max="<?= date('Y') ?>" placeholder="如 1985">
    </div>
    <div class="form-row">
      <label>讲述人</label>
      <input type="text" name="narrator" placeholder="提供影像/口述的讲述人">
    </div>
    <div class="form-row">
      <label>授权状态 *</label>
      <select name="license_status">
        <?php foreach (LICENSE_OPTIONS as $l): ?><option><?= e($l) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <label>公开状态</label>
      <select name="status">
        <option value="published">直接公开</option>
        <option value="draft">存为草稿</option>
      </select>
    </div>
  </div>
  <div class="form-row">
    <label>资料来源</label>
    <input type="text" name="source" placeholder="如：某某家庭相册 / 市档案馆征集">
  </div>
  <div class="form-row">
    <label>简介</label>
    <textarea name="description" placeholder="影像背后的故事……"></textarea>
  </div>
  <button class="btn" type="submit">导入</button>
</form>

<form method="post" enctype="multipart/form-data" class="panel wide mt">
  <?= csrf_field() ?>
  <input type="hidden" name="mode" value="batch">
  <h2 style="margin-bottom:14px;font-size:18px">CSV 批量导入</h2>
  <div class="form-row">
    <label>媒体文件（可多选，文件名需与 CSV 中 file 列一致）</label>
    <input type="file" name="batch_files[]" multiple accept="image/*,video/*">
  </div>
  <div class="form-row">
    <label>CSV 文件（UTF-8 编码）</label>
    <input type="file" name="csv_file" accept=".csv,text/csv" required>
    <div class="hint">
      必需列：file, title, district, year, narrator, license_status；
      可选列：address, description, source, status（published/draft）。
      <a href="import_template.csv" download>下载模板</a>
    </div>
  </div>
  <button class="btn" type="submit">批量导入</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
