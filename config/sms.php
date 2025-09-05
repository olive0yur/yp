<?php

return [
    'template_type' => [
        1 => [
            'name' => '登录验证码',
            'template_tips' => '短信变量: ${code}=>验证码, ${time}=>短信有效时间(分钟)'
        ],
        2 => [
            'name' => '修改手机号',
            'template_tips' => '短信变量: ${code}=>验证码, ${time}=>短信有效时间(分钟)'
        ],
        3 => [
            'name' => '修改密码',
            'template_tips' => '短信变量: ${code}=>验证码, ${time}=>短信有效时间(分钟)'
        ],
        4 => [
            'name' => '注册短信验证码',
            'template_tips' => '短信变量: ${code}=>验证码, ${time}=>短信有效时间(分钟)'
        ],
        5 => [
            'name' => '注册成功',
            'template_tips' => '短信变量: ${password}=>初始密码, ${username}=>用户姓名'
        ]
    ],
    'sms_type' => [
        // 登录验证码
        'LOGIN_VERIFY_CODE' => 1,
        // 修改手机号验证码
        'MODIFY_MOBILE_VERIFY_CODE' => 2,
        // 修改密码验证码
        'MODIFY_PASSWORD' => 3,
        // 注册短信验证码
        'REGISTER_VERIFY_CODE' => 4,
        // 注册成功
        'REGISTER_SUCCESS' => 5
    ],
    'sms_platform' => [
        1 => '阿里云',
        2 => '聚合',
        3 => '网建'
    ]
];