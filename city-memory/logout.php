<?php
require_once __DIR__ . '/includes/auth.php';
logout();
session_start();
flash('ok', '已退出登录');
redirect('index.php');
