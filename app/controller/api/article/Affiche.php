<?php

namespace app\controller\api\article;

use app\common\repositories\system\affiche\AfficheCateRepository;
use app\common\repositories\system\affiche\AfficheFollowRepository;
use app\common\repositories\system\affiche\AfficheRepository;
use app\controller\api\Base;
use think\App;
use think\facade\Cache;
use think\facade\Db;

class Affiche extends Base
{
    protected $repository;

    public function __construct(App $app, AfficheRepository $repository,AfficheCateRepository $cateRepository,AfficheFollowRepository $followRepository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->cateRepository = $cateRepository;
        $this->followRepository = $followRepository;
    }

    /**
     * 置顶公告列表
     */
    public function getTopAfficheList()
    {
        $where = $this->request->param(['is_show' => 1, 'is_top' => 1]);
        return app('api_return')->success($this->repository->getTopList($where, $this->request->companyId));
    }

    /**
     * 公告列表
     */
    public function afficheList()
    {
        $where = $this->request->param(['keywords', 'is_show' => 1]);
        [$page, $limit] = $this->getPage();
        return app('api_return')->success($this->repository->getApiList($where, $page, $limit, $this->request->companyId));
    }

    /**
     * 公告详情
     */
    public function afficheDetail($id)
    { 
        $res = $this->repository->getApiDetail($id);
        if($res) {
            return app('api_return')->success($res);
        } else {
            return app('api_return')->error('数据不存在');
        }
    }


    public function getCate(){
        return $this->success($this->cateRepository->getAll($this->request->companyId,1));
    }
    public function getList(){
        $data = $this->request->param([
            'cate_id' => '',
            'keywords' => '',
        ]);
        $data['is_show'] = 1;
        [$page, $limit] = $this->getPage();
        return $this->success($this->repository->getApiList($data,$page, $limit,$this->request->companyId));
    }

    /**
     * 129功能新添加按钮
     * @return mixed
     */
    public function getList1(){
        $data = $this->request->param([
            'cate_id' => '',
            'keywords' => '',
            'uuid' => $this->request->userId(),
        ]);
        $data['is_show'] = 1;
        [$page, $limit] = $this->getPage();
        return $this->success($this->repository->getApiList1($data,$page, $limit,$this->request->companyId));
    }

    /**
     * 获取角标数量
     * @return mixed
     */
    public function getnums()
    {
        $data = [
            'uuid' =>$this->request->userId(),
        ];
        return $this->success($this->repository->getApinums($data,$this->request->companyId));
    }

    /**
     *
     */
    public function decNums()
    {
        $uuid = $this->request->userId();
        $getNums = Cache::store('redis')->get('count_'.$uuid);
        if ($getNums > 0)
        {
            $nums = Cache::store('redis')->dec('count_'.$uuid,1);
        }else
        {
            $nums = 0;
        }
        if($nums <= 0)
        {
             Cache::store('redis')->set('count_'.$uuid,0);
        }
        return $this->success($nums,'清除完成！');
    }

    public function getDetail(){
        $data = $this->request->param(['id'=>'']);
        if(!$data) return $this->error('请选择公告');
        return $this->success($this->repository->getApiDetail($data['id'],$this->request->userId()?:0,$this->request->companyId));
    }

    public function getNew(){
        return $this->success($this->repository->getApiNew([],$this->request->companyId));
    }
    /**
     * 取消关注公告
     * @return bool
     */
    public function follow()
    {
        $data = $this->request->param(['id'=>'']);
        return $this->success($this->followRepository->follow($data,$this->request->userId(),$this->request->companyId));
    }

    /**
     * 我关注公告
     * @return bool
     */
    public function getLog(){
        $data['uuid'] = $this->request->userId();
        [$page, $limit] = $this->getPage();
        return $this->success($this->followRepository->getApiList($data,$page,$limit,$this->request->companyId));
    }



 /*   public function */
}