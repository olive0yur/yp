<?php

namespace app\controller\game\game;

use app\common\repositories\users\UsersRepository;
use app\controller\api\Base;

class Kill extends Base
{

    public function getUserInfo(UsersRepository $repository){
        $data = $repository->getGameDetail($this->request->userInfo(),$this->request->companyId);
        return json(['code'=>1,'message'=>'','data'=>$data]);
    }


    public function getbalance(UsersRepository $repository)
    {
        $param = $this->request->param(['uuid'=>'']);
        $data = $repository->getGameBalance($param);
        return json(['code'=>1,'message'=>'','data'=>$data]);
    }

    public function decbalance(UsersRepository $repository)
    {
        $param = $this->request->param(['requestNo'=>'','userId'=>'','coinType'=>'','gameName'=>'','changePoints'=>'','gameDate'=>'','batch_no'=>'']);
        if(!$param['coinType']) return json(['code'=>400,'message'=>'类型错误！']);
        if(!$param['changePoints'] || $param['changePoints']  <= 0) return json(['code'=>400,'message'=>'投注金额错误！']);

        $data = $repository->decbalance($param);
        return json(['code'=>1,'message'=>'投入成功','data'=>$data]);
    }

    public function test(UsersRepository $repository)
    {
        $param = array (
            'requestNo' => '',
            'coinType' => '余额',
            'gameName' => '大逃杀',
            'changePoints' => 117.05,
            'inComeDetails' =>
                array (
                    0 =>
                        array (
                            'changePoints' => '18.809000',
                            'userId' => 181,
                        ),
                ),
            'batch_no' => 33185,
        );
        if(!$param['coinType']) return json(['code'=>400,'message'=>'类型错误！']);
        if(!is_array($param['inComeDetails'])) return json(['code'=>400,'message'=>'参数【inComeDetails】类型错误']);
        $data = $repository->incbalance($param);
        return json(['code'=>1,'message'=>'','data'=>$data]);
    }
    
    public function incbalance(UsersRepository $repository)
    {
        $param = $this->request->param(['requestNo'=>'','coinType'=>'','gameName'=>'','changePoints'=>'','inComeDetails'=>'','batch_no'=>'']);
        file_put_contents('win.txt',var_export($param,true));
        if(!$param['coinType']) return json(['code'=>400,'message'=>'类型错误！']);
        if(!is_array($param['inComeDetails'])) return json(['code'=>400,'message'=>'参数【inComeDetails】类型错误']);
        $data = $repository->incbalance($param);
        return json(['code'=>1,'message'=>'','data'=>$data]);
    }


}