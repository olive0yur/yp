<?php

namespace app\common\repositories\system\sms;

use app\common\dao\system\sms\SmsTemplateDao;
use app\common\repositories\BaseRepository;

/**
 * Class SmsTemplateRepository
 *
 * @package app\common\repositories\system\sms
 * @mixin SmsTemplateDao
 */
class SmsTemplateRepository extends BaseRepository
{

    public function __construct(SmsTemplateDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, int $companyId = null, int $page = null, int $limit = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->when($page !== null, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->select();
        foreach ($list as $k => $v) {
            $list[$k]['sms_type'] = $this->templateType($v['sms_type'])['name'] ?? '';
        }
        return compact('count', 'list');
    }

    /**
     * 模板类型类型
     *
     * @param $type
     * @return array|string[]|\string[][]
     */
    public function templateType($type = null)
    {
        if ($type !== null) {
            return config('sms.template_type.' . $type);
        } else {
            return config('sms.template_type');
        }
    }

    public function getTemplateList(int $companyId = null)
    {
        $query = $this->dao->search([
            'status' => 1
        ], $companyId);
        $list = $query->field('sms_type,template_id,content')->select();

        return $list;
    }

    /**
     * 保存
     *
     * @param int $companyId
     * @param array $data
     * @param int $id
     * @return \app\common\dao\BaseDao|int|\think\Model
     * @throws \think\db\exception\DbException
     */
    public function save(int $companyId, array $data, int $id = 0)
    {
        if ($id > 0) {
            $res = $this->dao->update($id, $data);
        } else {
            $data['company_id'] = $companyId;
            $res = $this->dao->create($data);
        }
        return $res;
    }
}