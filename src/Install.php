<?php

namespace zjkal\WebmanTurnstile;

use Composer\Script\Event;
use Composer\IO\IOInterface;

/**
 * Composer 安装脚本
 */
class Install
{
    /**
     * 安装后执行的脚本
     *
     * @param Event $event Composer 事件
     */
    public static function postInstall(Event $event): void
    {
        self::copyConfigFiles($event->getIO());
    }

    /**
     * 更新后执行的脚本
     *
     * @param Event $event Composer 事件
     */
    public static function postUpdate(Event $event): void
    {
        self::copyConfigFiles($event->getIO());
    }

    /**
     * 复制配置文件
     *
     * @param IOInterface $io IO 接口
     */
    private static function copyConfigFiles(IOInterface $io): void
    {
        $vendorDir = dirname(dirname(__DIR__));
        $projectRoot = dirname($vendorDir);
        
        // 源配置文件路径
        $sourceConfig = __DIR__ . '/../config/turnstile.php';
        
        // 目标配置文件路径
        $targetConfigDir = $projectRoot . '/config';
        $targetConfig = $targetConfigDir . '/turnstile.php';

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
                $io->write('Webman Turnstile config file created: ' . $targetConfig);
            } else {
                $io->writeError('Failed to copy config file to: ' . $targetConfig);
            }
        } else {
            $io->write('Webman Turnstile config file already exists: ' . $targetConfig);
        }

        // 提示用户配置密钥
        $io->write('');
        $io->write('<info>Webman Turnstile installed successfully!</info>');
        $io->write('<comment>Please configure your Turnstile secret key in:</comment>');
        $io->write('<comment>' . $targetConfig . '</comment>');
        $io->write('');
    }
}