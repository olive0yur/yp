<?php
// 事件定义文件
return [
    'bind' => [
    ],

    'listen' => [
        'AppInit' => [],
        'HttpRun' => [],
        'HttpEnd' => [],
        'LogLevel' => [],
        'LogWrite' => [
            \app\listener\log\DingDingNotify::class, // 钉钉通知
            \app\listener\log\WxworkNotify::class, // 企业微信通知
        ],
        'admin.login' => [\app\listener\admin\LoginSuccess::class],//后台登录事件
        'admin.logout' => [\app\listener\admin\LogoutSuccess::class],//后台退出事件

        'company.login' => [\app\listener\company\LoginSuccess::class],//企业后台登录事件
        'company.logout' => [\app\listener\company\LogoutSuccess::class],//企业后台退出事件

        'agent.login' => [\app\listener\agent\LoginSuccess::class],//代理后台登录事件
        'agent.logout' => [\app\listener\agent\LogoutSuccess::class],//代理后台退出事件


        'user.mine' => [\app\listener\api\UserMine::class],//用户自动开通矿洞
        'mine.dispatch' => [\app\listener\api\MineDispatch::class],//矿机到达

        'swoole.init' => [
            \app\listener\system\StartQueueListen::class,
            \app\listener\system\StartCronListen::class,
        ]
    ],

    'subscribe' => [
    ],
];
