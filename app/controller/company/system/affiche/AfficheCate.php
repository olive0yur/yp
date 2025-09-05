<?php

namespace app\controller\company\system\affiche;

use think\App;
use app\controller\company\Base;
use think\exception\ValidateException;
use app\validate\system\affiche\AfficheCateValidate;
use app\common\repositories\system\affiche\AfficheCateRepository;

class AfficheCate extends Base
{
    protected $repository;

    public function __construct(App $app, AfficheCateRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    public function list()
    {
        if($this->request->isAjax()) {
            $where = $this->request ->param([
                'keywords' => ''
            ]);
            [$page,$limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data([ 'code' => 0,  'data' => $data['list'], 'count' => $data['count'] ]);
        }
        return $this->fetch('system/affiche/cate/list', [
            'addAuth' => company_auth('companySystemAfficheCateAdd'),
            'editAuth' => company_auth('companySystemAfficheCateEdit'),
            'delAuth' => company_auth('companySystemAfficheCateDel'),
            'switchStatusAuth' => company_auth('companySystemAfficheCateSwitch'),
        ]);
    }

    public function add()
    {
        if($this->request->isPost()) {
            $param = $this->request->param([
                'name' => '',
                'keywords' => '',
                'desc' => '',
                'is_show' => '',
                'sort' => '',
            ]);
            try {
                validate(AfficheCateValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if($res) {
                    return $this->success('添加成功');
                }else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        }
        return $this->fetch('system/affiche/cate/add');
    }

    public function edit()
    {
        $id = (int)$this->request->param('id');
        if(!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->get($id);
        if(!$info){
            return $this->error('信息错误');
        }
        if($this->request->isPost()) {
            $param = $this->request->param([
                'name' => '',
                'keywords' => '',
                'desc' => '',
                'is_show' => '',
                'sort' => '',
            ]);
            try {
                validate(AfficheCateValidate::class)->scene('edit')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $res = $this->repository->editInfo($id, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
        return $this->fetch('system/affiche/cate/edit',[
            'info' => $info
        ]);
    }

    /**
     * 公告显示开关
     */
    public function switchStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('is_show', 0) == 1 ? 1 :0;

        $res = $this->repository->switchStatus($id, $status);
        if($res) {
            return $this->success('修改成功');
        }else{
            return $this->error('修改失败');
        }
    }

    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->delete($ids);
            if($data) {
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}