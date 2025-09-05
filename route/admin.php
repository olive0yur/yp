<?php

use think\facade\Route;


$adminRoute = function () {

    Route::get('captcha', "\\think\\captcha\\CaptchaController@index")->prefix('');
    // 后台登录
    Route::group('login', function () {
        Route::get('index', 'Login/index')->name('adminLoginPage')->middleware(\app\http\middleware\admin\CheckLogin::class, false);
        Route::post('doLogin', 'Login/doLogin')->name('adminLoginDoLogin');
    })->prefix('admin.system.admin.');

    Route::group('', function () {

        Route::group('export', function () {
            // 导出文件下载
            Route::get('downloadFile', 'common.Export/download')->prefix('')->name('exportFileDownload');
        });
        Route::get('/', 'Index/index')->name('adminIndex');##后台首页
        Route::group('index', function () {
            Route::get('welcome', 'Index/welcome')->name('adminIndexWelcome');##欢迎页
            Route::get('getMenu', 'Index/getMenu')->name('adminIndexGetMenu');##获取后台菜单目录
            Route::get('clearCache', 'Index/clearCache')->name('adminClearCache');##清除缓存
            Route::post('signOut', 'Index/signOut')->name('adminLogout');##退出登录
            Route::get('statistics', 'Index/statistics')->name('adminIndexStatistics');##获取统计数据
            Route::get('billMonthlyAmountData', 'Index/billMonthlyAmountData')->name('adminIndexBillMonthlyAmountData');##账单月报统计
        });



        Route::group('', function () {##网站配置
            Route::group('admin', function () {
                Route::group('company', function () {
                    Route::get('list', '/list')->name('adminCompanyList');
                    Route::rule('add', '/add')->name('adminCompanyAdd');
                    Route::rule('del', '/del')->name('adminCompanyDel');
                    Route::rule('edit', '/edit')->name('adminCompanyEdit');
                    Route::rule('setAuth', '/setAuth')->name('adminCompanySetAuth');
                    Route::rule('status', '/status')->name('adminCompanyStatus');
                });
            })->prefix('admin.system.company.CompanyManage');
            Route::group('system', function () {
                Route::group('website', function () {
                    Route::rule('siteInfo', 'Website/siteinfo')->name('adminSystemSiteInfo');
                    Route::rule('programInfo', 'Website/programInfo')->name('adminSystemProgramInfo');
                });

                Route::group('admin', function () {
                    Route::group('rule', function () {
                        Route::get('list', 'AdminAuthRule/list')->name('adminAuthMenuList');##权限列表
                        Route::rule('edit', 'AdminAuthRule/edit')->name('adminEditAuthMenu');##编辑权限
                    });
                    Route::group('user', function () {
                        Route::rule('editInfo', 'AdminUser/editInfo')->name('adminEditSelfInfo');##修改基本资料
                        Route::rule('editPassword', 'AdminUser/editPassword')->name('adminEditSelfPassword');##修改密码
                        Route::get('list', 'AdminUser/list')->name('adminAdminUserList');##管理员列表
                        Route::rule('add', 'AdminUser/add')->name('adminAddAdminUser');##添加管理员
                        Route::rule('edit', 'AdminUser/edit')->name('adminEditAdminUser');##编辑管理员
                        Route::rule('del', 'AdminUser/del')->name('adminDelAdminUser');##删除管理员
                        Route::rule('setAuth', 'AdminUser/setAuth')->name('adminSetAdminUserAuth');##设置权限
                        Route::get('adminLogList', 'AdminUser/adminLogList')->name('adminAdminUserLogList');##管理员日志
                    });
                })->prefix('admin.system.admin.');
            })->prefix('admin.system.');

        })->middleware(\app\http\middleware\admin\CheckAuth::class);
    })->middleware(\app\http\middleware\admin\CheckLogin::class);
    // miss路由
    Route::miss(function () {
        return view('admin/error/404');
    });
};

if (env('SINGLE_DOMAIN_MODE')) {
    Route::group(env('ADMIN_URL') ?: 'admin', $adminRoute)->prefix('admin.'); // 单域名访问
} else {
    Route::domain(env('ADMIN_URL'), $adminRoute)->prefix('admin.'); // 独立域名访问
}