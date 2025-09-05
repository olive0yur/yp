<?php


namespace app\work;

use app\common\model\BaseModel;
use app\common\model\mine\MineUserModel;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersRepository;
use think\facade\Log;
use think\worker\Server;
use Workerman\Lib\Timer;

class Works extends Server
{
    protected $socket = 'websocket://0.0.0.0:9503';
    protected $so;
    protected $arr=[];
    protected $context = [
        'ssl' => [
            'local_cert' => '/www/server/panel/vhost/ssl/sxqichuangkeji.com/fullchain.pem', //证书文件的存放路径（请改成自己的路径）
            'local_pk' => '/www/server/panel/vhost/ssl/sxqichuangkeji.com/privkey.pem', //证书私钥的存放路径（请改成自己的路径）
            'verify_peer' => false,
        ],
    ];
    protected $option = [
        'transport' => 'ssl',   //设置transport开启ssl，启用wss://
    ];

    public function onWorkerStart($worker)
    {
    }
}