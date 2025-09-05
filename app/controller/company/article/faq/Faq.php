<?php

namespace app\controller\company\article\faq;

use think\App;
use app\controller\company\Base;
use think\exception\ValidateException;
use app\validate\article\faq\FaqValidate;
use app\common\repositories\article\faq\FaqRepository;
use app\common\repositories\article\faq\FaqCateRepository;


class Faq extends Base
{
    protected $repository;

    public function __construct(App $app, FaqRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
		$this->assign([
			'addAuth' => company_auth('companyArticleFaqAdd'),##
			'editAuth' => company_auth('companyArticleFaqEdit'),##
			'delAuth' => company_auth('companyArticleFaqDel'),##
            'switchStatusAuth' => company_auth('companyArticleFaqTop'),##
            'placedTopAuth' => company_auth('companyArticleFaqShow'),##
		]);
    }

    protected function commonParams()
    {
        /**
         * @var FaqCateRepository $faqCateRepository
         */
        $faqCateRepository = app()->make(FaqCateRepository::class);
        $cateData = $faqCateRepository->getCateData($this->request->companyId,1);
        $this->assign([
            'cateData' => $cateData,
        ]);
    }

    public function list()
    {
        if($this->request->isAjax()) {
            $where = $this->request ->param([
                'keywords' => ''
            ]);
            [$page,$limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0,'data' => $data['list'],'count' => $data['count']]);
        }
        return $this->fetch('article/faq/list');
    }

    /**
     * 添加常见问题
     */
    public function add()
    {
        if($this->request->isPost()){
            $param = $this->request->param([
                'title' => '',
                'picture' => '',
                'keywords' => '',
                'is_show' => '',
                'is_top' => '',
                'content' => '',
                'cate_id' => '',
                'sort' => ''
            ]);
            try {
                validate(FaqValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $param['content'] = htmlspecialchars($param['content']);
                $res = $this->repository->addInfo($this->request->companyId,$param);
                company_user_log(4, '添加常见问题', $param);
                if($res) {
                    return $this->success('添加成功');
                } else{
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        } else {
            $this->commonParams();
            return $this->fetch('article/faq/add');
        }
    }

    /**
     * 编辑常见问题
     */

     public function edit()
     {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'sort' => '',
                'title' => '',
                'picture' => '',
                'is_show' => '',
                'is_top' => '',
                'content' => '',
                'cate_id' => '',
            ]);
            try {
                validate(FaqValidate::class)->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $param['content'] = htmlspecialchars($param['content']);
                $res = $this->repository->editInfo($id, $param,$info);
                if ($res) {
                    company_user_log(4, '编辑常见问题 id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            $this->commonParams();
            return $this->fetch('article/faq/edit', [
                'info' => $info,
            ]);
        }
     }

    /**
     * 删除常见问题
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '删除常见问题 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

    /**
     * 常见问题显示开关 
     */
    public function switchStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status', 2) == 1 ? 1 : 2;
        $res = $this->repository->switchStatus($id, $status);
        if ($res) {
            company_user_log(4, '修改常见问题状态 id:' . $id, [
                'status' => $status
            ]);
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }

    /**
     * 常见问题显示置顶开关 
     */
    public function placedTop()
    {
        $id = $this->request->param('id');
        $placedTop = $this->request->param('statusTop', 2) == 1 ? 1 : 2;
        $res = $this->repository->placedTop($id, $placedTop);
        if ($res) {
            company_user_log(4, '修改常见问题置顶 id:' . $id, [
                'statusTop' => $placedTop
            ]);
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }

}