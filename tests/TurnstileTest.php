<?php

namespace zjkal\WebmanTurnstile\Tests;

use PHPUnit\Framework\TestCase;
use zjkal\WebmanTurnstile\Turnstile;

/**
 * Turnstile 基础测试
 */
class TurnstileTest extends TestCase
{
    /**
     * 测试错误消息获取
     */
    public function testGetErrorMessage(): void
    {
        $this->assertEquals('缺少密钥参数', Turnstile::getErrorMessage('missing-input-secret'));
        $this->assertEquals('无效的密钥', Turnstile::getErrorMessage('invalid-input-secret'));
        $this->assertEquals('未知错误', Turnstile::getErrorMessage('unknown-error'));
    }

    /**
     * 测试多个错误消息获取
     */
    public function testGetErrorMessages(): void
    {
        $errorCodes = ['missing-input-secret', 'invalid-input-response'];
        $expected = ['缺少密钥参数', '无效的响应参数'];
        
        $this->assertEquals($expected, Turnstile::getErrorMessages($errorCodes));
    }

    /**
     * 测试空 token 验证
     */
    public function testVerifyWithEmptyToken(): void
    {
        // 模拟配置
        if (!function_exists('config')) {
            function config($key, $default = null) {
                if ($key === 'turnstile') {
                    return [
                        'enable' => true,
                        'secret_key' => 'test-secret-key',
                        'timeout' => 30,
                        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                        'verify_hostname' => false,
                        'allowed_hostnames' => [],
                    ];
                }
                return $default;
            }
        }

        $result = Turnstile::verify('');
        
        $this->assertFalse($result['success']);
        $this->assertContains('missing-input-response', $result['error-codes']);
    }

    /**
     * 测试快速验证方法
     */
    public function testCheck(): void
    {
        $result = Turnstile::check('');
        $this->assertFalse($result);
    }
}