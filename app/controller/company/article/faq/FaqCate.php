<?php

namespace app\controller\company\article\faq;

use think\App;
use think\exception\ValidateException;
use app\controller\company\Base;
use app\validate\article\faq\FaqCateValidate;
use app\common\repositories\article\faq\FaqCateRepository;

class FaqCate extends Base
{
    protected $repository;

    public function __construct(App $app, FaqCateRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
		$this->assign([
		    'addAuth' => company_auth('companyArticleFaqCateAdd'),##添加操作指南分类
		    'editAuth' => company_auth('companyArticleFaqCateEdit'),##编辑操作指南分类
		    'delAuth' => company_auth('companyArticleFaqCateDel'),##删除操作指南分类
		]);
    }

	public function list()
	{
	    if($this->request->isAjax()) {
	        $where = $this->request->param(['keywords' => '']);
	        [$page, $limit] = $this->getPage();
	        $res = $this->repository->getList($where, $page, $limit, $this->request->companyId);
	        return json()->data(['code' => 0, 'data' => $res['list'],'count' => $res['count']]);
	    }
	    return $this->fetch('article/faq/cate/list');
	}

	public function add()
	{
	    if($this->request->isPost()) {
            $param = $this->request->param([
                'name' => '',
                'is_show' => '',
                'keywords' => '',
                'desc' => '',
                'sort' => ''
            ]);
	        try {
	            validate(FaqCateValidate::class)->scene('add')->check($param);
	        } catch (ValidateException $e) {
	            return json()->data($e->getError());
	        }
            if ($this->repository->companyFieldExists($this->request->companyId, 'name', $param['name'])) {
                return $this->error($param['name'].' 分类名已存在');
            }
	        try{
	            $res = $this->repository->addInfo($this->request->companyId,$param);
	            company_user_log(4, '添加常见问题分类', $param);
	            if($res) {
	                return $this->success('添加成功');
	            } else{
	                return $this->error('添加失败');
	            }
	        } catch (\Exception $e) {
	            return $this->error('网络失败');
	        }
	    }
	    return $this->fetch('article/faq/cate/add');
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
                'id' => '',
                'name' => '',
                'is_show' => '',
                'keywords' => '',
                'desc' => '',
                'sort' => '',
            ]);
	        try {
	            validate(FaqCateValidate::class)->check($param);
	        } catch (ValidateException $e) {
	            return json()->data($e->getError());
	        }
	        try {
                $res = $this->repository->editInfo($id, $param);
	            if($res) {
	                company_user_log(4, '编辑常见问题分类 id:' . $info['id'], $param);
	                return $this->success('修改成功');
	            }else{
	                return $this->error('修改失败');
	            }
	        } catch (\Exception $e) {
	            return $this->error('网络错误');
	        }
	    } else {
	        return $this->fetch('article/faq/cate/edit',[
	            'info' => $info
	        ]);
	    }
	}

	public function del()
	{
	    $ids = (array)$this->request->param('ids');
	    if(!$ids){
	        return $this->error('参数错误');
	    }
	    $info = $this->repository->get($ids);
	    if(!$info){
	        return $this->error('信息错误');
	    }
	    try {
	        $data = $this->repository->batchDelete($ids);
	        if($data) {
	            company_user_log(4, '删除常见问题分类 ids:' . implode(',', $ids), $data);
	            return $this->success('删除成功');
	        } else {
	            return $this->error('删除失败');
	        }
	    } catch (\Exception $e) {
	        return $this->error('网络失败');
	    }
	}
}