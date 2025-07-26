<?php

namespace zjkal\WebmanTurnstile;

use zjkal\WebmanTurnstile\Exception\TurnstileException;

/**
 * Cloudflare Turnstile 验证类
 */
class Turnstile
{
    /**
     * 获取配置
     *
     * @return array
     */
    private static function getConfig(): array
    {
        return config('turnstile', []);
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
     * 快速验证 Turnstile token（仅返回布尔值）
     *
     * @param string $token Turnstile 响应 token
     * @param string|null $remoteIp 客户端 IP 地址（可选，不传则自动获取）
     * @return bool 验证是否成功
     * @throws TurnstileException 当密钥未配置、网络请求失败或响应解析失败时抛出
     */
    public static function check(string $token, ?string $remoteIp = null): bool
    {
        $result = self::verify($token, $remoteIp);
        return $result['success'] ?? false;
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
     * 发送 HTTP 请求
     *
     * @param string $url 请求 URL
     * @param array $postData POST 数据
     * @param int $timeout 超时时间
     * @return string 响应内容
     * @throws TurnstileException 当 HTTP 请求失败时抛出
     */
    private static function makeRequest(string $url, array $postData, int $timeout = 30)
    {
        $postFields = http_build_query($postData);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen($postFields),
                    'User-Agent: Webman-Turnstile/1.0'
                ],
                'content' => $postFields,
                'timeout' => $timeout,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            throw new TurnstileException('Turnstile HTTP request failed: ' . ($error['message'] ?? 'Unknown error'), ['network-error']);
        }

        return $response;
    }

    /**
     * 获取错误代码的中文描述
     *
     * @param string $errorCode 错误代码
     * @return string 错误描述
     */
    public static function getErrorMessage(string $errorCode): string
    {
        $messages = [
            'missing-input-secret' => '缺少密钥参数',
            'invalid-input-secret' => '无效的密钥',
            'missing-input-response' => '缺少响应参数',
            'invalid-input-response' => '无效的响应参数',
            'bad-request' => '请求格式错误',
            'timeout-or-duplicate' => '超时或重复提交',
            'internal-error' => '内部错误',
            'service-disabled' => '服务已禁用',
            'missing-secret-key' => '未配置密钥',
            'network-error' => '网络请求失败',
            'invalid-response' => '响应格式错误',
            'invalid-hostname' => '主机名验证失败',
        ];

        return $messages[$errorCode] ?? '未知错误';
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
}