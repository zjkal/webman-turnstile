<?php

namespace zjkal\WebmanTurnstile\Example;

use support\Request;
use support\Response;
use zjkal\WebmanTurnstile\Turnstile;
use zjkal\WebmanTurnstile\TurnstileHelper;
use zjkal\WebmanTurnstile\Exception\TurnstileException;

/**
 * Turnstile 示例控制器
 * 
 * 这个控制器展示了如何在 Webman 中使用 Turnstile 验证
 */
class ExampleController
{
    /**
     * 显示表单页面
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $siteKey = 'your-site-key-here'; // 替换为你的站点密钥
        
        $html = '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnstile 验证示例</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .result { margin-top: 20px; padding: 15px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
    ' . TurnstileHelper::generateScript() . '
</head>
<body>
    <h1>Turnstile 验证示例</h1>
    
    <form method="POST" action="/example/verify">
        <div class="form-group">
            <label for="name">姓名:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">邮箱:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>人机验证:</label>
            ' . TurnstileHelper::generateHtml($siteKey) . '
        </div>
        
        <button type="submit">提交</button>
    </form>
    
    <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 4px;">
        <h3>使用说明:</h3>
        <ol>
            <li>将上面的 <code>your-site-key-here</code> 替换为你的 Cloudflare Turnstile 站点密钥</li>
            <li>在配置文件中设置你的密钥: <code>config/turnstile.php</code></li>
            <li>提交表单测试验证功能</li>
        </ol>
    </div>
</body>
</html>';

        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    /**
     * 处理表单提交和验证
     *
     * @param Request $request
     * @return Response
     */
    public function verify(Request $request): Response
    {
        $name = $request->post('name');
        $email = $request->post('email');
        
        // 方法1: 使用助手类从请求中验证
        try {
            $result = TurnstileHelper::verifyFromRequestOrFail($request);
            
            $html = $this->generateResultPage(true, [
                'message' => '验证成功！',
                'name' => $name,
                'email' => $email,
                'turnstile_result' => $result
            ]);
            
        } catch (\Exception $e) {
            $html = $this->generateResultPage(false, [
                'message' => $e->getMessage(),
                'name' => $name,
                'email' => $email
            ]);
        }

        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    /**
     * API 验证示例
     *
     * @param Request $request
     * @return Response
     */
    public function apiVerify(Request $request): Response
    {
        // 方法2: 直接使用 Turnstile 类验证（IP 会自动获取）
        $token = $request->post('cf-turnstile-response');
        
        try {
            $result = Turnstile::verify($token);
            
            if ($result['success']) {
                return json([
                    'success' => true,
                    'message' => '验证成功',
                    'data' => [
                        'challenge_ts' => $result['challenge_ts'] ?? null,
                        'hostname' => $result['hostname'] ?? null,
                    ]
                ]);
            } else {
                return json([
                    'success' => false,
                    'message' => '验证失败',
                    'errors' => Turnstile::getErrorMessages($result['error-codes'] ?? []),
                    'error_codes' => $result['error-codes'] ?? []
                ], 400);
            }
        } catch (TurnstileException $e) {
            return json([
                'success' => false,
                'message' => '验证异常：' . $e->getMessage(),
                'error_codes' => $e->getErrorCodes()
            ], 500);
        }
    }

    /**
     * 快速验证示例
     *
     * @param Request $request
     * @return Response
     */
    public function quickCheck(Request $request): Response
    {
        // 方法3: 快速验证（仅返回布尔值）
        $isValid = TurnstileHelper::checkFromRequest($request);
        
        return json([
            'valid' => $isValid,
            'message' => $isValid ? '验证通过' : '验证失败'
        ]);
    }

    /**
     * 获取配置状态
     *
     * @param Request $request
     * @return Response
     */
    public function status(Request $request): Response
    {
        $status = TurnstileHelper::getStatus();
        return json($status);
    }

    /**
     * 生成结果页面
     *
     * @param bool $success 是否成功
     * @param array $data 数据
     * @return string HTML 内容
     */
    private function generateResultPage(bool $success, array $data): string
    {
        $resultClass = $success ? 'success' : 'error';
        $resultTitle = $success ? '✅ 成功' : '❌ 失败';
        
        $html = '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>验证结果</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .result { padding: 20px; border-radius: 4px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .data { background: #f8f9fa; padding: 15px; border-radius: 4px; margin-top: 20px; }
        pre { background: #f1f1f1; padding: 10px; border-radius: 4px; overflow-x: auto; }
        a { color: #007cba; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>验证结果</h1>
    
    <div class="result ' . $resultClass . '">
        <h2>' . $resultTitle . '</h2>
        <p>' . htmlspecialchars($data['message']) . '</p>
    </div>
    
    <div class="data">
        <h3>提交的数据:</h3>
        <p><strong>姓名:</strong> ' . htmlspecialchars($data['name'] ?? '') . '</p>
        <p><strong>邮箱:</strong> ' . htmlspecialchars($data['email'] ?? '') . '</p>
    </div>';

        if ($success && isset($data['turnstile_result'])) {
            $html .= '
    <div class="data">
        <h3>Turnstile 验证详情:</h3>
        <pre>' . htmlspecialchars(json_encode($data['turnstile_result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>
    </div>';
        }

        $html .= '
    <p><a href="/example">← 返回表单</a></p>
</body>
</html>';

        return $html;
    }
}