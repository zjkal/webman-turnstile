<?php

namespace zjkal\WebmanTurnstile\Middleware;

use Webman\MiddlewareInterface;
use support\Response;
use support\Request;
use zjkal\WebmanTurnstile\Turnstile;

/**
 * Turnstile 验证中间件
 */
class TurnstileMiddleware implements MiddlewareInterface
{
    /**
     * 处理请求
     *
     * @param Request $request 请求对象
     * @param callable $handler 下一个处理器
     * @return Response 响应对象
     */
    public function process(Request $request, callable $handler): Response
    {
        // 只对 POST 请求进行验证
        if ($request->method() !== 'POST') {
            return $handler($request);
        }

        $token = $request->post('cf-turnstile-response');
        $clientIp = $request->getRealIp();

        $result = Turnstile::verify($token, $clientIp);

        if (!$result['success']) {
            $errorCodes = $result['error-codes'] ?? [];
            $errorMessages = Turnstile::getErrorMessages($errorCodes);
            
            return new Response(400, [
                'Content-Type' => 'application/json'
            ], json_encode([
                'success' => false,
                'message' => 'Turnstile 验证失败',
                'errors' => $errorMessages,
                'error_codes' => $errorCodes,
            ], JSON_UNESCAPED_UNICODE));
        }

        // 将验证结果添加到请求属性中
        $request->turnstile_result = $result;

        return $handler($request);
    }
}