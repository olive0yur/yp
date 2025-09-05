<?php

namespace app\common\model\users;

use app\common\model\avata\AvataUserModel;
use app\common\model\BaseModel;
use app\common\model\cochain\CaoTianUserModel;
use app\common\model\cochain\EbaoUserModel;
use app\common\model\ddc\ta\TaUserModel;
use app\common\model\forum\ForumModel;
use app\common\model\forum\ForumZanModel;
use app\common\model\mine\MineUserModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\tichain\TichainUserModel;
use app\common\model\wallet\HuifuUserModel;
use app\common\model\wallet\LianUserModel;
use app\common\model\wallet\SandUserModel;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;

class UsersModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'users';
    }



    public function tjrInfo()
    {
        return $this->hasOne(UsersPushModel::class, 'user_id', 'id')->where('levels', 1);
    }

    public function avatars()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'head_file_id');
    }

    public function getPersonalAuthAttr()
    {
        return $this->cert()->value('cert_status');
    }

    public function cert()
    {
        return $this->hasOne(UsersCertModel::class, 'id', 'cert_id');
    }

    public function pool()
    {
        return $this->hasMany(UsersPoolModel::class, 'uuid', 'id');
    }


    public function getZtNumAttr()
    {
        return UsersPushModel::where('parent_id', $this->id)->where('levels', 1)->count();
    }

    /**
     * 直推有效用户
     * @return \think\model\relation\HasOne
     */
    public function getZtValidNumAttr()
    {
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);

        return $usersRepository->getUserZtValidnNum($this->id);
    }

    public function pList(){
        return $this->hasMany(MineUserModel::class,'uuid','id');
    }


    public function getQueueAttr($v,$data){
       /** @var UsersPushRepository $usersPushRepository */
       $usersPushRepository = app()->make(UsersPushRepository::class);
       $parent_id = $usersPushRepository->search(['user_id'=>$data['id'],'levels'=>1],$data['company_id'])->value('parent_id');
       if($parent_id){
           $parent  =  app()->make(UsersRepository::class)->search([],$data['company_id'])->where(['id'=>$parent_id])->field('id,nickname,head_file_id,user_code')->with(['avatars'=>function($query){
               $query->bind(['avatar'=>'show_src']);
           }])->find();
           return [
               'nickname'=>$parent['nickname'],
               'avatar'=>isset($parent['avatar']) && $parent['avatar'] ? $parent['avatar'] :'',
               'user_code'=>$parent['user_code']
           ];
       }else{
           return [
               'nickname'=>'',
               'avatar'=>'',
               'user_code'=>''
           ];
       }
    }

}
