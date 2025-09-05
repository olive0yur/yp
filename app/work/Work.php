<?php


namespace app\work;

use app\common\repositories\users\UsersRepository;
use think\facade\Log;
use think\worker\Server;
use Workerman\Lib\Timer;
define('HEARTBEAT_TIME', 20);// 心跳间隔20秒

class Work extends Server
{
    protected $socket = 'websocket://0.0.0.0:9502';
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
        Timer::add(2, function()use($worker){
            $time_now = time();
            foreach($worker->connections as $connection) {
                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if($this->arr){
                    /** @var UsersRepository  $usersRepository */
                    $usersRepository= app()->make(UsersRepository::class);
                    foreach ($this->arr as $v){
                        $user = $usersRepository->get($v);
                        if($user){
                            $data = ['food' => $user['food']];
                            // 建立socket连接到内部推送端口
                            $ret = $this->sendMessageByUid($v, $data); // 返回推送结果
                            $connection->send($ret?'true':false);
                        }

                        // 返回推送结果
                    }
                }
                if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                    $connection->close();
                }
            }
        });

    }

    public function sendMessageByUid($uid, $message)
    {
        if (isset($this->so[$uid])) {
            $connection = $this->so[$uid];
            $connection->send(json_encode($message));
            return true;
        }
        return false;
    }


    public function onConnect($connection){
        Log::info('connect success');
        $connection->send(json_encode('connect success'));
    }

    ## 接收消息，接收心跳消息 ,接收用户id 信息等等
    public function onMessage($connection,$data)
    {
        $aa = json_decode($data,true);
        if(is_array($aa)){
            $connection->uuid = $aa['uuid'];
            $this->so[$connection->uuid] = $connection;
            if(!in_array($aa['uuid'],$this->arr)){
                $this->arr[] = $aa['uuid'];
            }
        }
    }

}