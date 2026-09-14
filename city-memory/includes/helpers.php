<?php
require_once __DIR__ . '/db.php';

// ---------- 输出安全 ----------
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_check(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) {
        http_response_code(403);
        exit('请求校验失败，请返回重试');
    }
}

// ---------- 跳转与闪存消息 ----------
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}
function flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- 业务工具 ----------
function decade_of(?int $year): ?int {
    if (!$year || $year < 1800 || $year > 2100) return null;
    return intdiv($year, 10) * 10;
}

function media_type_label(string $t): string {
    return $t === 'video' ? '影像' : '照片';
}

function status_label(string $s): string {
    return ['pending' => '待审核', 'approved' => '已通过', 'rejected' => '已驳回'][$s] ?? $s;
}

// 上传文件处理，返回 [相对路径, 类型] 或抛出异常
function handle_upload(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('文件上传失败（错误码 ' . ($file['error'] ?? '?') . '）');
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('文件超过大小限制（50MB）');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (in_array($ext, ALLOWED_IMAGE_EXT, true) && str_starts_with($mime, 'image/')) {
        $type = 'photo';
    } elseif (in_array($ext, ALLOWED_VIDEO_EXT, true) && (str_starts_with($mime, 'video/') || $mime === 'application/octet-stream')) {
        $type = 'video';
    } else {
        throw new RuntimeException('不支持的文件类型：' . e($ext));
    }

    $name = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_PATH . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('保存文件失败');
    }
    return [$name, $type];
}

// 分页
function paginate(int $total, int $page, int $perPage): array {
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    return [$page, $pages, ($page - 1) * $perPage];
}
