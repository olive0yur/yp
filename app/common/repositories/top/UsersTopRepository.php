<?php

namespace app\common\repositories\top;

use app\common\dao\top\UsersTopDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\givLog\MineGivLogRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class UsersTopRepository
 * @package app\common\repositories\top
 * @mixin UsersTopDao
 */
class UsersTopRepository extends BaseRepository
{

    public function __construct(UsersTopDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->with(['users'=>function($query){
            $query->bind(['user_code','nickname','regist_ip','mobile','food']);
        }])->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }



    public function getApiList($top_id,$page, $limit, $companyId = null)
    {
        $query = $this->dao->search(['top_id'=>$top_id], $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'users'=>function($query){
                     $query->field('id,user_code,head_file_id,top_name,nickname,qq,wechat')->with(['avatars'=>function($query){
                         $query->bind(['avatar'=>'show_src']);
                     }]);
                }
            ])
            ->order('id asc')
            ->select();
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $top = $usersRepository->search([],$companyId)->where('id',$top_id)->field('top_name,top_remark')->find();
        $user = $usersRepository->search([],$companyId)->where('id',$top_id)->field('id,user_code,head_file_id,top_name,nickname,qq,wechat')->with(['avatars'=>function($query){
            $query->bind(['avatar'=>'show_src']);
        }])->find();
        return compact('count', 'list','top','user');
    }

    public function getApiDetail(int $id)
    {
        $data = $this->dao->search([])
            ->with([
                'file' => function ($query) {
                    $query->bind(['cover' => 'show_src']);
                },
                'headInfo' => function ($query) {
                    $query->bind(['head_img' => 'show_src']);
                }
            ])
            ->where('id', $id)
            ->field('id,name,file_id,content,head_file_id')
            ->find();

        return $data;
    }

    /**
     * 删除
     */
    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);

        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['add_at'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->with([
                'file' => function ($query) {
                    $query->bind(['cover' => 'show_src']);
                },
                'headInfo' => function ($query) {
                    $query->bind(['head_img' => 'show_src']);
                }

            ])
            ->hidden(['file'])
            ->where('id', $id)
            ->find();

        return $data;
    }



    public function addTop($data,$userInfo,$companyId){

        if($userInfo['is_top'] != 1) throw new ValidateException('请先成为大玩家!');
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $user = $usersRepository->search(['user_code'=>$data['user_code']],$companyId)->find();
        if(!$user) throw new ValidateException('您输入的用户不存在！');
        if($userInfo['user_code'] == $user['user_code']) throw new ValidateException('禁止添加自己!');
        if($user['is_top'] == 1) throw new ValidateException('您无法把别的盟主添加到您的名下');
        $top = $this->dao->search(['uuid'=>$user['id']])->find();
        if($top) throw new ValidateException('您输入的用户已经加入联盟!');
        $config =  web_config($companyId,'program.top');
        if(!$config) throw new ValidateException('参数设置未完善!');
        $count = $this->dao->search(['top_id'=>$userInfo['id']])->count('id');
        if($count >= ($config['elder'] + $userInfo['add_num'])) throw new ValidateException('您的长老名额已用完,请新增名额');
        $arr['uuid'] = $user['id'];
        $arr['top_id'] = $userInfo['id'];
        return $this->addInfo($companyId,$arr);
    }
    public function addNum($userInfo,$companyId){
        try {
            if($userInfo['is_top']!=1) throw new ValidateException('请先成为大玩家!');
            $top = web_config($companyId,'program.top','');
            if(!$top) throw new ValidateException('参数配置未完善!');
            if($top['added'] <=0) throw new ValidateException('配置错误!');
            return Db::transaction(function () use ($userInfo,$top,$companyId) {
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $user = $usersRepository->search([])->where(['id'=>$userInfo['id']])->lock(true)->find();
                $re =$usersRepository->search([], $companyId)->where('id',$user['id'])->where('food', '>=', $top['added'])->dec('food', $top['added'])->update();
                if($re){
                    $usersRepository->search([], $companyId)->where('id',$user['id'])->inc('add_num',1)->update();
                    /** @var UsersFoodLogRepository $usersFoodLogRepository */
                    $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
                    $data['user_id'] = $userInfo['id'];
                    $data['amount'] = $top['added'];
                    $data['before_change'] = $userInfo['food'];
                    $data['after_change'] = $userInfo['food'] - $top['added'];
                    $data['log_type'] = 3;
                    $data['remark'] = '增加长老名额';
                    $data['track_port'] = 4;
                    return $usersFoodLogRepository->addInfo($companyId,$data);
                }

            });
        }catch (\Exception $exception){
            throw new ValidateException($exception->getMessage());
        }
    }


    public function getMyTopList($page,$limit,$userInfo,$companyId){
            $query = $this->dao->search(['top_id'=>$userInfo['id']], $companyId);
            $count = $query->count();
            $list = $query->page($page, $limit)
                ->with([
                    'users'=>function($query){
                        $query->field('id,company_id,user_code,head_file_id,top_name,nickname,qq,wechat,food')
                            ->with(['avatars'=>function($query){
                            $query->bind(['avatar'=>'show_src']);
                        }])->append(['library'])
                        ;
                    }
                ])
                ->order('id desc')
                ->select();
        $top = web_config($companyId,'program.top');
        $total = $top['elder'] + $userInfo['add_num'];
        $num = $this->dao->search(['top_id'=>$userInfo['id']],$companyId)->count('id');
        $residue = $total - $num;
        return compact('count', 'list','residue','total');
    }

    public function getTopMsg($top_id,$companyId){
        $ids = $this->dao->search(['top_id'=>$top_id],$companyId)->column('uuid');
        $uuids = array_merge($ids,[$top_id]);
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $total = $usersRepository->search([],$companyId)->whereIn('id',$uuids)->sum('food');
        /** @var MineGivLogRepository $mineGivLogRepository */
        $mineGivLogRepository = app()->make(MineGivLogRepository::class);
        $out_num = $mineGivLogRepository->search([],$companyId)->whereIn('uuid',$uuids)->where('type',1)
            ->whereDay("create_at")->group('order_sn')->count('id');
        $out_total = $mineGivLogRepository->search([],$companyId)->whereIn('uuid',$uuids)->where('type',1)->whereDay("create_at")->sum('num');
        $in_num = $mineGivLogRepository->search([],$companyId)->whereIn('uuid',$uuids)->where('type',2)
            ->whereDay("create_at")->group('order_sn')->count('id');
        $in_total = $mineGivLogRepository->search([],$companyId)->whereIn('uuid',$uuids)->where('type',2)->whereDay("create_at")->sum('num');

        $out = $mineGivLogRepository->search([],$companyId)->whereIn('uuid',$uuids)->where('type',1)->sum('num');
        $in = $mineGivLogRepository->search([],$companyId)->whereIn('uuid',$uuids)->where('type',2)->sum('num');


        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $top = $usersRepository->search([],$companyId)->where('id',$top_id)->field('top_remark,top_name')->find();

        $add_num = $usersRepository->search([],$companyId)->where('id',$top_id)->value('add_num');
        $added = web_config($companyId,'program.top.added');
        $zhi = $add_num * $added;
        return compact('total','out_num','out_total','in_num','in_total','top','out','in','zhi');
    }

    public function getApiTopList($user_code,$top_id,$page,$limit,$companyId){
        $uuids = $this->dao->search(['top_id'=>$top_id],$companyId)->column('uuid');
        /** @var MineGivLogRepository $mineGivLogRepository */
        $mineGivLogRepository = app()->make(MineGivLogRepository::class);
        $query = $mineGivLogRepository->search(['user_code'=>$user_code],$companyId)
            ->whereIn('uuid',$uuids);
        $count = $query->count();
        $list = $query->with(['userMain'=>function($query){
            $query->field('id,nickname,head_file_id,user_code')->with(['avatars'=>function($query){
                $query->bind(['avatar'=>'show_src']);
            }]);
        }])->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }
}