<?php

$config = [
    'id' => 'my',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'defaultRoute' => 'my/index',
    'components' => [
        'log' => [
            // 'traceLevel' => YII_DEBUG ? 3 : 0,
            // 'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    // 'exportInterval' => 1,
                ],
            ],
        ],
    ],
];

return $config;

?>