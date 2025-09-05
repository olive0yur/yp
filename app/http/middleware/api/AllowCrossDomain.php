<?php

namespace app\http\middleware\api;

use think\facade\Config;
use think\Response;

class AllowCrossDomain
{
    protected $header = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age' => 1728000,
        'Access-Control-Allow-Headers' => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With,Token,FZBSESSID,Language,Client-Type,Company-Code,User-Mark',
    ];

    public function handle($request, \Closure $next)
    {
        $header = $this->header;

        $cookieDomain = Config::get('cookie.domain', '*');
        $origin = $request->header('origin');

        if ($origin && ('' == $cookieDomain || strpos($origin, $cookieDomain))) {
            $header['Access-Control-Allow-Origin'] = $origin;
        } else {
            $header['Access-Control-Allow-Origin'] = '*';
        }
        if (strtoupper($request->method()) == "OPTIONS") {
            return Response::create()->code(200)->header($header);
        }
        $request->clientType = $request->header('client-type');
        $request->userMark = $request->header('user-mark');

        return $next($request)->header($header);
    }
}