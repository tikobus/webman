<?php

namespace Tikobus\Webman;

class Project
{
    static function Init() {
        $vendor = self::vendorPath();
        $root = dirname($vendor);
        $path = $vendor . '/workerman/webman';
        copy_dir($path, $root . '/webman');
        foreach (glob($root . '/webman/*') as $dir) {
            rename($dir, $root . '/' . basename($dir));
        }
        rename($root . '/webman/.gitignore', $root . '/.gitignore');
        unlink($root . '/webman');
    }

    static function vendorPath() {
        $path = __DIR__;
        for ($i=0; $i < 10; $i++) {
            if (is_dir($path . '/vendor')) {
                return $path . '/vendor';
            }
            $path = dirname($path);
        }
        throw new \Exception("Not Found Vendor !", 1);
    }
}
