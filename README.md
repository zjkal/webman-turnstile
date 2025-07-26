# Webman Turnstile

一个用于 Webman 框架的 Cloudflare Turnstile 验证组件。

## 版本兼容性

- ✅ Webman 1.x
- ✅ Webman 2.x
- 📋 PHP >= 7.4

## 简介

Webman Turnstile 是一个专为 Webman 框架设计的 Composer 包，用于简化 Cloudflare Turnstile 的后端验证流程。通过简单的配置和静态方法调用，您可以轻松地在 Webman 项目中集成 Turnstile 验证功能。

## 特性

- 🚀 简单易用的静态方法调用
- ⚙️ 自动生成配置文件
- 🔒 安全的后端验证
- 📦 完全兼容 Webman 框架
- 🛠️ 支持自定义配置
- 🌐 自动获取客户端 IP 地址

## 安装

使用 Composer 安装：

```bash
composer require zjkal/webman-turnstile
```

安装完成后，配置文件会自动生成到 `config/turnstile.php`。

## 配置

编辑配置文件 `config/turnstile.php`：

```php
<?php
return [
    'enable' => true,
    'secret_key' => 'your-turnstile-secret-key',
    'timeout' => 30, // 验证超时时间（秒）
    'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
];
```

### 配置说明

- `enable`: 是否启用 Turnstile 验证
- `secret_key`: Cloudflare Turnstile 的密钥
- `timeout`: HTTP 请求超时时间
- `verify_url`: Turnstile 验证接口地址

## 使用方法

> 💡 **提示**: 所有验证方法的 IP 参数都是可选的。如果不传入 IP 参数，系统会自动通过 Webman 的 `request()` 助手函数获取客户端真实 IP 地址，让使用更加简便。

### 基本验证

```php
use zjkal\WebmanTurnstile\Turnstile;
use zjkal\WebmanTurnstile\Exception\TurnstileException;

// 验证 Turnstile token（IP 地址会自动获取）
$token = $request->post('cf-turnstile-response');

try {
    $result = Turnstile::verify($token);
    
    if ($result['success']) {
        // 验证成功
        echo "验证通过！";
    } else {
        // 验证失败
        echo "验证失败：" . implode(', ', $result['error-codes']);
    }
} catch (TurnstileException $e) {
    // 处理异常（如密钥未配置、网络错误等）
    echo "验证异常：" . $e->getMessage();
    echo "错误代码：" . implode(', ', $e->getErrorCodes());
}
```

### 快速验证（仅返回布尔值）

```php
use zjkal\WebmanTurnstile\Turnstile;
use zjkal\WebmanTurnstile\Exception\TurnstileException;

$token = $request->post('cf-turnstile-response');

try {
    if (Turnstile::check($token)) {
        // 验证通过
        echo "验证成功！";
    } else {
        // 验证失败
        echo "验证失败！";
    }
} catch (TurnstileException $e) {
    // 处理异常（如密钥未配置、网络错误等）
    echo "验证异常：" . $e->getMessage();
}
```

## 前端集成

在您的 HTML 页面中添加 Turnstile 组件：

```html
<!DOCTYPE html>
<html>
<head>
    <title>Turnstile 验证</title>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <form method="POST" action="/verify">
        <!-- 其他表单字段 -->
        
        <!-- Turnstile 组件 -->
        <div class="cf-turnstile" data-sitekey="your-site-key"></div>
        
        <button type="submit">提交</button>
    </form>
</body>
</html>
```

## API 参考

### Turnstile::verify($token, $remoteIp = null)

验证 Turnstile token 并返回详细结果。

**参数：**
- `$token` (string): Turnstile 响应 token
- `$remoteIp` (string, 可选): 客户端 IP 地址，不传则自动从 request() 获取

**返回值：**
返回包含验证结果的数组：
```php
[
    'success' => true|false,
    'challenge_ts' => '2023-01-01T00:00:00.000Z', // 挑战完成时间
    'hostname' => 'example.com', // 验证的主机名
    'error-codes' => [], // 错误代码数组
    'action' => 'login', // 动作名称（如果设置）
    'cdata' => 'custom_data' // 自定义数据（如果设置）
]
```

**异常：**
- `TurnstileException`: 当密钥未配置、网络请求失败或响应解析失败时抛出

### Turnstile::check($token, $remoteIp = null)

快速验证方法，仅返回布尔值。

**参数：**
- `$token` (string): Turnstile 响应 token
- `$remoteIp` (string, 可选): 客户端 IP 地址，不传则自动从 request() 获取

**返回值：**
- `true`: 验证成功
- `false`: 验证失败

**异常：**
- `TurnstileException`: 当密钥未配置、网络请求失败或响应解析失败时抛出

## 异常处理

当发生配置错误、网络错误或响应解析错误时，验证方法会抛出 `TurnstileException` 异常。您可以通过捕获异常来处理这些错误情况：

```php
use zjkal\WebmanTurnstile\Turnstile;
use zjkal\WebmanTurnstile\Exception\TurnstileException;

try {
    $result = Turnstile::verify($token);
    // 处理验证结果...
} catch (TurnstileException $e) {
    // 获取异常信息
    $message = $e->getMessage();
    $errorCodes = $e->getErrorCodes();
    
    // 检查特定错误类型
    if ($e->hasErrorCode('missing-secret-key')) {
        // 处理密钥未配置的情况
        error_log('Turnstile 密钥未配置');
    } elseif ($e->hasErrorCode('network-error')) {
        // 处理网络错误
        error_log('Turnstile 网络请求失败');
    }
    
    // 返回错误响应给用户
    return json(['error' => '验证服务暂时不可用，请稍后重试']);
}
```

### TurnstileException 方法

- `getMessage()`: 获取异常消息
- `getErrorCodes()`: 获取错误代码数组
- `getFirstErrorCode()`: 获取第一个错误代码
- `hasErrorCode($code)`: 检查是否包含特定错误代码

## 错误代码

常见的 Turnstile 错误代码：

- `missing-input-secret`: 缺少密钥参数
- `invalid-input-secret`: 无效的密钥
- `missing-input-response`: 缺少响应参数
- `invalid-input-response`: 无效的响应参数
- `bad-request`: 请求格式错误
- `timeout-or-duplicate`: 超时或重复提交
- `internal-error`: 内部错误

## 许可证

MIT License

## 贡献

欢迎提交 Issue 和 Pull Request！

## 更新日志

### v1.0.0
- 初始版本发布
- 支持基本的 Turnstile 验证功能
- 自动配置文件生成