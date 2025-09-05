<?php

namespace app\controller\company\system;

use app\common\repositories\system\ConfigRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\controller\company\Base;
use think\App;

class Upload extends Base
{
    protected $repository;

    public function __construct(App $app, UploadFileRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 上传设置
     *
     * @return string|\think\response\Json
     * @throws \Exception
     */
    public function uploadConfig()
    {
        /**
         * @var ConfigRepository $configRepository
         */
        $configRepository = app()->make(ConfigRepository::class);
        if ($this->request->isPost()) {
            $configRepository->modifyConfig('upload', $this->request->post(), $this->request->companyId);
            company_user_log(2, '设置上传配置', $this->request->post());
            return json()->data([ 'code' => 0,'msg' => '修改成功']);
        } else {
            return $this->fetch('system/upload/config', [
                'info' => web_config($this->request->companyId, 'upload')
            ]);
        }
    }

    /**
     * 上传文件列表
     *
     * @return string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function uploadFileList()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'storage_type' => '',
                'time' => ''
            ]);
            [$page, $limit] = $this->getPage(1, 15);
            $res = $this->repository->getList($where, '*', $page, $limit, $this->request->companyId);
            $list = $res['list'];
            foreach ($list as $k => $v) {
                $list[$k]['file_size'] = format_bytes($v['file_size']);
                $list[$k]['storage_type'] = $this->repository::STORAGE_TYPE[$v['storage_type']] ?? '';
                $source = $v['source'] == 'admin' ? '管理员' : '用户';
                $list[$k]['source'] = $source;
            }
            return json()->data(['code' => 0,   'data' => $list, 'count' => $res['count']  ]);
        } else {
            return $this->fetch('system/upload/upload_file_list', [
                'delAuth' => company_auth('companyDeleteUploadFile'),
                'storageType' => $this->repository::STORAGE_TYPE
            ]);
        }
    }

    /**
     * 删除上传文件
     *
     * @return \think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delUploadFile()
    {
        $ids = (array)$this->request->param('ids');
        $ids = array_filter($ids);
        if (empty($ids)) {
            return $this->error('参数错误');
        }

        $fileList = $this->repository->selectWhere([
            ['id', 'in', $ids]
        ]);
        if (empty($fileList)) {
            return $this->error('图片不存在');
        }

        $num = 0;
        $upload = new \api\upload\Upload();
        $successIds = [];
        foreach ($fileList as $k => $v) {
            $upload->setDriver($v['storage_type'] ?? 'local');
            $res = $upload->deleteFile($v['upload_path']);
            if ($res) {
                $v->delete();
                $num++;
                $successIds[] = $v['id'];
            }
        }
        if ($num > 0) {
            return $this->success('成功删除' . $num . '个文件');
        } else {
            return $this->error('删除失败');
        }
    }
}