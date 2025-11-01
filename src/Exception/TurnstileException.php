<?php

namespace zjkal\turnstile\Exception;

use Exception;

/**
 * Turnstile 异常类
 */
class TurnstileException extends Exception
{
    /**
     * 错误代码列表
     *
     * @var array
     */
    private array $errorCodes;

    /**
     * 构造函数
     *
     * @param string $message 错误消息
     * @param array $errorCodes 错误代码列表
     * @param int $code 错误代码
     * @param Exception|null $previous 上一个异常
     */
    public function __construct(string $message = '', array $errorCodes = [], int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCodes = $errorCodes;
    }

    /**
     * 获取错误代码列表
     *
     * @return array
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    /**
     * 获取第一个错误代码
     *
     * @return string|null
     */
    public function getFirstErrorCode(): ?string
    {
        return $this->errorCodes[0] ?? null;
    }

    /**
     * 检查是否包含特定错误代码
     *
     * @param string $errorCode 错误代码
     * @return bool
     */
    public function hasErrorCode(string $errorCode): bool
    {
        return in_array($errorCode, $this->errorCodes, true);
    }
}