<?php

declare (strict_types=1);

namespace app\http\middleware\game;

use app\common\repositories\users\UsersRepository;
use app\common\services\JwtService;
use app\exception\ApiException;
use app\http\response\api\StatusCode;
use app\Request;
use think\exception\ValidateException;
use think\facade\Cache;

class CheckToken
{

    public function handle(Request $request, \Closure $next, $force = true)
    {
        /**
         * @var UsersRepository $usersRepository
         */
        $usersRepository = app()->make(UsersRepository::class);
        try {
            $token = $request->header('token');
            if (!$token || strlen($token) != 40) {
                throw new ApiException('token错误', StatusCode::LOGIN_CODE);
            }
            $token = $usersRepository->getToken($token);
            if (!$token) {
                throw new ApiException('token错误', StatusCode::LOGIN_CODE);
            }

            $service = new JwtService();
            $payload = $service->decode($token);
            if (!$payload) {
                throw new ApiException('token错误', StatusCode::LOGIN_CODE);
            }
            $userId = $payload['data']->uid;
            $isLogin = Cache::store('redis')->get('token_'.$userId);
            if($isLogin != $request->header('token')){
                throw new ApiException('token错误', StatusCode::LOGIN_CODE);
            }
            $userInfo = $usersRepository->getDetail($userId);

            if (!$userInfo) {
                throw new ApiException('用户不存在', StatusCode::LOGIN_CODE);
            }
            if ($userInfo['status'] != 1) {
                throw new ApiException('账号已被禁用', StatusCode::LOGIN_CODE);
            }
            $usersRepository->updateToken($token);
        } catch (\Exception $e) {
            if ($force) {
                throw  $e;
            }
            $request->macro('isLogin', function () {
                return false;
            });
            $request->macros(['userId', 'userInfo'], function () {
                return null;
            });
            return $next($request);
        }

        $request->macro('isLogin', function () {
            return true;
        });

        // 用户信息
        $request->macro('userInfo', function () use (&$userInfo) {
            return $userInfo;
        });
        // 用户ID
        $request->macro('userId', function () use (&$userInfo) {
            return $userInfo['id'];
        });
        $res = $next($request);
        return $res;
    }
}