<?php


namespace app\http;


use app\common\service\ContService;
use app\data\model\DataScreenInfo;
use app\data\model\DataScreenStreet;
use app\data\model\DataUserCall;
use app\data\model\DataUserFeedback;
use think\facade\Cache;
use think\facade\Log;
use think\worker\Server;
use Workerman\Lib\Timer;

class Work extends Server
{
    protected $socket = 'websocket://0.0.0.0:2346';

    public function onWorkerStart($worker)
    {
        $time_interval = 2.5;
        Timer::add($time_interval,function()use($worker){
            $param = [];
            //获取推送人数
            $countPerson = DataScreenInfo::mk()->field("count(id) as count,street_name")->group('street_name')->select()->toArray();
            foreach ($countPerson as &$item)
            {
                $item['street_id'] = $item['street_name'];
                $item['street_name'] = ContService::STREET_NAME[$item['street_name']];
            }

            $disabilityClass = [1,2,3,4];
            foreach ($disabilityClass as &$class)
            {
                $disability_class = DataScreenInfo::mk()->field("count(id) as count,disability_type,disability_class")->where(['disability_class'=>$class])->group('disability_type')->select()->toArray();
                foreach ($disability_class as &$type)
                {

                    $type['disability_type_id'] = $type['disability_type'];
                    $type['disability_type'] = ContService::DISABILITY_TYPE[$type['disability_type']];     //残疾类别

                    $type['disability_class_id'] = $type['disability_class'];
                    $type['disability_class'] = ContService::DISABILITY_CLASS[$type['disability_class']];   //残疾等级
                }
                $disability_class_group[$type['disability_class_id']][]  = $disability_class;
            }
            $param['countPerson'] = $countPerson;        //人数分布
            $param['disability_class_group'] = $disability_class_group;   //残疾分布


            //获取残疾呼叫
            $count = Cache::get('callInfoCount');
            $callInfo = DataUserCall::mk()->field('id,longitude,latitude,user_id')->where(['is_push'=>1])->select();
            if (count($callInfo) > $count)
            {
                foreach ($callInfo as $key=>&$item)
                {
                    $usersCallInfo = DataScreenInfo::mk()->where(['id'=>$item['user_id']])->find();
                    $item['screen_name'] = $usersCallInfo['screen_name'];      //呼叫人姓名
                    $item['tele_phone'] = $usersCallInfo['tele_phone'];        //呼叫人电话
                    $item['residential_address'] = $usersCallInfo['residential_address'];   //居住地址
                    $item['street_name'] = $usersCallInfo['street_name'];   //居住地址
                    DataUserCall::mk()->where(['id'=>$item['id']])->save(['is_push'=>2]);
                    unset($callInfo[$key]['id']);
                    unset($callInfo[$key]['user_id']);
                }
            }else
            {
                $callInfo = [];
            }
            Cache::set('callInfoCount',count($callInfo));
            $param['callInfo'] = $callInfo;




            //服务数据
            $configService = DataScreenStreet::mk()->select()->toArray();
            foreach ($configService as $key=>&$item)
            {
                $item['count'] = DataUserCall::mk()->where(['server_id'=>$item['id']])->count();
            }
            $param['serviceType'] = $configService;



            //服务数据
            $serviceCount = DataUserCall::mk()->count();   //服务数量
            $doneCount = DataUserCall::mk()->where(['is_done'=>1])->count();   //完成数量
            $noDoneCount = DataUserCall::mk()->where(['is_done'=>2])->count(); //未完成数量
            $stafi = DataUserFeedback::mk()->where(['type'=>1])->count();      //满意度数量
            $shensu = DataUserFeedback::mk()->where(['type'=>2])->count();      //申诉数量
            $tousu = DataUserFeedback::mk()->where(['type'=>3])->count();      //投诉数量
            $param['dayService'] = [
                'serviceCount' =>$serviceCount,
                'doneCount' =>$doneCount,
                'noDoneCount' =>$noDoneCount,
                'stafi' =>$stafi,
                'shensu' =>$shensu,
                'tousu' =>$tousu,
            ];
            foreach ($worker->connections as $connection) {

                $connection->send(json_encode($param));
            }

           /* foreach($worker->connections as $connection) {
                var_dump(2222222);
                $connection->send(json_encode(['id'=>1]));
            }*/

        });
    }
    public function onConnect($connection){
        Log::info('connect success');
        $connection->send(json_encode('connect success'));
    }

    ## 接收消息，接收心跳消息 ,接收用户id 信息等等
    public function onMessage($connection,$data)
    {
        $connection->lastMessageTime = time();
        $connection->send(json_encode('receive success'));
    }

}