<?php

namespace app\controller\api;

use api\upload\exception\UploadException;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\services\UploadService;

class Upload extends Base
{
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
            $upload = UploadService::init($this->request->companyId, $dir);
            $res = $upload->uploadImageFile($field);
            $fileInfo = $res['data']['fileInfo'];
            $groupId = $this->request->param('group_id', 0, 'intval');
            $img = getimagesize($fileInfo['show_src']);
            $data = [
                'group_id' => $groupId,
                'show_src' => $fileInfo['show_src'],
                'upload_path' => $fileInfo['upload_path'],
                'storage_type' => $fileInfo['storage_type'],
                'file_md5' => $fileInfo['file_md5'],
                'file_size' => $fileInfo['file_size'],
                'file_type' => $fileInfo['file_type'],
                'file_name' => $fileInfo['file_name'],
                'source' => 'api',
                'admin_id' => 0,
                'uuid' => $this->request->userId(),
                'company_id' => $this->request->companyId,
                'width' => isset($img) && $img ? $img['0'] : '',
                'height' => isset($img) && $img ? $img['1'] : '',
            ];
            /**
             * @var UploadFileRepository $uploadFileRepository
             */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $up = $uploadFileRepository->create($data);
            return json()->data([
                'code' => 0,
                'msg' => '上传成功',
                'data' => [
                    'id' => $up['id'],
                    'src' => $res['data']['src']
                ]
            ]);
        } catch (UploadException $e) {
            return json()->data(['code' => -1, 'msg' => $e->getMessage()]);
        }
    }
}