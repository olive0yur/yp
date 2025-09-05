<?php

namespace app\controller\admin\system\affiche;

use think\App;
use app\controller\admin\Base;
use think\exception\ValidateException;
use app\validate\system\affiche\AfficheValidate;
use app\common\repositories\system\affiche\AfficheRepository;
use app\common\repositories\system\affiche\AfficheCateRepository;

class Affiche extends Base
{
    protected $repository;

    public function __construct(App $app, AfficheRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    protected function commonParams()
    {
        /**
         * @var AfficheCateRepository $afficheCateRepository
         */
        $afficheCateRepository = app()->make(AfficheCateRepository::class);
        $cateData = $afficheCateRepository->getCateData(0,1);
        $this->assign([
            'cateData' => $cateData,
        ]);
    }

    /**
     * 公告列表
     */
    public function list()
    {
        if($this->request->isAjax()) {
            $where = $this->request ->param([
                'keywords' => ''
            ]);
            [$page,$limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,0);
            return json()->data([ 'code' => 0, 'data' => $data['list'], 'count' => $data['count'] ]);
        }
        return $this->fetch('system/affiche/list', [
            'addAuth' => admin_auth('adminSystemAfficheAdd'),
            'editAuth' => admin_auth('adminSystemAfficheEdit'),
            'delAuth' => admin_auth('adminSystemAfficheDel'),
            'switchStatusAuth' => admin_auth('adminSystemAfficheSwitch'),
            'placedTopAuth' => admin_auth('adminSystemAfficheTop')
        ]);
    }

    /**
     * 添加公告
     */
    public function add()
    {
        if($this->request->isPost()) {
            $param = $this->request->param([
                'cate_id' => '',
                'title' => '',
                'is_show' => '',
                'is_top' => '',
                'picture' => '',
                'content' => ''
            ]);
            try {
                validate(AfficheValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $param['content'] = htmlspecialchars($param['content']);
                $res = $this->repository->addInfo(0,$param);
                if($res) {
                    return $this->success('添加成功');
                }else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        }else{
            $this->commonParams();
            return $this->fetch('system/affiche/add');
        }

    }

    /**
     * 编辑公告
     */
    public function edit()
    {
        $id = (int)$this->request->param('id');
        if(!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if(!$info){
            return $this->error('信息错误');
        }
        if($this->request->isPost()) {
            $param = $this->request->param([
                'cate_id' => '',
                'title' => '',
                'is_show' => '',
                'is_top' => '',
                'picture' => '',
                'content' => '',
            ]);
            try {
                validate(AfficheValidate::class)->scene('edit')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $param['content'] = htmlspecialchars($param['content']);
                $res = $this->repository->editInfo($id, $param,$info);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误'.$e->getMessage());
            }
        }else{
            $this->commonParams();
            return $this->fetch('system/affiche/edit',[
                'info' => $info
            ]);
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

    /**
     * 公告显示开关
     */
    public function switchStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status', 0) == 1 ? 1 :0;

        $res = $this->repository->switchStatus($id, $status);
        if($res) {
            return $this->success('修改成功');
        }else{
            return $this->error('修改失败');
        }
    }

    /**
     * 公告置顶开关
     */
    public function placedTop()
    {
        $id = $this->request->param('id');
        $statusTop = $this->request->param('statusTop', 0) == 1 ? 1 :0;

        $res = $this->repository->placedTop($id, $statusTop);
        if($res) {
            return $this->success('修改成功');
        }else{
            return $this->error('修改失败');
        }
    }
}