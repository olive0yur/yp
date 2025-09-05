<?php

namespace app\common\repositories\guild;

use app\common\dao\guild\GuildDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class GuideRepository
 * @package app\common\repositories\GuideRepository
 * @mixin GuildDao
 */
class GuildRepository extends BaseRepository
{

    public function __construct(GuildDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->with(['user'=>function($query){
            $query->field('id,mobile');
            $query->bind(['mobile'=>'mobile']);
        }])->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }



    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['create_time'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
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
                app()->make(GuildMemberRepository::class)->search(['guild_id'=>$v['id']])->delete();
                app()->make(GuildWareHouseRepository::class)->search(['guild_id'=>$v['id']])->delete();
                app()->make(GuildWareLogRepository::class)->search(['guild_id'=>$v['id']])->delete();
            }
            return $list;
        }
        return [];
    }

    public function createGuide($data,$userInfo,$companyId){
        if ($userInfo['is_create_guild'] == 2) throw new ValidateException('您没有创建公会的权限，请联系管理员开通！');
        if ($data['type'] == 1)
        {

            $guide = $this->dao->search(['uuid'=>$userInfo['id']],$companyId)->find();
            if($guide) throw new ValidateException('禁止重复创建公会!');

            /** @var GuildConfigRepository $configRepository */
            /** @var GuildMemberRepository $memberRepository */
            $configRepository =app()->make(GuildConfigRepository::class);
            $memberRepository = app()->make(GuildMemberRepository::class);

            $isGuide = $memberRepository->search(['uuid'=>$userInfo['id']],$companyId)->find();
            if($isGuide) throw new ValidateException('请先退出其他公会！');

            $config = $configRepository->search(['status'=>1,'level'=>1],$companyId)->find();
            if(!$config) throw new ValidateException('公会配置未完善！');

            $data['uuid'] = $userInfo['id'];
            $data['guild_person'] = $config['people'];   //获取配置的人数

            $guideInfo = $this->addInfo($companyId,$data);
            if ($guideInfo)
            {
                $mem['guild_id'] = $guideInfo['id'];
                $mem['uuid'] = $userInfo['id'];
                $memberRepository->addInfo($companyId,$mem);
                return $guideInfo;
            }
            throw new ValidateException('创建失败!');
        }else if ($data['type'] == 2)
        {
            $guide = $this->dao->search(['uuid'=>$userInfo['id']],$companyId)->find();
            if(!$guide) throw new ValidateException('公会不存在！');
            unset($data['type']);
            return $this->editInfo($guide,$data);
        }
    }

    public function addGuide($data,$userInfo,$companyId){
        $guildInfo = $this->getDetail($data['guild_id']);
        if(!$guildInfo) throw new ValidateException('公会不存在');
        $configRepository = app()->make(GuildConfigRepository::class);
        $config = $configRepository->search(['level'=>$guildInfo['level'],'status'])->find();
        if(!$config) throw new ValidateException('公会配置未完善!');
        $memberRepository =  app()->make(GuildMemberRepository::class);
        $memJoin = $memberRepository->search(['uuid'=>$userInfo['id']])->find();
        if($memJoin) throw new ValidateException('您已经加入了公会,禁止重复加入公会!');

        $memberCount = $memberRepository->search(['guild_id'=>$guildInfo['id']])->count('id');
        if ($memberCount >= $config['people']) throw new ValidateException('公会人数已满!');

        $mem['guild_id'] = $guildInfo['id'];
        $mem['uuid'] = $userInfo['id'];
        if($memberRepository->addInfo($companyId,$mem)) return true;
        throw new ValidateException("网络错误，加入失败!");
    }

    public function guildList($userInfo,$companyId=0){
        $guildList = $this->search([],$companyId)->order('level desc')->select();
        $member = app()->make(GuildMemberRepository::class);
        foreach ($guildList as $key => $value)
        {
            $status = $member->search(['guild_id'=>$value['id'],'uuid'=>$userInfo['id']])->find();
            if ($status)
            {
                $guildList[$key]['status'] = 1;
            }else
            {
                $guildList[$key]['status'] = 2;
            }
            $guildList[$key]['people'] = app()->make(GuildConfigRepository::class)->search(['level'=>$value['level']],$companyId)->value('people');
            $uuids = $member->search(['guild_id'=>$value['id']],$companyId)->column('uuid');
            $guildList[$key]['guildCount'] = count($uuids);
            //计算公会里面人员的总产量
            $guildList[$key]['totalFood'] =app()->make(UsersRepository::class)->search([],$companyId)->whereIn('id',$uuids)->sum('food');     //计算公会里面人员的产量
        }
        array_multisort($guildList->toArray(), SORT_ASC);
        $param['guildList'] = $guildList;
        return $param;
    }

    public function upgradeGuild($userInfo,$companyId){
         $guideInfo = $this->dao->search(['uuid'=>$userInfo['id']],$companyId)->find();
         if(!$guideInfo) throw new ValidateException('您不是公会创始人，无法升级!');

         /** @var  GuildConfigRepository $guideConfigRepository */
         $guideConfigRepository = app()->make(GuildConfigRepository::class);

         $conf = $guideConfigRepository->search(['level'=>($guideInfo['level']+1),'status'=>1],$companyId)->find();
         if(!$conf) throw new ValidateException('您的公会已经是最高等级');
         $tokens = web_config($companyId, 'site')['tokens'];
         if(!$tokens) throw new ValidateException('矿产产出未配置!');
         if($conf['gold'] > $userInfo['food']) throw new ValidateException('您的'.$tokens.'不足');
        return Db::transaction(function () use ($userInfo,$conf,$guideInfo,$companyId) {
             app()->make(UsersRepository::class)->foodChange($userInfo['id'], 3, '-' . $conf['gold'], ['remark' => '升级公会', 'company_id' => $companyId], 4);
             $this->editInfo($guideInfo,['level'=>($guideInfo['level']+1)]);
            return true;
        });
    }

    public function getApiDetail($userInfo,$companyId)
    {
        $guild = app()->make(GuildMemberRepository::class)
            ->search([],$companyId)
            ->where(['uuid'=>$userInfo['id']])
            ->find();
        if($guild){
            $data = $this->dao->search([],$companyId)
                ->where('id',$guild['guild_id'])
                ->withAttr('rate',function ($v,$data) use($companyId){
                    return app()->make(GuildConfigRepository::class)->search(['level'=>$data['level']],$companyId)->value('rate');
                })
                ->withAttr('gold',function ($v,$data) use($companyId){
                return app()->make(GuildConfigRepository::class)->search(['level'=>$data['level']],$companyId)->value('gold');
            })
                ->append(['rate','gold'])
                ->find();
            return $data;
        }
        return [];
    }


}