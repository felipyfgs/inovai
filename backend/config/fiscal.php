<?php

return [
    'nfe' => [
        'versao' => '4.00',
        'layout' => 'PL_009_V4',
        'modelo' => 55,
    ],
    'nfce' => [
        'versao' => '4.00',
        'layout' => 'PL_009_V4',
        'modelo' => 65,
    ],
    'cte' => [
        'versao' => '4.00',
        'layout' => 'CTe_v4.00',
        'modelo' => 57,
    ],
    'mdfe' => [
        'versao' => '3.00',
        'layout' => 'MDFe_v3.00',
        'modelo' => 58,
    ],
    'uf_cuf' => [
        'AC' => 12, 'AL' => 27, 'AM' => 13, 'AP' => 16, 'BA' => 29,
        'CE' => 23, 'DF' => 53, 'ES' => 32, 'GO' => 52, 'MA' => 21,
        'MG' => 31, 'MS' => 50, 'MT' => 51, 'PA' => 15, 'PB' => 25,
        'PE' => 26, 'PI' => 22, 'PR' => 41, 'RJ' => 33, 'RN' => 24,
        'RO' => 11, 'RR' => 14, 'RS' => 43, 'SC' => 42, 'SE' => 28,
        'SP' => 35, 'TO' => 17,
    ],
    'timeout' => 30,
    'proxy' => [
        'ip' => env('FISCAL_PROXY_IP', ''),
        'port' => env('FISCAL_PROXY_PORT', ''),
        'user' => env('FISCAL_PROXY_USER', ''),
        'pass' => env('FISCAL_PROXY_PASS', ''),
    ],
    'contingencia' => [
        'svcan' => [
            'uf' => 'AN',
            'cUF' => 91,
            'versao' => '4.00',
        ],
        'svcrs' => [
            'uf' => 'RS',
            'cUF' => 43,
            'versao' => '4.00',
        ],
    ],
];
