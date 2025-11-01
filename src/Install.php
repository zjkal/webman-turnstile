<?php

namespace plugin\zjkal\turnstile;

use Composer\Script\Event;

/**
 * Composer 安装脚本
 */
class Install
{
    /**
     * 安装后执行的脚本
     */
    public static function postInstall(Event $event): void
    {
        self::copyConfigFiles($event);
    }

    /**
     * 更新后执行的脚本
     */
    public static function postUpdate(Event $event): void
    {
        self::copyConfigFiles($event);
    }

    /**
     * 复制配置文件到宿主项目
     */
    private static function copyConfigFiles(Event $event): void
    {
        $composer = $event->getComposer();
        $io = $event->getIO();

        $vendorDir = $composer->getConfig()->get('vendor-dir');
        $vendorDirAbsolute = is_dir($vendorDir) ? realpath($vendorDir) : $vendorDir;
        $projectRoot = dirname($vendorDirAbsolute ?: $vendorDir);

        // 源配置文件路径（插件内）
        $sourceConfig = __DIR__ . '/config/plugin/zjkal/turnstile/app.php';

        // 目标配置文件路径（宿主项目）
        $targetConfigDir = $projectRoot . '/config/plugin/zjkal/turnstile';
        $targetConfig = $targetConfigDir . '/app.php';

        // 创建目标目录
        if (!is_dir($targetConfigDir)) {
            if (!mkdir($targetConfigDir, 0755, true)) {
                $io->writeError('Failed to create config directory: ' . $targetConfigDir);
                return;
            }
        }

        // 复制配置文件（如果不存在）
        if (!file_exists($targetConfig)) {
            if (copy($sourceConfig, $targetConfig)) {
                $io->write('Webman Turnstile plugin config created: ' . $targetConfig);
            } else {
                $io->writeError('Failed to copy config file to: ' . $targetConfig);
            }
        } else {
            $io->write('Webman Turnstile plugin config already exists: ' . $targetConfig);
        }

        // 提示用户配置密钥
        $io->write('');
        $io->write('<info>Webman Turnstile installed successfully!</info>');
        $io->write('<comment>Please configure your Turnstile secret key in:</comment>');
        $io->write('<comment>' . $targetConfig . '</comment>');
        $io->write('');
    }
}