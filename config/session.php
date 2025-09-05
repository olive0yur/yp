<?php
// +----------------------------------------------------------------------
// | 会话设置
// +----------------------------------------------------------------------

return [
    // session name
    'name'           => env('session.name','PHPSESSID'),
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => env('session.var_session_id','PHPSESSID'),
    // 驱动方式 支持file cache
    'type'           => env('session.driver','file'),
    // 存储连接标识 当type使用cache的时候有效
    'store'          => null,
    // 过期时间
    'expire'         => 86400,
    // 前缀
    'prefix'         => env('session.prefix',''),
];
