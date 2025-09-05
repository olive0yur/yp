<?php

namespace app\controller\api\active;

use app\common\repositories\active\syn\ActiveSynInfoRepository;
use app\common\repositories\active\syn\ActiveSynLogRepository;
use app\common\repositories\active\syn\ActiveSynRepository;
use app\controller\api\Base;
use think\App;
use think\exception\ValidateException;

class Syn extends Base
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function poolList(ActiveSynRepository $repository)
    {
        $data = $this->request->param(['with_id' => '','pool_id'=>'']);
        if (!$data['with_id']) return $this->error('with_id不能为空');
        [$page, $limit] = $this->getPage();
        return $this->success($repository->getApiPoolList($data['with_id'],$data['pool_id'], $page, $limit, $this->request->userId(), $this->request->companyId));
    }

    public function getDetail(ActiveSynRepository $repository)
    {
        $data = $this->request->param(['with_id' => '', 'id' => '']);
        if (!$data['with_id']) return $this->error('with_id不能为空');
        return $this->success($repository->getApiDetail($data, $this->request->userId(), $this->request->companyId));
    }


    public function getDetail_v1(ActiveSynRepository $repository)
    {
        $data = $this->request->param(['with_id' => '', 'id' => '']);
        if (!$data['with_id']) return $this->error('with_id不能为空');
        return $this->success($repository->getDetail_v1($data, $this->request->userId(), $this->request->companyId));
    }


    ## 选择合成
    public function syn(ActiveSynRepository $repository)
    {
        $data = $this->request->param([
            'id'=>'',
            'with_id' => '',
            'pool' => []
        ]);
        if (!$data['with_id']) return $this->error('请选择合成活动');
        if (!$data['pool']) return $this->error('请选择合成卡牌!');
        return $this->success($repository->syn($data, $this->request->userId(), $this->request->companyId), '合成成功');

    }

    public function syn_v1(ActiveSynRepository $repository)
    {
        $data = $this->request->param([
            'id'=>'',
            'with_id' => '',
            'syn' => []
        ]);
        if (!$data['with_id']) return $this->error('请选择合成活动');
        if (!$data['syn']) return $this->error('请选择合成卡牌!');
        return $this->success($repository->syn_v1($data, $this->request->userId(), $this->request->companyId), '合成成功');

    }

    ## 一健合成【批量,一次提交多份】
    public function fastSyn(ActiveSynRepository $repository)
    {
        $data = $this->request->param([
            'with_id' => '',
            'num' => 1,
            'id'=>''
        ]);
        if (!$data['with_id']) return $this->error('请选择合成活动');
        if ($data['num'] <= 0) return $this->error('请输入合成数量');
        return $this->success($repository->fastSyn($data, $this->request->userId(), $this->request->companyId), '合成成功');
    }

    ## 一健合成【批量 一次一份】
    public function fastOneSyn(ActiveSynRepository $repository)
    {
        $data = $this->request->param([
            'with_id' => '',
            'id'=>''
        ]);
        $data['num'] = 1;
        if (!$data['with_id']) return $this->error('请选择合成活动');
        return $this->success($repository->fastOneSyn($data, $this->request->userId(), $this->request->companyId), '合成成功');
    }


    public function synQueue(ActiveSynRepository $repository)
    {
        $data = $this->request->param([
            'with_id' => '',
            'id' => ''
        ]);
        $data['num'] = 1;
        if (!$data['with_id']) return $this->error('请选择合成活动');
        return $this->success($repository->fastOneSyn($data, $this->request->userId(), $this->request->companyId), '合成成功');
    }

    public function synGet(ActiveSynLogRepository $repository)
    {
        $data = $this->request->param([
            'no' => '',
        ]);
        if(!$data['no']) return $this->error('参数错误!');
        return $this->success($repository->synGet($data, $this->request->userId(), $this->request->companyId));
    }


    public function getLog(ActiveSynLogRepository $repository)
    {
        $data = $this->request->param(['limit' => '', 'page' => '', 'uuid' => $this->request->userId()]);
        return $this->success($repository->getApiList($data, $data['page'], $data['limit'], $this->request->companyId));
    }

    public function synLogInfo(ActiveSynLogRepository $repository){
        $data = $this->request->param(['id' => '', 'uuid' => $this->request->userId()]);
        return $this->success($repository->getApiDetail($data, $this->request->companyId));
    }
}