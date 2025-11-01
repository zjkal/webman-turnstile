<?php

namespace plugin\zjkal\turnstile;

use plugin\zjkal\turnstile\Exception\TurnstileException;

/**
 * Turnstile 助手类
 */
class TurnstileHelper
{
    /**
     * 从请求中验证 Turnstile
     *
     * @param \Webman\Http\Request $request 请求对象
     * @param string $fieldName Turnstile 字段名，默认为 'cf-turnstile-response'
     * @return array 验证结果
     */
    public static function verifyFromRequest($request, string $fieldName = 'cf-turnstile-response'): array
    {
        $token = $request->post($fieldName);
        
        return Turnstile::verify($token);
    }

    /**
     * 从请求中快速验证 Turnstile
     *
     * @param \Webman\Http\Request $request 请求对象
     * @param string $fieldName Turnstile 字段名，默认为 'cf-turnstile-response'
     * @return bool 验证是否成功
     */
    public static function checkFromRequest($request, string $fieldName = 'cf-turnstile-response'): bool
    {
        $token = $request->post($fieldName);
        
        return Turnstile::check($token);
    }

    /**
     * 从请求中验证 Turnstile，失败时抛出异常
     *
     * @param \Webman\Http\Request $request 请求对象
     * @param string $fieldName Turnstile 字段名，默认为 'cf-turnstile-response'
     * @return array 验证结果
     * @throws TurnstileException 验证失败时抛出异常
     */
    public static function verifyFromRequestOrFail($request, string $fieldName = 'cf-turnstile-response'): array
    {
        $result = self::verifyFromRequest($request, $fieldName);
        
        if (!$result['success']) {
            $errorCodes = $result['error-codes'] ?? [];
            $errorMessages = Turnstile::getErrorMessages($errorCodes);
            $message = 'Turnstile 验证失败: ' . implode(', ', $errorMessages);
            
            throw new TurnstileException($message, $errorCodes);
        }
        
        return $result;
    }

    /**
     * 生成 Turnstile HTML 代码
     *
     * @param string $siteKey 站点密钥
     * @param array $options 选项
     * @return string HTML 代码
     */
    public static function generateHtml(string $siteKey, array $options = []): string
    {
        $attributes = [
            'class' => 'cf-turnstile',
            'data-sitekey' => $siteKey,
        ];

        // 合并自定义选项
        foreach ($options as $key => $value) {
            if (strpos($key, 'data-') === 0) {
                $attributes[$key] = $value;
            } else {
                $attributes['data-' . $key] = $value;
            }
        }

        $attributeString = '';
        foreach ($attributes as $key => $value) {
            $attributeString .= sprintf(' %s="%s"', $key, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }

        return sprintf('<div%s></div>', $attributeString);
    }

    /**
     * 生成 Turnstile JavaScript 代码
     *
     * @param array $options 选项
     * @return string JavaScript 代码
     */
    public static function generateScript(array $options = []): string
    {
        $src = $options['src'] ?? 'https://challenges.cloudflare.com/turnstile/v0/api.js';
        $async = isset($options['async']) && $options['async'] ? ' async' : ' async';
        $defer = isset($options['defer']) && $options['defer'] ? ' defer' : ' defer';
        
        return sprintf('<script src="%s"%s%s></script>', $src, $async, $defer);
    }

    /**
     * 生成完整的 Turnstile 表单代码
     *
     * @param string $siteKey 站点密钥
     * @param array $turnstileOptions Turnstile 选项
     * @param array $scriptOptions 脚本选项
     * @return string 完整的 HTML 代码
     */
    public static function generateForm(string $siteKey, array $turnstileOptions = [], array $scriptOptions = []): string
    {
        $script = self::generateScript($scriptOptions);
        $turnstile = self::generateHtml($siteKey, $turnstileOptions);
        
        return $script . "\n" . $turnstile;
    }

    /**
     * 验证配置是否正确
     *
     * @return array 验证结果
     */
    public static function validateConfig(): array
    {
        $config = config('plugin.zjkal.turnstile.app', []);
        $errors = [];

        if (!$config['enable']) {
            $errors[] = 'Turnstile 服务未启用';
        }

        if (empty($config['secret_key'])) {
            $errors[] = '未配置 Turnstile 密钥';
        }

        if (!filter_var($config['verify_url'], FILTER_VALIDATE_URL)) {
            $errors[] = '验证 URL 格式不正确';
        }

        if ($config['timeout'] <= 0) {
            $errors[] = '超时时间必须大于 0';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'config' => $config,
        ];
    }

    /**
     * 获取配置状态信息
     *
     * @return array 状态信息
     */
    public static function getStatus(): array
    {
        $validation = self::validateConfig();
        $config = $validation['config'];
        
        return [
            'enabled' => $config['enable'] ?? false,
            'configured' => !empty($config['secret_key']),
            'valid' => $validation['valid'],
            'errors' => $validation['errors'],
            'version' => '1.0.0',
        ];
    }
}