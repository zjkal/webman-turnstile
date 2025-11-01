<?php

namespace plugin\zjkal\turnstile;



/**
 * Composer 安装脚本
 */
class Install
{
    /**
     * 安装（复制配置文件）
     */
    public static function install(): void
    {
        self::installConfigFiles();
    }

    /**
     * 卸载（删除配置文件）
     */
    public static function uninstall(): void
    {
        self::uninstallConfigFiles();
    }

    /**
     * 安装：复制配置文件到宿主项目
     */
    private static function installConfigFiles(): void
    {
        // 自动检测项目根路径：vendor 在插件发布后位于 {project}/vendor
        $vendorDir = dirname(__DIR__, 2) . '/vendor';
        $projectRoot = is_dir($vendorDir) ? dirname(realpath($vendorDir) ?: $vendorDir) : dirname(__DIR__, 2);

        // 源配置文件路径（插件内）
        $sourceConfig = __DIR__ . '/config/plugin/zjkal/turnstile/app.php';
        if (!is_file($sourceConfig) || !is_readable($sourceConfig)) {
            echo "[turnstile] Source config not found or unreadable: {$sourceConfig}\n";
            return;
        }

        // 目标配置文件路径（宿主项目）
        $targetConfigDir = $projectRoot . '/config/plugin/zjkal/turnstile';
        $targetConfig = $targetConfigDir . '/app.php';

        // 创建目标目录
        if (!is_dir($targetConfigDir)) {
            if (!@mkdir($targetConfigDir, 0755, true) && !is_dir($targetConfigDir)) {
                echo "[turnstile] Failed to create config directory: {$targetConfigDir}\n";
                return;
            }
            echo "[turnstile] Created config directory: {$targetConfigDir}\n";
        }

        // 复制配置文件（如果不存在）
        if (!file_exists($targetConfig)) {
            if (@copy($sourceConfig, $targetConfig)) {
                echo "[turnstile] Created plugin config: {$targetConfig}\n";
            } else {
                echo "[turnstile] Failed to copy config file to: {$targetConfig}\n";
                return;
            }
        } else {
            echo "[turnstile] Config already exists, skip: {$targetConfig}\n";
        }

        // 提示用户配置密钥与后续操作
        echo "[turnstile] Installed successfully. Please set your secret key in: {$targetConfig}\n";
        echo "[turnstile] If you need to reinitialize, delete the file above and rerun composer install/update.\n";
    }

    /**
     * 卸载：删除宿主项目中的配置文件
     */
    private static function uninstallConfigFiles(): void
    {
        // 自动检测项目根路径
        $vendorDir = dirname(__DIR__, 2) . '/vendor';
        $projectRoot = is_dir($vendorDir) ? dirname(realpath($vendorDir) ?: $vendorDir) : dirname(__DIR__, 2);

        // 目标配置文件路径（宿主项目）
        $targetConfigDir = $projectRoot . '/config/plugin/zjkal/turnstile';
        $targetConfig = $targetConfigDir . '/app.php';

        if (file_exists($targetConfig)) {
            if (@unlink($targetConfig)) {
                echo "[turnstile] Removed plugin config: {$targetConfig}\n";
            } else {
                echo "[turnstile] Failed to remove config file: {$targetConfig}\n";
            }
        } else {
            echo "[turnstile] No plugin config found to remove: {$targetConfig}\n";
        }

        // 如果目录为空则尝试删除目录
        if (is_dir($targetConfigDir)) {
            $files = scandir($targetConfigDir);
            if (is_array($files) && count(array_diff($files, ['.', '..'])) === 0) {
                @rmdir($targetConfigDir);
                echo "[turnstile] Removed empty config directory: {$targetConfigDir}\n";
            }
        }

        echo "[turnstile] Uninstalled (config cleaned).\n";
    }
}