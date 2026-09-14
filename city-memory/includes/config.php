<?php
// 全局配置
define('APP_NAME', '城市记忆影像馆');
define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('UPLOAD_URL', 'uploads');
define('DB_FILE', DATA_PATH . '/city_memory.sqlite');

// 上传限制
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_EXT', ['mp4', 'webm', 'mov', 'm4v']);

// 授权状态选项
define('LICENSE_OPTIONS', ['已授权', '待授权', '仅馆内展示']);
// 线索类型
define('CLUE_TYPES', ['人物辨认', '地点确认', '年代考证', '事件背景', '其他补充']);

session_start();
date_default_timezone_set('Asia/Shanghai');
