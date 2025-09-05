<?php

use think\facade\Route;


$agentRoute = function () {
    Route::get('captcha', "\\think\\captcha\\CaptchaController@index")->prefix('');
    // 后台登录
    Route::group('login', function () {
        Route::get('index', 'Login/index')->name('agentUserLogin')->middleware(\app\http\middleware\agent\CheckLogin::class, false);
        Route::post('doLogin', 'Login/doLogin')->name('agentUserDoLogin');
    })->prefix('agent.');

    Route::group('', function () {
        Route::get('/', 'Index/index')->name('agentIndex');
        Route::group('index', function () {
            Route::get('welcome', 'Index/welcome')->name('agentIndexWelcome');
            Route::get('getMenu', 'Index/getMenu')->name('agentIndexGetMenu');
            Route::get('clearCache', 'Index/clearCache')->name('agentIndexClearCache');
            Route::post('signOut', 'Index/signOut')->name('agentLoginout');
            Route::get('statistics', 'Index/statistics')->name('agentIndexStatistics');
        });


        ## 用户  add ##
        Route::group('users', function () {
            Route::group('users', function () {
                Route::rule('pushCountList', '/pushCountList')->name('agentUsersPushCountList');##团队列表
            })->prefix('agent.users.Users');##用户列表
            Route::group('child', function () {
                Route::rule('list', '/list')->name('agentChildList');##代理列表
                Route::rule('add', '/add')->name('agentChildListAdd');##代理列表
                Route::rule('edit', '/edit')->name('agentChildListEdit');##代理列表
                Route::rule('del', '/del')->name('agentChildListDel');##代理列表
            })->prefix('agent.users.UsersChild');##用户列表
            Route::group('foodlod', function () {
                Route::rule('list', '/list')->name('agentFoodlogList');##代理列表
            })->prefix('agent.users.foodlod');##用户列表
        });
        ## 用户  end ##
    })->middleware(\app\http\middleware\agent\CheckLogin::class);
    // miss路由
    Route::miss(function () {
        return view('agent/error/404');
    });
};

if (env('SINGLE_DOMAIN_MODE')) {
    Route::group( 'agent', $agentRoute)->prefix('agent.'); // 单域名访问
} else {
    Route::domain(env('agent_URL'), $agentRoute)->prefix('agent.'); // 独立域名访问
}