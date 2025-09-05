<?php

namespace app\controller\api\system;

use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersRepository;
use app\common\services\PaymentService;
use app\controller\api\Base;
use app\common\repositories\system\SystemPactRepository;
use app\common\repositories\system\sms\SmsConfigRepository;
use app\jobs\MineProductDJob;

class Config extends Base
{
    /**
     * 获取网站基本信息
     */
    public function getSiteInfo()
    {
        $config = web_config($this->request->companyId, 'site');
        $config['adver_config_time'] = web_config($this->request->companyId, 'program.mine.adver_time');
        $config['adver_award'] = web_config($this->request->companyId, 'program.mine.adver_award');
        return $this->success($config);
    }

    /**
     * 获取短信配置
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSmsConfig(SmsConfigRepository $repository)
    {
        $config = $repository->getSmsConfig($this->request->companyId);
        $data = [
            'verify_img_code' => $config['verify_img_code'] ?? '',
            'send_sms_time_out' => $config['send_sms_time_out'] ?? ''
        ];

        return $this->success($data);
    }

    /**
     * 获取平台协议
     */
    public function getPactInfo(SystemPactRepository $repository)
    {
        $type = (int)$this->request->param('type');
        $pactInfo = $repository->getPactInfo($type, $this->request->companyId);
        if (empty($pactInfo)) {
            return $this->error('协议不存在');
        }
        return $this->success($pactInfo['content'] ?? '');
    }

    /**
     * 获取APP版本信息
     */
    public function getversionConfig()
    {
        $config = web_config($this->request->companyId, 'program');
        if (empty($config)) {
            return $this->error('参数未设置');
        }
        $config = [
            'andior' => [
                'key' => $config['version']['andior']['key'],
                'down_url' => $config['version']['andior']['down_url'],
            ],'ios' => [
                'key' => $config['version']['ios']['key'],
                'down_url' => $config['version']['ios']['down_url'],
            ],'update' => [
                'updatetext' => $config['version']['update']['text'],
            ]
        ];
        return $this->success($config);
    }

    public function getMinePool(){
        $config = web_config($this->request->companyId, 'program');
        if (!isset($config['mine']['pool'])) {
            return $this->error('参数未设置');
        }
        $config = [
            'is_show'=> $config['is_show'] ?? 1,
            'price' => $config['mine']['pool']['price'] ?: '',
            'imgs' => $config['mine']['pool']['imgs'] ?: '',
            'one' => isset($config['node']['one']['rate']) ? $config['node']['one']['rate'] : 0,
            'two' => isset($config['node']['two']['rate']) ? $config['node']['two']['rate'] :0,
            'is_open' => isset($config['is_open']) ? $config['is_open'] : 2,
            'sb_people' => isset($config['sb'])?$config['sb']['people'] :0,
            'sb_day' => isset($config['sb'])?$config['sb']['day'] :0,
            'sb_baoshi' => isset($config['sb'])?$config['sb']['baoshi'] :0,
            'sb_unsetNum' => isset($config['sb'])?$config['sb']['unsetNum'] :0,
            'sb_cityNum' => isset($config['sb'])?$config['sb']['cityNum'] :0,
            'sb_cityTotal' => isset($config['sb'])?$config['sb']['cityTotal'] :0,
        ];
        return $this->success($config);
    }

    public function ceshi(){

    }

    //水晶产能配置
    public function mineOutputConfig()
    {
        //统计所有用户总数量
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $mineUserRepository = $this->app->make(MineUserRepository::class);

        $outputConfig = web_config($this->request->companyId, 'program.food_output');
        $nowRate = $outputConfig['now_rate'];
        unset($outputConfig['now_rate']);

        $count = [
            'total_food' => $usersRepository->search([], $this->request->companyId)->sum('food'),
            'lock_food' => $mineUserRepository->search([],$this->request->companyId)->sum('day_rate'),
            'config' => $outputConfig,
            'now_rate' => $nowRate
        ];

        return $this->success($count);
    }
}