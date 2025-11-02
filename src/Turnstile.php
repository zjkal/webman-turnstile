<?php

namespace zjkal\turnstile;

use zjkal\turnstile\Exception\TurnstileException;

/**
 * Cloudflare Turnstile 验证类
 */
class Turnstile
{
    // 允许测试或外部注入配置覆盖
    private static ?array $configOverride = null;

    public static function setConfig(array $config): void
    {
        self::$configOverride = $config;
    }

    public static function clearConfig(): void
    {
        self::$configOverride = null;
    }

    /**
     * 获取配置
     *
     * @return array
     */
    private static function getConfig(): array
    {
        // 默认配置（尽量保持与插件配置一致）
        $defaults = [
            'enable' => true,
            'secret_key' => '',
            'timeout' => 30,
            'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            'verify_hostname' => false,
            'allowed_hostnames' => [],
        ];

        // 测试或外部注入的配置覆盖优先
        if (is_array(self::$configOverride)) {
            return array_merge($defaults, self::$configOverride);
        }

        // 优先使用 Webman 的全局 config 函数
        try {
            $cfg = \config('plugin.zjkal.turnstile.app', []);
            if (is_array($cfg)) {
                return array_merge($defaults, $cfg);
            }
        } catch (\Throwable $e) {
            // 测试环境可能不存在全局 config，回退到本地文件
        }
    
        // 回退：尝试加载宿主项目或插件内的配置文件
        $paths = [
            dirname(__DIR__) . '/config/plugin/zjkal/turnstile/app.php',
            __DIR__ . '/config/plugin/zjkal/turnstile/app.php',
        ];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $cfg = include $path;
                if (is_array($cfg)) {
                    return array_merge($defaults, $cfg);
                }
            }
        }
    
        // 默认配置
        return $defaults;
    }

    /**
     * 验证 Turnstile token
     *
     * @param string $token Turnstile 响应 token
     * @param string|null $remoteIp 客户端 IP 地址（可选，不传则自动获取）
     * @return array 验证结果
     * @throws TurnstileException 当密钥未配置、网络请求失败或响应解析失败时抛出
     */
    public static function verify(string $token, ?string $remoteIp = null): array
    {
        $config = self::getConfig();
        
        if (!$config['enable']) {
            return [
                'success' => false,
                'error-codes' => ['service-disabled']
            ];
        }

        if (empty($config['secret_key'])) {
            throw new TurnstileException('Turnstile secret key is not configured', ['missing-secret-key']);
        }

        if (empty($token)) {
            return [
                'success' => false,
                'error-codes' => ['missing-input-response']
            ];
        }

        $postData = [
            'secret' => $config['secret_key'],
            'response' => $token,
        ];

        // 如果没有传入 IP，则自动从 request() 获取
        if ($remoteIp === null) {
            try {
                $request = request();
                if ($request) {
                    $remoteIp = $request->getRealIp();
                }
            } catch (\Throwable $e) {
                // 如果获取失败，静默处理，不影响验证流程
            }
        }

        if ($remoteIp) {
            $postData['remoteip'] = $remoteIp;
        }

        $response = self::makeRequest($config['verify_url'], $postData, $config['timeout']);
        
        if ($response === false) {
            throw new TurnstileException('Failed to verify Turnstile token: HTTP request failed', ['network-error']);
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TurnstileException('Failed to parse Turnstile response: ' . json_last_error_msg(), ['invalid-response']);
        }

        // 验证主机名（如果启用）
        if ($config['verify_hostname'] && $result['success']) {
            if (!self::verifyHostname($result['hostname'] ?? '', $config['allowed_hostnames'])) {
                $result['success'] = false;
                $result['error-codes'] = array_merge($result['error-codes'] ?? [], ['invalid-hostname']);
            }
        }

        return $result;
    }

    /**
     * 发起 HTTP 请求
     *
     * @param string $url 请求地址
     * @param array $postData POST 数据
     * @param int $timeout 超时时间（秒）
     * @return string|false 响应内容或失败返回 false
     */
    private static function makeRequest(string $url, array $postData, int $timeout)
    {
        // 尝试使用 cURL（优先）
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response !== false) {
                return $response;
            }
            
            // 如果 cURL 失败但没有错误信息，尝试使用 file_get_contents
            if (empty($error)) {
                // 继续尝试 file_get_contents
            } else {
                error_log("Turnstile cURL error: " . $error);
                return false;
            }
        }
        
        // 备选方案：使用 file_get_contents
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($postData),
                'timeout' => $timeout,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];

        $context = stream_context_create($options);
        
        // 错误处理
        $previousErrorReporting = error_reporting(0);
        $response = file_get_contents($url, false, $context);
        error_reporting($previousErrorReporting);

        if ($response === FALSE) {
            error_log("Turnstile file_get_contents error: Unable to connect to $url");
            return false;
        }

        return $response;
    }

    /**
     * 验证主机名
     *
     * @param string $hostname 要验证的主机名
     * @param array $allowedHostnames 允许的主机名列表
     * @return bool
     */
    private static function verifyHostname(string $hostname, array $allowedHostnames): bool
    {
        if (empty($allowedHostnames)) {
            return true;
        }

        return in_array($hostname, $allowedHostnames, true);
    }

    /**
     * 获取错误代码的中文描述
     *
     * @param string $code 错误代码
     * @return string 错误描述
     */
    public static function getErrorMessage(string $code): string
    {
        $messages = [
            'missing-input-secret' => '缺少密钥参数',
            'invalid-input-secret' => '无效的密钥',
            'missing-input-response' => '缺少响应参数',
            'invalid-input-response' => '无效的响应参数',
            'bad-request' => '请求格式错误',
            'timeout-or-duplicate' => '超时或重复提交',
            'internal-error' => '内部错误',
            'service-disabled' => '验证服务未启用',
            'invalid-response' => '响应解析失败',
            'network-error' => '网络请求失败',
            'invalid-hostname' => '主机名不在允许列表'
        ];

        return $messages[$code] ?? '未知错误';
    }

    /**
     * 获取多个错误代码的中文描述
     *
     * @param array $errorCodes 错误代码数组
     * @return array 错误描述数组
     */
    public static function getErrorMessages(array $errorCodes): array
    {
        return array_map([self::class, 'getErrorMessage'], $errorCodes);
    }

    public static function check(string $token, ?string $remoteIp = null): bool
    {
        try {
            $result = self::verify($token, $remoteIp);
            return $result['success'] === true;
        } catch (TurnstileException $e) {
            return false;
        }
    }
}