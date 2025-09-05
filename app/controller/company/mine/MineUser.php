<?php

namespace app\controller\company\mine;

use think\App;
use think\facade\Cache;
use app\controller\company\Base;
use app\common\repositories\users\UsersRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;

class MineUser extends Base
{
    protected $repository;

    public function __construct(App $app, MineUserRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'is_type' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('mine/user/list', [
            'importFileAuth' => company_auth('companyGiveUserMineBatch'),
            'delAuth' => company_auth('companyUserMineDel'),
        ]);
    }

    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '删除会员权益卡包 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

    /**
     * 空投用户卡包
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function giveUserCardPack()
    {
        $id = (array)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'card_pack_id' => '',
                'num' => '',
                'remark' => ''
            ]);
            if ($param['num'] <= 0) {
                return $this->error('空投数量必须大于0');
            }
            $data = $this->repository->batchGiveUserCardPack($id,$param);
            company_user_log(3, $param['remark']. '批量空投权益卡包 id:' . implode(',', $id), $param);
            return $this->success('投送成功');
        } else {
            /**
             * @var MineRepository $cardPackRepository
             */
            $cardPackRepository = app()->make(MineRepository::class);
            $cardPackData = $cardPackRepository->getCascaderData($this->request->companyId);
            return $this->fetch('mine/user/give', [
                'cardPackData' => $cardPackData,
            ]);
        }
    }

    /**
     * 导入excel
     */
    public function giveUserCardPackBatch()
    {
        $files = $this->request->file();
        validate(['file' => 'fileSize:102400|fileExt:xls,xlsx'])->check($files);
        $file = $files['file'] ?? null;
        if (!$file) {
            return $this->error('请上传文件');
        }
        $filePath = $file->getPathName();
        //载入excel文件
        $excel = \PHPExcel_IOFactory::load($filePath);
        //读取第一张表
        $sheet = $excel->getSheet(0);
        //获取总行数
        $row_num = $sheet->getHighestRow();
        //获取总列数
        $col_num = $sheet->getHighestColumn();
        $import_data = []; //数组形式获取表格数据
        for ($i = 2; $i <= $row_num; $i++) {
            $orderNo = trim($sheet->getCell("A" . $i)->getValue());
            $deviceNo = trim($sheet->getCell("B" . $i)->getValue());
            if ($orderNo && $deviceNo) {
                $import_data[] = [
                    'mobile' => trim($sheet->getCell("A" . $i)->getValue()),
                    'num' => force_to_string(trim($sheet->getCell("B" . $i)->getValue())),
                    'card_pack_id' => force_to_string(trim($sheet->getCell("C" . $i)->getValue())),
                ];
            }
        }
        $num = 0;
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        foreach ($import_data as $k => $v) {
            $userInfo = $usersRepository->getSearch([]) ->where(['mobile'=>$v['mobile'],'company_id'=>$this->request->companyId])->find();
            if(!$userInfo){
                continue;
            }
            if ($v['num'] <= 0) {
                continue;
            }
            $param['card_pack_id'] = $v['card_pack_id'];
            $param['num'] = $v['num'];
            $this->repository->giveUserCardPackInfo($userInfo,$param);
            $num++;
        }
        return $this->success('成功投放' . $num . '个用户');
    }

}