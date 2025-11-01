<?php

namespace plugin\zjkal\turnstile\Example;

use support\Request;
use support\Response;
use plugin\zjkal\turnstile\Turnstile;
use plugin\zjkal\turnstile\TurnstileHelper;
use plugin\zjkal\turnstile\Exception\TurnstileException;

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
            <input type="text" id="name" name="name" placeholder="请输入您的姓名" required>
        </div>
        
        <div class="form-group">
            <label for="email">邮箱:</label>
            <input type="email" id="email" name="email" placeholder="请输入您的邮箱" required>
        </div>
        
        <!-- Turnstile 组件 -->
        ' . TurnstileHelper::generateHtml($siteKey) . '

        <button type="submit">提交</button>
    </form>
</body>
</html>';

        return response($html);
    }

    /**
     * 处理验证请求
     *
     * @param Request $request
     * @return Response
     */
    public function verify(Request $request): Response
    {
        $data = [
            'name' => $request->post('name'),
            'email' => $request->post('email'),
        ];

        try {
            // 验证 Turnstile token
            $result = TurnstileHelper::verifyFromRequest($request);
            
            $success = $result['success'] ?? false;
            $data['turnstile_result'] = $result;

            $html = $this->renderResult($success, $data);
            return response($html);
        } catch (TurnstileException $e) {
            $errorHtml = $this->renderResult(false, $data, $e->getMessage());
            return response($errorHtml)->withStatus(400);
        }
    }

    /**
     * 渲染结果页面
     */
    private function renderResult(bool $success, array $data, string $errorMessage = ''): string
    {
        $statusClass = $success ? 'success' : 'error';
        $statusText = $success ? '验证成功' : '验证失败';
        
        $html = '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnstile 验证结果</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .result { margin-top: 20px; padding: 15px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        pre { background: #f6f8fa; padding: 12px; border-radius: 4px; }
        a { display: inline-block; margin-top: 20px; color: #007cba; }
    </style>
</head>
<body>
    <h1>Turnstile 验证结果</h1>

    <div class="result ' . $statusClass . '">
        <h2>' . $statusText . '</h2>';

        if (!$success && $errorMessage) {
            $html .= '<p><strong>错误信息:</strong> ' . htmlspecialchars($errorMessage) . '</p>';
        }

        $html .= '
        <h3>提交数据:</h3>
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