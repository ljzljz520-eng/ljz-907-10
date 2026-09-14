<?php
// PHP 内置服务器路由：禁止执行 uploads 下的脚本，仅作静态文件输出
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/uploads/#', $uri)) {
    $file = __DIR__ . $uri;
    if (!is_file($file)) { http_response_code(404); exit('Not Found'); }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, ['php', 'phtml', 'phar'], true)) { http_response_code(403); exit('Forbidden'); }
    $mimes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime', 'm4v' => 'video/mp4',
    ];
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}
return false;
