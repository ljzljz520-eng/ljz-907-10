<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
    }
    return $pdo;
}

function db_init(): void {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    display_name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    type TEXT NOT NULL DEFAULT 'photo',          -- photo | video
    file_path TEXT NOT NULL,
    district TEXT NOT NULL,                       -- 街区
    address TEXT DEFAULT '',                      -- 拍摄地点
    year INTEGER,                                 -- 拍摄年份
    decade INTEGER,                               -- 年代(如 1980)
    narrator TEXT DEFAULT '',                     -- 讲述人
    license_status TEXT NOT NULL DEFAULT '待授权', -- 授权状态
    description TEXT DEFAULT '',
    source TEXT DEFAULT '',                       -- 资料来源
    status TEXT NOT NULL DEFAULT 'published',     -- published | draft
    views INTEGER NOT NULL DEFAULT 0,
    created_by INTEGER,
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_media_district ON media(district);
CREATE INDEX IF NOT EXISTS idx_media_decade ON media(decade);
CREATE INDEX IF NOT EXISTS idx_media_status ON media(status);

CREATE TABLE IF NOT EXISTS clues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    media_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    clue_type TEXT NOT NULL DEFAULT '其他补充',
    content TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',       -- pending | approved | rejected
    admin_note TEXT DEFAULT '',
    reviewed_by INTEGER,
    reviewed_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_clues_status ON clues(status);
CREATE INDEX IF NOT EXISTS idx_clues_media ON clues(media_id);

CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    media_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',       -- pending | approved | rejected
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_comments_status ON comments(status);
CREATE INDEX IF NOT EXISTS idx_comments_media ON comments(media_id);
SQL;
    db()->exec($sql);
}
