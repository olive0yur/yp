<?php

namespace app\controller\company\box;

use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\company\Base;
use app\validate\pool\SaleValidate;
use think\App;
use think\facade\Cache;

class UserBox extends Base
{
    protected $repository;

    public function __construct(App $app, UsersBoxRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }
    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'mobile'=>'',
                'title'=>'',
                'status'=>1,
                'type'=>'',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('box/user/list', [
            'importFileAuth' => company_auth('companyGiveUserBoxSaleBatch'),
        ]);
    }

    /**
     * 空投用户肓盒
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function giveUserBox()
    {
        $id = (array)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'box_id' => '',
                'num' => '',
                'remark' => ''
            ]);
            if ($param['num'] <= 0) {
                return $this->error('空投数量必须大于0');
            }
            if ($param['num'] >= 500) {
                return $this->error('空投数量过大');
            }
            $data = $this->repository->batchGiveUserBox($id,$param);
            company_user_log(3, '批量空投肓盒 id:' . implode(',', $id), $param);
            return $this->success('投送成功');
        } else {
            /**
             * @var BoxSaleRepository $boxSaleRepository
             */
            $boxSaleRepository = app()->make(BoxSaleRepository::class);
            $boxData = $boxSaleRepository->getCascaderData($this->request->companyId);
            return $this->fetch('box/user/give', [
                'boxData' => $boxData,
            ]);
        }
    }

    /**
     * 导入excel
     */
    public function giveUserBoxBatch()
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
                    'box_id' => force_to_string(trim($sheet->getCell("C" . $i)->getValue())),
                ];
            }
        }
        $num = 0;
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        foreach ($import_data as $k => $v) {
            $userInfo = $usersRepository->getSearch([],$this->request->companyId)->where('mobile', $v['mobile'])->find();
            if(!$userInfo){
                continue;
            }
            if ($v['num'] <= 0) {
                continue;
            }
            $param['box_id'] = $v['box_id'];
            $param['num'] = $v['num'];
            $this->repository->giveUserBoxInfo($userInfo,$param);
            $num++;
        }
        return $this->success('成功投放' . $num . '个用户');
    }
}