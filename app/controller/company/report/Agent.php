<?php

namespace app\controller\company\report;

use app\common\repositories\agent\AgentRepository;
use think\App;
use app\controller\company\Base;

class Agent extends Base
{
    protected $repository;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }


    public function list(AgentRepository $repository)
    {
        if ($this->request->isAjax()) {
            $charts = $this->request->param('charts');
            $reg_time = $this->request->param('reg_time');
            $where = [];
            //设置查询字段默认条件
            $field = "DATE_FORMAT(create_time, '%Y-%m-%d') AS day,count(id) as total";
            //设置本周周一时间
            $timestamp = strtotime('Monday this week');
            //判断是否是自定义区间查询
            if ($reg_time){
                $where['reg_time'] = str_replace("+"," ",str_replace('%3A',':',$reg_time));
            }else if ($charts){
                //判断是否特定条件查询
                switch ($charts){
                    case 1:
                        $where['create_time'] = date('Y-m-d 00:00:00',$timestamp) . ' - ' . date("Y-m-d H:i:s");
                        break;
                    case 2:
                        $where['create_time'] = date('Y-m-01 00:00:00') . ' - ' . date("Y-m-d H:i:s");
                        break;
                    case 3:
                        $where['create_time'] = date('Y-01-01 00:00:00') . ' - ' . date("Y-m-d H:i:s");
                        $field = "DATE_FORMAT(create_time, '%Y-%m') AS day,count(id) as total";
                        break;
                }
            }else{
                $where['create_time'] =date('Y-m-d 00:00:00',$timestamp) . ' - ' . date("Y-m-d H:i:s");
            }

            $list = $repository->search($where)->field($field)->group('day')->select();
            return json()->data(['code' => 0, 'data' => $list]);
        } else {
            return $this->fetch('report/agent/list');
        }
    }
}