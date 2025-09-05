<?php

namespace app\common\model\users\user;
use app\common\model\BaseModel;

class AgentAuthRule extends BaseModel{
    public static function tablePk(): ?string
    {
        return "id";
    }

    public static function tableName(): string
    {
        return "agent_auth_rule";
    }

    public function searchIdAttr($query,$value){
        if(is_array($value)){
            $query->where('id','in',$value);
        }else{
            $query->where('id','=',(int)$value);
        }
    }
}