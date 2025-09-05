<?php

namespace app\controller\company\active\syn;

use app\common\repositories\active\syn\ActiveSynUserRepository;
use app\common\repositories\active\syn\ActiveSynRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\controller\company\Base;
use think\App;
use app\common\repositories\users\UsersRepository;

class SynUser extends Base
{


    protected $repository;

    public function __construct(App $app, ActiveSynUserRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function commons(){
        $get = $this->request->get();
        if(!$get['syn_id']) return $this->error('请选择合成');
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'syn_id'=>''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
    }


    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'syn_id' => '',
                'mobile'=>'',
            ]);
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('active/syn/user/add');
        }
    }



    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                admin_log(4, '删除合成 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

    public function import()
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
                    'mobile' => force_to_string(trim($sheet->getCell("A" . $i)->getValue())),
                    'syn_id' => force_to_string(trim($sheet->getCell("B" . $i)->getValue())),
                ];
            }
        }
        $num = 0;
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $datas = [];
        foreach ($import_data as $k => $v) {
            $userInfo = $usersRepository->getSearch([]) ->where('mobile', $v['mobile'])->find();
            if(!$userInfo){
                continue;
            }
            $datas[$k]['uuid'] = $userInfo['id'];
            $datas[$k]['syn_id'] = $v['syn_id'];
            $num++;
        }
        if($datas && $num > 0){
            $this->repository->addAll($datas);
        }
        return $this->success('成功导入' . $num . '个用户');
    }


}