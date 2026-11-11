#!/usr/bin/env php
<?php

/**
 * tikobus/webman — project installer.
 *
 * Executed automatically by `composer create-project tikobus/webman [<dir>]`:
 *
 *   1. Downloads the official workerman/webman skeleton into a temp dir.
 *   2. Replaces this thin wrapper's files with the real skeleton.
 *   3. Installs the curated extension set via `composer require`.
 *   4. Generates a project README.md and prints next steps.
 *
 * Environment variables:
 *
 *   WEBMAN_VERSION  Version constraint for the workerman/webman skeleton,
 *                   e.g. `2.2.3` or `^2.2`. Default: `^2.2`.
 */

declare(strict_types=1);

/** Official webman skeleton package. */
const SKELETON_PACKAGE = 'workerman/webman';

/** Default version constraint used for the skeleton. */
const SKELETON_VERSION = '^2.2';

/**
 * Extensions required on top of the skeleton: package => constraint.
 *
 * webman/database is the official ORM integration: it pulls
 * illuminate/database, provides `support\Db` / `support\Model`
 * (Eloquent) and generates `config/database.php`.
 */
const EXTENSIONS = [
    'webman/database'       => '^2.0',
    'illuminate/database'   => '^13.0',
    'illuminate/pagination' => '^13.0',
    'illuminate/events'     => '^13.0',
    'symfony/var-dumper'    => '^7.0 || ^8.0',
    'vlucas/phpdotenv'      => '^5.8',
    'workerman/crontab'     => '^1.0',
    'webman/console'        => '^2.0',
    'webman/redis-queue'    => '^2.0',
    'webman/stomp'          => '^2.0',
    'webman/event'          => '^1.0',
];

/** Wrapper files that must not survive into the created project. */
const WRAPPER_FILES = [
    '.gitattributes',
    '.gitignore',
    'CHANGELOG.md',
    'LICENSE',
    'README.md',
    'composer.json',
    'composer.lock',
    'src',
    'vendor',
];

function out(string $line = ''): void
{
    echo $line, PHP_EOL;
}

function fail(string $message): void
{
    fwrite(STDERR, PHP_EOL . '[tikobus/webman] ERROR: ' . $message . PHP_EOL);
    exit(1);
}

/**
 * Run a shell command with live output; abort the installer on failure.
 *
 * @param string[] $command
 */
function run(array $command): void
{
    $shell = implode(' ', array_map('escapeshellarg', $command));
    out('  $ ' . $shell);

    passthru($shell, $code);

    if ($code !== 0) {
        fail('Command failed with exit code ' . $code . ': ' . $shell);
    }
}

/** Recursively delete a file or directory. */
function rm_rf(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path);

        return;
    }
    if (is_dir($path)) {
        foreach (scandir($path) ?: [] as $item) {
            if ($item !== '.' && $item !== '..') {
                rm_rf($path . DIRECTORY_SEPARATOR . $item);
            }
        }
        rmdir($path);
    }
}

/** Recursively copy a file or directory. */
function copy_rf(string $src, string $dst): void
{
    if (!is_dir($src)) {
        copy($src, $dst);

        return;
    }
    if (!is_dir($dst) && !@mkdir($dst, 0777, true) && !is_dir($dst)) {
        fail('Cannot create directory: ' . $dst);
    }
    foreach (scandir($src) ?: [] as $item) {
        if ($item !== '.' && $item !== '..') {
            copy_rf($src . DIRECTORY_SEPARATOR . $item, $dst . DIRECTORY_SEPARATOR . $item);
        }
    }
}

/**
 * Move every entry of $src (dotfiles included) into $dst, falling back to
 * copy + delete when rename() cannot cross filesystems.
 */
function move_contents(string $src, string $dst): void
{
    foreach (scandir($src) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $from = $src . DIRECTORY_SEPARATOR . $item;
        $to   = $dst . DIRECTORY_SEPARATOR . $item;

        if (!@rename($from, $to)) {
            copy_rf($from, $to);
            rm_rf($from);
        }
    }
}

/** Look up the installed version of a package inside composer.lock. */
function installed_version(string $projectDir, string $package): ?string
{
    $lock = $projectDir . DIRECTORY_SEPARATOR . 'composer.lock';
    if (!is_file($lock)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($lock), true);
    foreach (($data['packages'] ?? []) as $installed) {
        if (($installed['name'] ?? '') === $package) {
            return $installed['version'] ?? null;
        }
    }

    return null;
}

/**
 * Build the README.md written into the created project.
 *
 * @param array<string, string> $versions package => installed version
 */
function build_readme(string $name, string $frameworkVersion, array $versions): string
{
    $rows = '';
    foreach ($versions as $package => $version) {
        $rows .= '| ' . $package . ' | ' . $version . ' |' . PHP_EOL;
    }

    $template = <<<'MD'
# {name}

基于 webman（webman-framework {framework}）的高性能 HTTP 服务项目，
由 `composer create-project tikobus/webman` 一键创建。

## 快速开始

```bash
php start.php start        # 调试模式启动（前台）
php start.php start -d     # 守护进程模式启动
php start.php stop         # 停止
php start.php reload       # 平滑重启
php start.php status       # 查看状态
```

> Windows 环境请运行 `windows.bat`。

## 预装组件

| 组件 | 安装版本 |
| --- | --- |
{rows}
说明：

- `webman/database` 提供 `support\Db` / `support\Model`（Eloquent ORM），连接配置见 `config/database.php`
- `vlucas/phpdotenv` 已安装，框架启动时会自动加载项目根目录的 `.env` 文件
- `symfony/var-dumper` 提供 `dump()` / `dd()` 调试函数

## 常用命令（webman/console）

```bash
php webman make:controller Admin/UserController   # 创建控制器
php webman make:model User                        # 创建模型
php webman version                                # 查看版本
php webman restart / stop / connections           # 服务管理
```

## 定时任务（workerman/crontab）

```php
use Workerman\Crontab\Crontab;

new Crontab('0 * * * *', function () {
    // 每小时执行
});
```

## 消息队列

- `webman/redis-queue`：配置 `config/plugin/webman/redis-queue/`，消费者位于 `app/queue/redis/`
- `webman/stomp`：配置 `config/plugin/webman/stomp/`，消费者位于 `app/queue/stomp/`

```php
use Webman\RedisQueue\Client;

Client::send('queue-name', $data);   // 投递消息
```

## 事件（webman/event）

事件监听配置见 `config/event.php` 与 `config/plugin/webman/event/`。

## 更多组件

```bash
composer setup-webman   # 官方交互式向导：模板引擎、验证器、Redis、think-orm 等
```

## 文档

- webman 手册：https://www.workerman.net/doc/webman/
- workerman 手册：https://www.workerman.net/doc/workerman/

MD;

    return str_replace(
        ['{name}', '{framework}', '{rows}'],
        [$name, $frameworkVersion, $rows],
        $template
    );
}

// ── 0. Environment ──────────────────────────────────────────────────────────

$projectDir  = getcwd() ?: fail('Unable to determine the project directory.');
$projectName = basename($projectDir);
$composer    = trim((string) (getenv('COMPOSER_BINARY') ?: '')) ?: 'composer';

$skeleton   = SKELETON_PACKAGE;
$versionArg = trim((string) (getenv('WEBMAN_VERSION') ?: '')) ?: SKELETON_VERSION;
$skeleton  .= ':' . $versionArg;

out('');
out('tikobus/webman installer');
out('========================');
out('project  : ' . $projectDir);
out('skeleton : ' . $skeleton);
out('');

// ── 1. Fetch the official skeleton into a temp dir ─────────────────────────

out('[1/4] Fetching the official webman skeleton ...');

$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'webman-skeleton-' . bin2hex(random_bytes(4));
run([$composer, 'create-project', $skeleton, $tempDir, '--no-install', '--no-interaction']);

if (!is_file($tempDir . DIRECTORY_SEPARATOR . 'composer.json')) {
    fail('The downloaded skeleton does not contain a composer.json.');
}

// ── 2. Swap the wrapper files for the skeleton ──────────────────────────────

out('[2/4] Placing skeleton files ...');

// NOTE: deleting src/ is safe while this script runs — PHP holds the whole
// file in memory once execution has started.
foreach (WRAPPER_FILES as $file) {
    rm_rf($projectDir . DIRECTORY_SEPARATOR . $file);
}

move_contents($tempDir, $projectDir);
rm_rf($tempDir);

// ── 3. Install the extension set ────────────────────────────────────────────

out('[3/4] Installing extensions (this may take a while) ...');

$requirements = [];
foreach (EXTENSIONS as $package => $constraint) {
    $requirements[] = $package . ':' . $constraint;
}

run(array_merge([$composer, 'require'], $requirements, ['--no-interaction']));

// ── 4. Generate README + report ─────────────────────────────────────────────

out('[4/4] Generating README.md ...');

$versions = [];
foreach (array_keys(EXTENSIONS) as $package) {
    $version = installed_version($projectDir, $package);
    if ($version !== null && $version !== '') {
        $versions[$package] = $version;
    }
}

$frameworkVersion = installed_version($projectDir, 'workerman/webman-framework') ?: 'unknown';
file_put_contents(
    $projectDir . DIRECTORY_SEPARATOR . 'README.md',
    build_readme($projectName, $frameworkVersion, $versions)
);

out('');
out('[OK] Project created successfully.');
out('');
out('Next steps:');
out('    cd ' . $projectName);
out('    php start.php start       # start the server (debug mode)');
out('    php webman version        # show versions');
out('');
out('Docs: https://www.workerman.net/doc/webman/');
out('');
