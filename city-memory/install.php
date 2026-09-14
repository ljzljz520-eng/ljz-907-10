<?php
// 安装/初始化脚本：php install.php
require_once __DIR__ . '/includes/helpers.php';

if (!is_dir(DATA_PATH)) mkdir(DATA_PATH, 0775, true);
if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0775, true);

db_init();
$db = db();

// 已初始化则跳过（除非加 --force）
$hasAdmin = $db->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch()['c'] ?? 0;
if ($hasAdmin && !in_array('--force', $argv ?? [])) {
    echo "数据库已初始化。如需重置请删除 data/city_memory.sqlite 后重跑。\n";
    exit(0);
}

// ---- 账号 ----
$insUser = $db->prepare('INSERT OR IGNORE INTO users (username, password_hash, display_name, role) VALUES (?,?,?,?)');
$insUser->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), '馆方管理员', 'admin']);
$insUser->execute(['chenmo', password_hash('user123', PASSWORD_DEFAULT), '陈默', 'user']);
$insUser->execute(['linyi', password_hash('user123', PASSWORD_DEFAULT), '林一', 'user']);
echo "账号已创建：admin/admin123（管理员），chenmo/user123、linyi/user123（访客）\n";

// ---- 生成仿真老照片（GD）----
function make_photo(string $file, string $caption, int $seed): void {
    mt_srand($seed);
    $w = 800; $h = 560;
    $img = imagecreatetruecolor($w, $h);
    // 泛黄的纸底色
    $base = imagecolorallocate($img, 205 + mt_rand(-10, 10), 190 + mt_rand(-10, 10), 158 + mt_rand(-10, 10));
    imagefill($img, 0, 0, $base);
    // 随机"建筑"色块
    for ($i = 0; $i < 7; $i++) {
        $shade = 90 + mt_rand(0, 80);
        $c = imagecolorallocate($img, $shade, (int)($shade * 0.92), (int)($shade * 0.75));
        $x = mt_rand(0, $w - 160); $bw = mt_rand(80, 180); $bh = mt_rand(140, 320);
        imagefilledrectangle($img, $x, $h - $bh - 90, $x + $bw, $h - 90, $c);
        // 窗户
        for ($wy = $h - $bh - 70; $wy < $h - 130; $wy += 34) {
            for ($wx = $x + 12; $wx < $x + $bw - 18; $wx += 30) {
                $wc = imagecolorallocate($img, 220, 210, 180);
                imagefilledrectangle($img, $wx, $wy, $wx + 14, $wy + 20, $wc);
            }
        }
    }
    // 街道
    $road = imagecolorallocate($img, 120, 112, 96);
    imagefilledrectangle($img, 0, $h - 90, $w, $h, $road);
    // 噪点做旧
    for ($i = 0; $i < 9000; $i++) {
        $n = 100 + mt_rand(0, 120);
        $nc = imagecolorallocate($img, $n, (int)($n * 0.94), (int)($n * 0.8));
        imagesetpixel($img, mt_rand(0, $w - 1), mt_rand(0, $h - 1), $nc);
    }
    // 划痕
    for ($i = 0; $i < 5; $i++) {
        $lc = imagecolorallocatealpha($img, 240, 235, 220, 90);
        $x = mt_rand(0, $w);
        imageline($img, $x, 0, $x + mt_rand(-30, 30), $h, $lc);
    }
    // 白边框
    $border = imagecolorallocate($img, 244, 240, 228);
    imagefilledrectangle($img, 0, 0, $w, 14, $border);
    imagefilledrectangle($img, 0, $h - 14, $w, $h, $border);
    imagefilledrectangle($img, 0, 0, 14, $h, $border);
    imagefilledrectangle($img, $w - 14, 0, $w, $h, $border);
    // 文字（内置字体仅支持 ASCII，写年份）
    $tc = imagecolorallocate($img, 70, 58, 44);
    imagestring($img, 5, 24, $h - 40, $caption, $tc);
    imagepng($img, UPLOAD_PATH . '/' . $file);
    imagedestroy($img);
}

// ---- 演示媒体 ----
$samples = [
    ['南门大街的自行车流', 'photo', '南门街区', '南门大街与府前路交叉口', 1982, '王秀兰', '已授权', '上班高峰期的自行车流，街角是当年的国营副食品商店。', '王秀兰家庭相册'],
    ['渡口老钟楼', 'photo', '渡口街区', '临江路渡口广场', 1975, '李长河', '已授权', '钟楼每到整点敲响，渡船工人以此对表。', '市档案馆征集'],
    ['纺织厂大门', 'photo', '纺织厂区', '建设东路国棉三厂正门', 1988, '张建国', '待授权', '厂庆三十周年时拍摄，门口挂着红灯笼。', '张建国提供'],
    ['火车站台送别', 'photo', '车站街区', '老火车站二号站台', 1991, '陈阿娣', '已授权', '春运站台，绿皮车即将发车。', '市民征集活动'],
    ['河埠头洗衣场景', 'photo', '渡口街区', '西河沿埠头', 1968, '周小妹', '仅馆内展示', '清晨河埠头，街坊们边洗衣边拉家常。', '周小妹口述历史项目'],
    ['人民电影院门前', 'photo', '南门街区', '人民路电影院', 1979, '王秀兰', '已授权', '《小花》上映时的排队场景。', '王秀兰家庭相册'],
    ['厂区运动会录像', 'video', '纺织厂区', '国棉三厂灯光球场', 1986, '张建国', '待授权', '职工运动会拔河决赛片段。', '厂宣传科留档'],
    ['骑楼下的早点摊', 'photo', '车站街区', '解放路骑楼', 1994, '林伯', '已授权', '豆浆、油条、粢饭糕，五毛钱管饱。', '林伯收藏'],
];

$ins = $db->prepare('INSERT INTO media (title, type, file_path, district, address, year, decade, narrator, license_status, description, source, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)');
foreach ($samples as $i => $s) {
    [$title, $type, $district, $addr, $year, $narrator, $lic, $desc, $src] = $s;
    if ($type === 'photo') {
        $file = 'seed_' . ($i + 1) . '.png';
        make_photo($file, (string)$year, $i * 77 + 13);
    } else {
        // 演示视频：放占位文件（实际导入时上传真实视频）
        $file = 'seed_' . ($i + 1) . '.mp4';
        if (!file_exists(UPLOAD_PATH . '/' . $file)) file_put_contents(UPLOAD_PATH . '/' . $file, '');
    }
    $ins->execute([$title, $type, $file, $district, $addr, $year, decade_of($year), $narrator, $lic, $desc, $src, 'published']);
}
echo "演示媒体已导入：" . count($samples) . " 条\n";

// ---- 演示留言与线索 ----
$chenmo = $db->query("SELECT id FROM users WHERE username='chenmo'")->fetchColumn();
$linyi  = $db->query("SELECT id FROM users WHERE username='linyi'")->fetchColumn();

$db->prepare('INSERT INTO comments (media_id, user_id, content, status) VALUES (?,?,?,?)')
   ->execute([1, $chenmo, '我小时候就在这家副食品店门口买冰棍，三分钱一根！', 'approved']);
$db->prepare('INSERT INTO comments (media_id, user_id, content, status) VALUES (?,?,?,?)')
   ->execute([2, $linyi, '钟楼的钟声现在还能听到吗？', 'approved']);
$db->prepare('INSERT INTO comments (media_id, user_id, content, status) VALUES (?,?,?,?)')
   ->execute([1, $linyi, '这张照片让我想起了外公。', 'pending']);

$db->prepare('INSERT INTO clues (media_id, user_id, clue_type, content, status) VALUES (?,?,?,?,?)')
   ->execute([1, $chenmo, '地点确认', '画面左侧的转角楼应该是原府前路 14 号的五金交电门市部，1996 年拆除。', 'approved']);
$db->prepare('INSERT INTO clues (media_id, user_id, clue_type, content, status) VALUES (?,?,?,?,?)')
   ->execute([2, $linyi, '人物辨认', '右二撑伞的男子很像我的祖父李守财，他当年是渡口的售票员。', 'pending']);

echo "演示留言与线索已写入。\n安装完成！\n";
