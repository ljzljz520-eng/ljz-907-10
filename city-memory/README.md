# 城市记忆影像馆

一个用 PHP + SQLite 构建的城市老照片/旧影像征集与展示系统。管理员导入影像资料并维护元数据，访客按街区或年代浏览，登录用户可提交补充线索与留言，经后台审核后公开。

## 功能

### 前台
- **片库**（`index.php`）：按街区、年代、类型（照片/影像）筛选，关键词搜索，分页浏览
- **详情**（`detail.php`）：查看影像、拍摄地点、年代、讲述人、授权状态、资料来源；浏览已公开的线索与留言；登录后提交留言
- **补充线索**（`clue.php`）：登录用户为影像提交线索（人物辨认/地点确认/年代考证/事件背景/其他），审核后公开
- **账号**：注册、登录、退出

### 后台（管理员）
- **概览**（`admin/index.php`）：馆藏与待审核统计、最新导入
- **导入影像**（`admin/import.php`）：
  - 单条导入：上传照片/视频 + 标题、街区、拍摄地点、年份、讲述人、授权状态、来源、简介
  - CSV 批量导入：多选媒体文件 + CSV 元数据（模板可下载），按文件名匹配
- **影像管理**（`admin/media.php`）：编辑元数据、公开/下架、删除（级联删除关联线索与留言）
- **审核中心**（`admin/review.php`）：线索与留言的通过/驳回，通过后前台公开

## 快速开始

```bash
# 需要 PHP 8+（含 pdo_sqlite、gd、mbstring、fileinfo 扩展）
./start.sh
# 或手动：
php install.php          # 初始化数据库与演示数据
php -S 0.0.0.0:8080 router.php
```

访问 http://127.0.0.1:8080

**默认账号**

| 账号 | 密码 | 角色 |
|---|---|---|
| admin | admin123 | 管理员 |
| chenmo | user123 | 注册用户 |
| linyi | user123 | 注册用户 |

## 目录结构

```
├── index.php            # 前台片库（街区/年代筛选）
├── detail.php           # 影像详情 + 留言
├── clue.php             # 提交补充线索（需登录）
├── login.php / register.php / logout.php
├── install.php          # 初始化数据库 + 演示数据
├── router.php           # 内置服务器路由（保护 uploads）
├── start.sh             # 一键启动
├── includes/            # 配置、数据库、鉴权、工具、布局
├── admin/               # 后台：概览/导入/影像管理/审核中心
├── assets/style.css
├── uploads/             # 上传的影像文件
└── data/                # SQLite 数据库
```

## 数据模型

- **users**：用户名、密码哈希、昵称、角色（admin/user）
- **media**：标题、类型（photo/video）、文件、街区、拍摄地点、年份/年代、讲述人、授权状态（已授权/待授权/仅馆内展示）、简介、来源、公开状态
- **clues**：补充线索，状态流转 pending → approved/rejected，审核后公开
- **comments**：留言，同样需要审核

## 安全说明

- 密码使用 `password_hash` 存储；登录后重建会话 ID
- 全部 SQL 使用 PDO 预处理语句
- 输出统一 `htmlspecialchars` 转义，防 XSS
- 表单携带 CSRF Token 校验
- 上传校验扩展名 + MIME（fileinfo），随机重命名；uploads 目录禁止脚本执行（router + .htaccess）
- 后台接口强制管理员鉴权

## CSV 批量导入格式

UTF-8 编码，必需列：`file, title, district, year, narrator, license_status`
可选列：`address, description, source, status`（published/draft）

`file` 列为媒体文件名，需与同时上传的文件名一致。模板见 `admin/import_template.csv`。
