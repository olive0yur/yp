<?php

use think\facade\Route;


Route::rule('SLBHealthCheck', function () {
    return response()->code(200);
});

// miss路由
Route::miss(function () {
    return \think\Response::create(['code' => 404, 'msg' => '接口不存在'], 'json')->code(404);
});


