#!/bin/bash
# 城市记忆影像馆 启动脚本
cd "$(dirname "$0")"
PHP_BIN=${PHP_BIN:-php}
if [ ! -f data/city_memory.sqlite ]; then
    echo "首次运行，初始化数据库..."
    $PHP_BIN install.php
fi
echo "启动服务：http://127.0.0.1:8080"
echo "默认账号：admin/admin123（管理员）  chenmo/user123（访客）"
exec $PHP_BIN -S 0.0.0.0:8080 router.php
