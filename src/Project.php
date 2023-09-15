<?php

namespace Tikobus\Webman;

class Project
{
    static function InitApp() {
        $vendor = self::vendorPath();
        $root = dirname($vendor);
        $path = $vendor . '/workerman/webman';
        self::copyDir($path, $root . '/webman');
        foreach (glob($root . '/webman/*') as $dir) {
            rename($dir, $root . '/' . basename($dir));
        }
        rename($root . '/webman/.gitignore', $root . '/.gitignore');
        rmdir($root . '/webman');
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
}
