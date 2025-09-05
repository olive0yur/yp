<?php

namespace app\controller\api\system;

use app\common\repositories\system\AppVersionRepository;
use app\controller\api\Base;
use think\App;

class Version extends Base
{
    protected $repository;
    protected $cateRepository;

    public function __construct(App $app, AppVersionRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 检测更新
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkUpdate()
    {
        $version = $this->request->param('app_version');
        $os = $this->request->param('os');

        $info = $this->repository->getCurrentVersion($os);
        if (!$info || ($info['download_url'] == '' && $info['apk_url'] == '')) {
            return $this->successText('暂无更新');
        }

        $version = str_replace('.', '', $version);
        if ($version >= $info['version_code']) {
            return $this->successText('暂无更新');
        }

        return $this->success([
            'apk_download_url' => $info['apk_url'],
            'download_url' => $info['download_url'],
            'force' => $info['force_update'] == 1,
            'desc' => $info['desc']
        ], '有新版本');
    }

    /**
     * 获取升级日志
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUpgradeLog()
    {
        $where = $this->request->param([
            'os'
        ]);

        [$page, $limit] = $this->getPage();

        $res = $this->repository->getUpgradeLog($where, $page, $limit);

        return $this->success($res);
    }
}