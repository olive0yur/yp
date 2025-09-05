<?php

namespace app\controller\api\guild;

use app\common\model\system\PaymentModel;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\guild\GuildWareLogRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\system\PaymentRepository;
use think\App;
use think\Exception;
use think\facade\Cache;
use app\controller\api\Base;
use app\common\repositories\pool\PoolFlowRepository;
use app\common\repositories\mark\UsersMarkBuyRepository;
use app\common\repositories\mark\UsersMarkSellRepository;
use \app\common\repositories\users\UsersPoolRepository;
use think\facade\Db;
use think\facade\Log;
use think\Request;

class Guild extends Base
{

    public function getInfo(GuildRepository $repository){
        return $this->success($repository->getApiDetail($this->request->userInfo(),$this->request->companyId));
    }

    /**
     * 创建公会
     */
    public function createGuild(GuildRepository $repository)
    {
        $data = $this->request->param(['guild_name'=>'','type'=>1,'guild_mark'=>'','guild_image'=>'']);
        return $this->success($repository->createGuide($data,$this->request->userInfo(),$this->request->companyId));
    }

    /**
     * 公会信息以及公会成员排名
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function guildList(GuildRepository $repository)
    {
        return $this->success($repository->guildList($this->request->userInfo(),$this->request->companyId));
    }

    /**
     * 加入公会
     */
    public function addGuild(GuildRepository $repository)
    {
            $data = $this->request->param(['guild_id'=>'']);
            if (!isset($data['guild_id'])) return $this->error('公会id不能为空！');
            return $this->success($repository->addGuide($data,$this->request->userInfo(),$this->request->companyId));
    }

    /**
     * 升级公会人数  公会的id
     * level
     */
    public function upgradeGuild(GuildRepository $repository)
    {
       return $this->success($repository->upgradeGuild($this->request->userInfo(),$this->request->companyId));
    }

    /**
     *  删除公会成员
     */
    public function deleteGuildMember(GuildMemberRepository $repositorye)
    {
        $data = $this->request->param(['id'=>'']);
        return $this->success($repositorye->delMember($data,$this->request->userInfo(),$this->request->companyId));
    }

    public function getUserList(GuildMemberRepository $repository){
         [$page,$limit] = $this->getPage();
         return $this->success($repository->getUserList($page,$limit,$this->request->userInfo(),$this->request->companyId));
    }

    public function wareHourse(GuildWareHouseRepository $repository){
        [$page,$limit] = $this->getPage();
        return $this->success($repository->getApiList($page,$limit,$this->request->userInfo(),$this->request->companyId));
    }

    public function send(GuildWareHouseRepository $repository)
    {
       $data = $this->request->param(['ware_id'=>'','num'=>'','user_code'=>'','pay_password'=>'']);
        if(!$data['ware_id']) return $this->error('请输入仓库id');
        if(!$data['num']) return $this->error('请输入数量');
        if(!$data['user_code']) return $this->error('请输入接收人');
        return $this->success($repository->send($data,$this->request->userInfo(),$this->request->companyId));
    }

    public function getGivLog(GuildWareLogRepository $repository){
        [$page,$limit] = $this->getPage();
        return $this->success($repository->getApiList($page,$limit,$this->request->userInfo(),$this->request->companyId));
    }

}