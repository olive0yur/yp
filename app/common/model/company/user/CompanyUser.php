<?php
namespace app\common\model\company\user;

use app\common\model\BaseModel;
use app\common\repositories\company\user\CompanyUserRepository;

class CompanyUser extends BaseModel{

    protected $autoWriteTimestamp = true;
    protected $createTime = "add_time";
    protected $updateTime = false;

    public static function tablePk(): ?string
    {
        return "id";
    }

    public static function tableName(): string
    {
        return "company_user";
    }

    // 修改器
    public function setPasswordAttr($value, $array)
    {
        /**
         * @var CompanyUserRepository $repository
         */
        $repository = app()->make(CompanyUserRepository::class);
        if (!empty($value)) {
            $password = $repository->passwordEncrypt($value);
            $this->set('password', $password);
        } else {
            $this->offsetUnset('password');
        }
    }
}