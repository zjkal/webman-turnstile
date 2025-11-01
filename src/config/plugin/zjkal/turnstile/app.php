<?php

return [
    'enable' => true,

    // Cloudflare Turnstile 密钥
    'secret_key' => '',

    // 验证超时时间（秒）
    'timeout' => 30,

    // Turnstile 验证接口地址
    'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',

    // 是否验证主机名
    'verify_hostname' => false,

    // 允许的主机名列表（当 verify_hostname 为 true 时使用）
    'allowed_hostnames' => [
        // 'example.com',
        // 'www.example.com',
    ],
];