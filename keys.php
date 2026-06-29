<?php

return [
    'api_key' => 'REPLACE_ME',

    'smtp' => [
        'host' => 'smtp.gmail.com',
        'username' => 'user@example.invalid',
        'password' => 'REPLACE_ME',
        'port' => 587,
        'encryption' => 'tls',
    ],

    'db' => [
        'host' => '127.0.0.1',
        'user' => 'root',
        'pass' => '',
        'name' => 'weedex',
    ],
    'evervault'=>[
        'app_id'=>'app_810612df38db',
        'team_id'=>'team_872bf8028573',
        'api_key'=>'ev:key:1:6xmb7Ks2GDBAwJ8LZgJxYsqLDlgfaW27MiwE10aK20AmhYtp3HNtom2BgeoZJJE9a:Ct0XJ+:689wBQ'
    ],
    'aws' => [
        'key' => 'AKIAYS2NS2YGXB325QIG',
        'secret' => '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',
        'region' => 'us-east-1',
        'dynamodb_table' => 'customer_payments',
    ]
];
