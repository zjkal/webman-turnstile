<?php

namespace {
    // 定义全局 config 函数用于测试
    if (!function_exists('config')) {
        function config($key, $default = null) {
            static $testConfig = [
                'plugin.zjkal.turnstile.app' => [
                    'enable' => true,
                    'secret_key' => 'test-secret-key',
                    'timeout' => 30,
                    'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    'verify_hostname' => false,
                    'allowed_hostnames' => [],
                ]
            ];
            
            return $testConfig[$key] ?? $default;
        }
    }
}

namespace zjkal\turnstile\Tests {
    use PHPUnit\Framework\TestCase;
    use zjkal\turnstile\Turnstile;

    class TurnstileTest extends TestCase
    {
        protected function setUp(): void
        {
            Turnstile::setConfig([
                'enable' => true,
                'secret_key' => 'test-secret-key',
                'timeout' => 5,
            ]);
        }

        protected function tearDown(): void
        {
            Turnstile::clearConfig();
        }

        public function testVerifyMissingToken()
        {
            $result = Turnstile::verify('');
            $this->assertFalse($result['success']);
            $this->assertContains('missing-input-response', $result['error-codes']);
        }

        public function testCheckReturnsBoolean()
        {
            $this->assertFalse(Turnstile::check(''));
        }
    }
}