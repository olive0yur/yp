<?php

use think\facade\Route;

Route::group('game', function () {
    Route::group('kill', function () {
        Route::post('getUserInfo', '/getUserInfo')->prefix('game.game.kill')->middleware(\app\http\middleware\game\CheckToken::class, true);
        Route::post('getbalance', '/getbalance')->prefix('game.game.kill');
        Route::post('decbalance', '/decbalance')->prefix('game.game.kill');
        Route::post('incbalance', '/incbalance')->prefix('game.game.kill');
        Route::post('test', '/test')->prefix('game.game.kill');
    });
    Route::group('race', function () {
        Route::get('getUserInfo', '/getUserInfo')->prefix('game.game.race')->middleware(\app\http\middleware\game\CheckToken::class, true);
        Route::post('decbalance', '/decbalance')->prefix('game.game.race')->middleware(\app\http\middleware\game\CheckToken::class, true);
        Route::post('incbalance', '/incbalance')->prefix('game.game.race')->middleware(\app\http\middleware\game\CheckToken::class, true);
        Route::post('cordbalance', '/cordbalance')->prefix('game.game.race')->middleware(\app\http\middleware\game\CheckToken::class, true);
    });
})->prefix('game.game');

// miss路由
Route::miss(function () {
    return \think\Response::create(['code' => 404, 'msg' => '接口不存在'], 'json')->code(404);
});


