<?php

namespace app\controller\company\system;

use api\sms\exception\SmsException;
use app\common\repositories\system\sms\SmsConfigRepository;
use app\common\repositories\system\sms\SmsLogRepository;
use app\common\repositories\system\sms\SmsTemplateRepository;
use app\common\services\SmsService;
use app\controller\company\Base;
use app\validate\system\SmsValidate;
use think\App;
use think\exception\ValidateException;

class Sms extends Base
{
    protected $repository;

    public function __construct(App $app, SmsConfigRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * 短信配置
     *
     * @return \think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function smsConfig()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'verify_img_code' => '',
                'send_sms_time_out' => '',
                'auth_sms_time_out' => '',
                'sms_type' => '',
                'signature' => '',
                'aliyun_access_key_id' => '',
                'aliyun_access_key_secret' => '',
                'juhe_key' => '',
                'webchinese_sms_account' => '',
                'webchinese_sms_key' => '',
                'edit_time' => time(),
                'yunmark_sms_appcode'=>'',
                'yunmark_sms_sign'=>''
            ]);
            $res = $this->repository->save($this->request->companyId,$param);
            if ($res) {
                return $this->success('设置成功');
            } else {
                return $this->error('设置失败');
            }
        } else {
            $smsConfig = $this->repository->getSmsConfig($this->request->companyId);
            return $this->fetch('system/sms/config', [
                'smsConfig' => $smsConfig
            ]);
        }
    }

    /**
     * 短信模板列表
     *
     * @return \think\response\View
     * @throws \think\db\exception\DbException
     */
    public function templateList()
    {
        if ($this->request->isAjax()) {
            /**
             * @var SmsTemplateRepository $smsTemplateRepository
             */
            $smsTemplateRepository = app()->make(SmsTemplateRepository::class);
            $where = $this->request->param([
                'keywords' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $res = $smsTemplateRepository->getList($where,$this->request->companyId, $page, $limit);
            return json()->data([ 'code' => 0,'data' => $res['list'], 'count' => $res['count']]);
        } else {
            return $this->fetch('system/sms/template_list', [
                'addAuth' => company_auth('companySmsTemplateAdd'),
                'editAuth' => company_auth('companySmsTemplateEdit'),
                'delAuth' => company_auth('companySmsTemplateDelete'),
                'testSendAuth' => company_auth('companySmsTestSend')
            ]);
        }
    }

    /**
     * 添加短信模板
     *
     * @return \think\response\Json|\think\response\View
     */
    public function addTemplate()
    {
        /**
         * @var SmsTemplateRepository $smsTemplateRepository
         */
        $smsTemplateRepository = app()->make(SmsTemplateRepository::class);
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'status' => '',
                'sms_type' => '',
                'template_id' => '',
                'content' => ''
            ]);
            try {
                validate(SmsValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            if ($smsTemplateRepository->companyFieldExists($this->request->companyId, 'sms_type', $param['sms_type'])) {
                return $this->error('此模板已存在');
            }
            $res = $smsTemplateRepository->save($this->request->companyId, $param);
            if ($res) {
                company_user_log(2, '添加短信模板', $param);
                return $this->success('添加成功');
            } else {
                return $this->error('添加失败');
            }
        } else {
            return $this->fetch('system/sms/add_template', [
                'title' => '添加短信模板',
                'templateTypes' => $smsTemplateRepository->templateType()
            ]);
        }
    }

    /**
     * 编辑短信模板
     *
     * @return \think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editTemplate()
    {
        /**
         * @var SmsTemplateRepository $smsTemplateRepository
         */
        $smsTemplateRepository = app()->make(SmsTemplateRepository::class);
        $id = (int)$this->request->param('id');
        if (!$smsTemplateRepository->companyExists($this->request->companyId, $id)) {
            return $this->error('数据不存在');
        }
        $info = $smsTemplateRepository->get($id);
        if (empty($info)) {
            return $this->error('模板信息不存在');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'status' => '',
                'sms_type' => '',
                'template_id' => '',
                'content' => '',
                'id' => ''
            ]);
            try {
                validate(SmsValidate::class)->scene('edit')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            if ($smsTemplateRepository->companyFieldExists($this->request->companyId, 'sms_type', $param['sms_type'], $id)) {
                return $this->error('此模板已存在');
            }
            $oldData = $info->toArray();
            $res = $smsTemplateRepository->save($this->request->companyId, $param, $id);
            if ($res) {
                company_user_log(2, '修改短信模板', [
                    'oldData' => $oldData,
                    'request_params' => $param
                ]);

                return $this->success('修改成功');
            } else {
                return $this->error('修改失败');
            }
        } else {
            return $this->fetch('system/sms/edit_template', [
                'title' => '修改短信模板',
                'info' => $info,
                'templateTypes' => $smsTemplateRepository->templateType()
            ]);
        }
    }

    /**
     * 删除短信模板
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delTemplate()
    {
        $id = (int)$this->request->param('id');
        /**
         * @var SmsTemplateRepository $smsTemplateRepository
         */
        $smsTemplateRepository = app()->make(SmsTemplateRepository::class);
        if (!$smsTemplateRepository->companyExists($this->request->companyId, $id)) {
            return $this->error('数据不存在');
        }
        $info = $smsTemplateRepository->get($id);
        $res = $smsTemplateRepository->delete($id);
        if ($res) {
            company_user_log(2, '删除短信模板', $info->toArray());
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败');
        }
    }

    /**
     * 模拟短信发送
     *
     * @return \think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function testSend()
    {
        $id = (int)$this->request->param('id');
        /**
         * @var SmsTemplateRepository $smsTemplateRepository
         */
        $smsTemplateRepository = app()->make(SmsTemplateRepository::class);
        if (!$smsTemplateRepository->companyExists($this->request->companyId, $id)) {
            return $this->error('数据不存在');
        }
        $info = $smsTemplateRepository->get($id);
        $phone = $this->request->param('phone');
        try {
            SmsService::init($this->request->companyId)->sendTestContent($phone, $info['sms_type']);

            return $this->success('发送成功');
        } catch (SmsException $e) {
            return $this->error($e->getMessage());
        }
    }


    /**
     * 短信日志列表
     *
     * @return string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function smsLogList()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'mobile' => '',
                'time' => ''
            ]);
            if ($time = $this->request->param('time', '', 'trim')) {
                $times = explode(' - ', $time);
                $startTime = strtotime($times[0]);
                $endTime = strtotime($times[1]);
                $where[] = ['add_time', 'between', [$startTime, $endTime]];
            }
            /**
             * @var SmsLogRepository $smsLogRepository
             */
            $smsLogRepository = app()->make(SmsLogRepository::class);
            [$page, $limit] = $this->getPage();
            $res = $smsLogRepository->getList($this->request->companyId, $where, $page, $limit);
            return json()->data(['code' => 0,  'data' => $res['list'], 'count' => $res['count']]);
            $page = $this->request->get('page', '1', 'intval');
            $pageSize = $this->request->get('limit', '10', 'intval');
            $list = SmsLogModel::where($where)
                ->page($page, $pageSize)
                ->order('id', 'desc')
                ->select();
            foreach ($list as $k => &$v) {
                $v['sms_type'] = SmsTemplateModel::$smsType[$v['sms_type']] ?? '';
                $v['valid_msg'] = $v['is_verify'] == 1 ? date('Y-m-d H:i:s', $v['valid_time']) . '已验证' : '未验证';
                $v['status'] = SmsLogModel::$status[$v['status']] ?? '';
            }

            return json()->data([
                'code' => 0,
                'data' => $list,
                'count' => SmsLogModel::where($where)->count()
            ]);
        } else {
            return $this->fetch('system/sms/sms_log_list');
        }
    }
}