<?php

namespace app\traits;

trait CategoryDao
{

    /**
     * 查询修改目标是否为自己到子集
     * @param int $id
     * @param int $pid
     */
    public function checkChangeToChild(int $id, int $pid)
    {
        return ($this->getModel()::getDB())
            ->where('path', 'like', $this->getPathById($id) . $id . '/%')
            ->where($this->getPk(), $pid)
            ->count();
    }

    /**
     *  获取子集 等级 数组
     * @param $id
     * @return array
     */
    public function getChildLevelById($id)
    {
        $level = ($this->getModel()::getDB())->where('path', 'like', $this->getPathById($id) . $id . '/%')->column('level');
        return (is_array($level) && !empty($level)) ? $level : [0];
    }


    /**
     * 根据ID获取分类层级
     *
     * @param int $id 分类ID
     * @return mixed
     */
    public function getLevelById($id)
    {
        return ($this->getModel())::getDB()
            ->where($this->getPk(), $id)
            ->value('level');
    }

    /**
     * 根据ID获取分类path
     *
     * @param int $id 分类ID
     * @return mixed
     */
    public function getPathById($id)
    {
        return ($this->getModel())::getDB()
            ->where($this->getPk(), $id)
            ->value('path');
    }

    /**
     * 编辑
     * @param int $id
     * @param array $data
     */
    public function updateParent(int $id, array $data)
    {
        ($this->getModel()::getDB())->transaction(function () use ($id, $data) {
            $change = $data['change'];
            unset($data['change']);
            ($this->getModel()::getDB())->where($this->getPk(), $id)->update($data);

            $this->updateChild($change['oldPath'], $change['newPath'], $change['changeLevel']);
        });
    }

    /**
     * 修改子类
     * @param string $oldPath
     * @param string $newPath
     * @param $changeLevel
     */
    public function updateChild(string $oldPath, string $newPath, $changeLevel)
    {
        $query = ($this->getModel()::getDB())->where('path', 'like', $oldPath . '%')
            ->select();
        if ($query) {
            $query->each(function ($item) use ($oldPath, $newPath, $changeLevel) {
                $child = ($this->getModel()::getDB())->find($item[$this->getPk()]);
                $child->path = str_replace($oldPath, $newPath, $child->path);
                $child->level = $child->level + $changeLevel;
                $child->save();
            });
        }
    }

    /**
     * 是否存在子集
     * @param int $id
     * @return mixed
     */
    public function hasChild(int $id)
    {
        return ($this->getModel()::getDB())->where('path', 'like', $this->getPathById($id) . $id . '/%')->count();
    }

    /**
     * 获取所有子集ID
     * @param int $id
     * @return mixed
     */
    public function getChildIds(int $id)
    {
        return ($this->getModel()::getDB())->where('path', 'like', $this->getPathById($id) . $id . '/%')->column('id');
    }

    /**
     * 修改状态
     *
     * @param int $id
     * @param int $status
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function switchStatus(int $id, int $status)
    {
        return ($this->getModel()::getDB())->where($this->getPk(), $id)->update([
            'is_show' => $status
        ]);
    }
}