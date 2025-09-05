<?php

namespace app\common\services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\admin\Exception;

class JwtService
{
    // 密钥
    const SECRET_KEY = '';

    /**
     * 创建token
     *
     * @param int $id
     * @param string $type
     * @param $exp
     * @param array $params
     * @return array
     */
    public function createToken(int $id, string $type, $exp, array $params = [])
    {
        $host = app('request')->host();
        $key = '!@#$%*&';         //这里是自定义的一个随机字串，应该写在config文件中的，解密时也会用，相当    于加密中常用的 盐  salt
        $token = array(
            "iss" => $host,        //签发者 可以为空
            "aud" => 'my',          //面象的用户，可以为空
            "iat" => time(),      //签发时间
            "nbf" => time() + 3,    //在什么时候jwt开始生效  （这里表示生成100秒后才生效）
            "exp" => time()+  60*60*24*365, //token 过期时间
            "data" => [           //记录的userid的信息，这里是自已添加上去的，如果有其它信息，可以再添加数组的键值对
                'uid' => $id,
            ]
        );
        $keyId  = 'xf';
        $jwt = JWT::encode($token, $key, "HS256",$keyId);  //根据参数生成了 token
        $params['token'] = $jwt;
        $params['out'] = $exp * 60 * 60;
        return $params;
    }

    /**
     * 解析token
     *
     * @param string $token
     * @return false|\stdClass
     */
    public function decode(string $token)
    {
        if(strlen($token) <=35){
            return ['code'=>-1,'msg'=>'请重新登录'];
        }

        $key = '!@#$%*&';
        $status = array("code" => 2);
        try {
            JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($token, new Key($key,'HS256')); //HS256方式，这里要和签发的时候对应
            $arr = (array)$decoded;
            $res['code'] = 1;
            $res['data'] = $arr['data'];
            return $res;
        } catch (\Firebase\JWT\SignatureInvalidException $e) { //签名不正确
            $status['msg'] = "签名不正确";
            return $status;
        } catch (\Firebase\JWT\BeforeValidException $e) { // 签名在某个时间点之后才能用
            $status['msg'] = "token失效";
            return $status;
        } catch (\Firebase\JWT\ExpiredException $e) { // token过期
            $status['msg'] = "token失效";
            return $status;
        } catch (Exception $e) { //其他错误
            $status['msg'] = "未知错误";
            return $status;
        }
    }
}