<?php

namespace zjkal\turnstile\Middleware;

use Webman\MiddlewareInterface;
use support\Response;
use Webman\Http\Request as HttpRequest;
use plugin\zjkal\turnstile\Turnstile;

class TurnstileMiddleware implements MiddlewareInterface
{
    public function process(HttpRequest $request, callable $handler): \Webman\Http\Response
    {
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

        $request->turnstile_result = $result;
        return $handler($request);
    }
}