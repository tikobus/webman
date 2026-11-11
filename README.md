# tikobus/webman

一条命令创建自带常用扩展的 [webman](https://www.workerman.net/doc/webman/) 项目：

```bash
composer create-project tikobus/webman myproject
# 或指定版本
composer create-project tikobus/webman:^2.0 myproject
```

## 它做了什么

`composer create-project` 完成后自动执行 `src/install.php`：

1. 下载官方 `workerman/webman` 骨架（默认 `^2.2`，即 webman 2.x 最新版）
2. 用骨架文件替换本安装器的包装文件
3. `composer require` 安装下列扩展（各插件自动生成配置）
4. 生成项目 `README.md`

如需指定骨架版本：

```bash
WEBMAN_VERSION=2.2.3 composer create-project tikobus/webman myproject
```

## 预装扩展（均为最新版）

| 扩展 | 约束 | 说明 |
| --- | --- | --- |
| webman/database | ^2.0 | 官方 ORM 集成，自动安装 illuminate/database，提供 `support\Db` / `support\Model` |
| illuminate/database | ^13.0 | Eloquent ORM |
| illuminate/pagination | ^13.0 | 分页 |
| illuminate/events | ^13.0 | 事件 |
| symfony/var-dumper | ^7.0 \|\| ^8.0 | `dump()` / `dd()` 调试 |
| vlucas/phpdotenv | ^5.8 | `.env` 环境变量（框架自动加载） |
| workerman/crontab | ^1.0 | 定时任务 |
| webman/console | ^2.0 | 命令行工具（`php webman ...`） |
| webman/redis-queue | ^2.0 | Redis 消息队列 |
| webman/stomp | ^2.0 | Stomp 消息队列 |
| webman/event | ^1.0 | 事件系统插件 |

## 环境要求

- PHP >= 8.3（illuminate 13 要求 ^8.3）
- Composer 2.x
- 不要带 `--no-scripts`（安装器依赖 `post-create-project-cmd` 钩子）

> 需要旧的 webman 1.x + illuminate 10 技术栈时，使用旧版标签：
> `composer create-project tikobus/webman:v1.5.9 project`

## 维护

1. 修改 `src/install.php` 中的 `EXTENSIONS`（扩展清单）或 `SKELETON_VERSION`（骨架默认版本）
2. 提交并打 tag（如 `v2.0.0`），推送后 Packagist 自动同步
