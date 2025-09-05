<?php

namespace app\traits;

trait CategoryRepository
{
    protected $needLevel = true;

    public function getPathById(int $id)
    {
        if ($this->dao->getPathById($id))
            $path = $this->dao->getPathById($id) . $id . '/';
        return $path ?? '/';
    }

    public function getLevelById(int $id)
    {
        $level = 1;
        if (($parentLevel = $this->dao->getLevelById($id)) !== null)
            $level = $parentLevel + 1;
        return $level;
    }

    /**
     * 提交的pid 是否等于当前pid
     * @param int $id
     * @param int $pid
     */
    public function checkUpdate(int $id, int $pid)
    {
        return (($this->dao->get($id))['pid'] == $pid) ? true : false;
    }

    /**
     * 添加分类
     *
     * @param $data
     * @return \app\common\dao\BaseDao|\think\Model
     */
    public function create($data)
    {
        $this->needLevel && $data['level'] = $this->getLevelById($data['pid']);
        $data['path'] = $this->getPathById($data['pid']);

        return $this->dao->create($data);
    }

    /**
     * 编辑分类
     *
     * @param $id
     * @param $data
     * @throws \think\db\exception\DbException
     */
    public function update($id, $data)
    {
        if ($this->checkUpdate($id, $data['pid'])) {
            $this->dao->update($id, $data);
        } else {
            $data['path'] = $this->getPathById($data['pid']);
            $data['level'] = $this->getLevelById($data['pid']);

            $data['change'] = [
                'oldPath' => $this->dao->getPathById($id) . $id . '/',
                'newPath' => $data['path'] . $id . '/',
                'changeLevel' => $this->changeLevel($id, $data['pid']),
            ];
            $this->dao->updateParent($id, $data);
        }
        return true;
    }

    /**
     * 检测 是否修改到 子集
     *
     * @param int $id
     * @param int $pid
     * @return bool
     */
    public function checkChangeToChild(int $id, int $pid)
    {
        return ($this->dao->checkChangeToChild($id, $pid) > 0) ? false : true;
    }

    /**
     * 编辑时 子集是否 超过最低限制
     * @param int $id
     * @param int $pid
     * @param int $maxLevel 最大层级
     * @return bool
     */
    public function checkChildLevel(int $id, int $pid, $maxLevel = 4)
    {
        $childLevel = max($this->dao->getChildLevelById($id)); //1
        $changLevel = $childLevel + ($this->changeLevel($id, $pid)); //2
        return $changLevel < $maxLevel;
    }

    /**
     * 变动等级差
     * @param int $id
     * @param int $pid
     * @return int|mixed
     *  0->1  1-> 2  2-> 3
     *  3->2  2-> 1  1-> 0
     */
    public function changeLevel(int $id, int $pid)
    {
        return ($this->dao->getLevelById($pid) + 1) - ($this->dao->getLevelById($id));
    }

    /**
     * 子集是否存在
     * @param int $id
     * @return bool
     */
    public function hasChild(int $id)
    {
        return (($this->dao->hasChild($id)) > 0) ? true : false;
    }
}