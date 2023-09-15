<?php

namespace Tikobus\Webman;

class Project
{
    static function InitApp() {
        $vendor = self::vendorPath();
        $root = dirname($vendor);
        $path = $vendor . '/workerman/webman';
        if (file_exists($root . '/start.php')) {
            return;
        }
        self::copyDir($path, $root . '/webman');
        foreach (glob($root . '/webman/*') as $dir) {
            if (!file_exists($root . '/' . basename($dir))) {
                rename($dir, $root . '/' . basename($dir));
            }
        }
        rename($root . '/webman/.gitignore', $root . '/.gitignore');
        self::removeDir($root . '/webman');
    }

    static function vendorPath() {
        $path = __DIR__;
        for ($i=0; $i < 10; $i++) {
            if (is_dir($path . '/vendor')) {
                return $path . '/vendor';
            }
            $path = dirname($path);
            echo $path, "\n";
        }
        throw new \Exception("Not Found Vendor !", 1);
    }

    static function copyDir(string $source, string $dest, bool $overwrite = false)
    {
        if (\is_dir($source)) {
            if (!is_dir($dest)) {
                \mkdir($dest);
            }
            $files = \scandir($source);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    self::copyDir("$source/$file", "$dest/$file");
                }
            }
        } else if (\file_exists($source) && ($overwrite || !\file_exists($dest))) {
            \copy($source, $dest);
        }
    }

    static function removeDir(string $dir): bool
    {
        if (\is_link($dir) || \is_file($dir)) {
            return \unlink($dir);
        }
        $files = \array_diff(\scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (\is_dir("$dir/$file") && !\is_link($dir)) ? self::removeDir("$dir/$file") : \unlink("$dir/$file");
        }
        return \rmdir($dir);
    }
}
