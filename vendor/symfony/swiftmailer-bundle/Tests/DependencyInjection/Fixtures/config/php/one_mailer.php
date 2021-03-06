<?php

$container->loadFromExtension('swiftmailer', [
    'default_mailer' => 'main_mailer',
    'mailers' => [
        'main_mailer' => [
            'transport' => 'smtp',
            'username' => 'user',
            'password' => 'pass',
            'host' => 'example.org',
            'port' => '12345',
            'encryption' => 'tls',
            'auth-mode' => 'login',
            'timeout' => '1000',
            'source_ip' => '127.0.0.1',
            'local_domain' => 'local.example.org',
        ],
    ],
]);
