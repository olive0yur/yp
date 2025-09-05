<?php

namespace app\controller\company;

use api\upload\exception\UploadException;
use app\common\repositories\system\upload\UploadFileGroupRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\services\UploadService;

class Upload extends Base
{

    /**
     * 选择上传文件
     *
     * @param UploadFileGroupRepository $repository
     * @return string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function selectUploadFile(UploadFileGroupRepository $repository)
    {
        if ($this->request->isAjax()) {
            $groupList = $repository->getCascadesData($this->request->companyId, 0);
            return json()->data(['code' => 0, 'data' => $groupList]);
        }
        return $this->fetch('upload/select_upload_file');
    }

    /**
     * 添加上传文件组
     *
     * @param UploadFileGroupRepository $repository
     * @return string|\think\response\Json|\think\response\View|void
     */
    public function addUploadFileGroup(UploadFileGroupRepository $repository)
    {
        if ($this->request->isPost()) {
            $name = $this->request->param('name', '', 'trim');
            if (empty($name)) {
                return json()->data(['code' => -1, 'msg' => '参数错误']);
            }

            $pid = (int)$this->request->param('pid', 0);
            if ($pid && !$repository->exists($pid)) {
                return $this->error('上级分类不存在');
            }
            $data = [
                'group_name' => $name,
                'company_id' => $this->request->companyId,
                'pid' => $pid
            ];
            $res = $repository->create($data);
            if ($res) {
                return json()->data(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $res->id]]);
            } else {
                return json()->data(['code' => -1, 'msg' => '添加失败']);
            }
        }
    }

    /**
     * 删除上传文件组
     *
     * @param UploadFileGroupRepository $repository
     * @return string|\think\response\Json|\think\response\View|void
     * @throws \think\db\exception\DbException
     */
    public function deleteUploadFileGroup(UploadFileGroupRepository $repository)
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            if ($id <= 0) {
                return json()->data(['code' => -1, 'msg' => '参数错误']);
            }
            if (!$repository->exists($id)) {
                return $this->error('数据不存在');
            }
            if ($repository->hasChild($id)) {
                return $this->error('该分组存在子分组，请先处理子分组');
            }
            $info = $repository->get($id);
            $res = $repository->delete($id);
            if ($res) {
                /**
                 * @var UploadFileRepository $uploadFileRepository
                 */
                $uploadFileRepository = app()->make(UploadFileRepository::class);
                $uploadFileRepository->whereUpdate([
                    'group_id' => $id
                ], [
                    'group_id' => $info['pid']
                ]);
                return json()->data(['code' => 0, 'msg' => '删除成功']);
            } else {
                return json()->data(['code' => -1, 'msg' => '删除失败']);
            }
        }
    }

    /**
     * 编辑上传文件组
     *
     * @param UploadFileGroupRepository $repository
     * @return \think\response\Json|void
     * @throws \think\db\exception\DbException
     */
    public function editUploadFileGroup(UploadFileGroupRepository $repository)
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            $name = $this->request->param('name', '', 'trim');
            if (empty($name) || $id <= 0) {
                return json()->data(['code' => -1, 'msg' => '参数错误']);
            }
            $data = [
                'group_name' => $name
            ];
            $res = $repository->editInfo($id, $data);
            if ($res) {
                return json()->data(['code' => 0, 'msg' => '编辑成功']);
            } else {
                return json()->data(['code' => -1, 'msg' => '编辑失败']);
            }
        }
    }

    /**
     * 获取上传的图片列表
     *
     * @return void
     */
    public function getUploadImageList()
    {
        $where = $this->request->param([
            'group_id' => -1
        ]);
        $where['source'] = 'company';
        $where['file_type'] = ['image/png', 'image/jpeg', 'image/gif', 'image/jpg', 'video/mp4', 'image/webp', 'applicatio', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $where['uuid'] = 0;
        /**
         * @var UploadFileRepository $uploadFileRepository
         */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        [$page, $limit] = $this->getPage(1, 15);
        $res = $uploadFileRepository->getList($where, 'id,show_src file_path,file_name name,file_type', $page, $limit, $this->request->companyId);
        return json()->data(['code' => 0, 'data' => $res['list'], 'count' => $res['count']]);
    }

    /**
     * 删除上传的文件
     *
     * @return void
     */
    public function deleteUploadFile()
    {
        $id = (array)$this->request->param('id');
        foreach ($id as $k => $v) {
            $v = intval($v);
            if ($v <= 0) {
                unset($id[$k]);
                continue;
            }
            $id[$k] = $v;
        }

        if (empty($id)) {
            return json()->data(['code' => -1, 'msg' => '参数错误']);
        }
        /**
         * @var UploadFileRepository $uploadFileRepository
         */
        $uploadFileRepository = app()->make(UploadFileRepository::class);

        $list = $uploadFileRepository->selectWhere([
            ['id', 'in', $id],
            ['company_id', '=', $this->request->companyId]
        ]);

        $num = 0;
        $upload = UploadService::init($this->request->companyId);
        $successIds = [];
        foreach ($list as $k => $v) {
            $upload->setDriver($v['storage_type'] ?? 'local');
//            $res = $upload->deleteFile($v['upload_path']);
//            if ($res) {
                $v->delete();
                $num++;
                $successIds[] = $v['id'];
//            }
        }
        if ($num > 0) {
            return json()->data(['code' => 0, 'msg' => '成功删除' . $num . '个文件', 'data' => ['id' => $successIds]]);
        } else {
            return json()->data(['code' => -1, 'msg' => '删除失败']);
        }
    }

    /**
     * 移动上传上传的文件组
     *
     * @return void
     */
    public function moveUploadFileGroup()
    {
        $id = (array)$this->request->param('id');
        foreach ($id as $k => $v) {
            $v = intval($v);
            if ($v <= 0) {
                unset($id[$k]);
                continue;
            }
            $id[$k] = $v;
        }
        $groupId = $this->request->param('group_id', 0, 'intval');
        $groupId = $groupId <= 0 ? 0 : $groupId;

        if (empty($id)) {
            return json()->data(['code' => -1, 'msg' => '参数错误']);
        }
        /**
         * @var UploadFileRepository $uploadFileRepository
         */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        $res = $uploadFileRepository->whereUpdate([
            ['id', 'in', $id]
        ], [
            'group_id' => $groupId
        ]);

        if ($res) {
            return json()->data(['code' => 0, 'msg' => '移动成功']);
        } else {
            return json()->data(['code' => -1, 'msg' => '移动失败']);
        }
    }

    /**
     * 上传图片
     *
     * @return \think\response\Json
     */
    public function uploadImage()
    {
        $field = ($this->request->param('field') ? $this->request->param('field') : 'file');
        $dir = ($this->request->param('dir') ? $this->request->param('dir') : 'home');

        try {

            $type = pathinfo($_FILES['file']['name']);
            $type = strtolower($type["extension"]);

            if ($type == 'xlsx' || $type == 'csv') {
                $upload = UploadService::init($this->request->companyId, $dir, 'local');
            } else {
                $upload = UploadService::init($this->request->companyId, $dir);
            }

            $res = $upload->uploadImageFile($field);
            $fileInfo = $res['data']['fileInfo'];
            $groupId = $this->request->param('group_id', 0, 'intval');
            if ($type == 'xlsx' || $type == 'csv') {
                $img[0] = '';
                $img[1] = '';
            } else {
                $img = getimagesize($res['data']['tempPath']);
            }
            $data = [
                'group_id' => $groupId,
                'show_src' => $fileInfo['show_src'],
                'upload_path' => $fileInfo['upload_path'],
                'storage_type' => $fileInfo['storage_type'],
                'file_md5' => $fileInfo['file_md5'],
                'file_size' => $fileInfo['file_size'],
                'file_type' => $fileInfo['file_type'],
                'file_name' => $fileInfo['file_name'],
                'source' => 'company',
                'admin_id' => $this->request->adminId,
                'company_id' => $this->request->companyId,
                'width' => isset($img) && $img ? $img['0'] : '',
                'height' => isset($img) && $img ? $img['1'] : '',
            ];

            /**
             * @var UploadFileRepository $uploadFileRepository
             */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $uploadFileRepository->create($data);

            return json()->data([
                'code' => 0,
                'msg' => '上传成功',
                'data' => [
                    'src' => $res['data']['src']
                ]
            ]);
        } catch (UploadException $e) {
            return json()->data(['code' => -1, 'msg' => $e->getMessage()]);
        }
    }
}