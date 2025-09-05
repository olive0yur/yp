<?php

namespace app\helper;

/**
 * 临时数据
 *
 * @author gkdos
 */
class TempData
{
    private static $tempData = [];

    /**
     * 获取临时值
     *
     * @param string $name 名称
     * @return void
     * @author gkdos
     */
    public static function get($name)
    {
        return self::$tempData[$name] ?? null;
    }

    /**
     * 设置临时值
     *
     * @param string $name 名称
     * @param string $value 值
     * @return void
     * @author gkdos
     */
    public static function set($name, $value)
    {
        return self::$tempData[$name] = $value;
    }

    /**
     * 检测值存不存在
     *
     * @param string $name 名称
     * @return boolean
     * @author gkdos
     */
    public static function has($name)
    {
        return isset(self::$tempData[$name]);
    }

    /**
     * 获取或设置值
     *
     * @param string $name 名称
     * @param mixed $value 值
     * @return void
     * @author gkdos
     */
    public static function remember($name, $value)
    {
        if (!self::has($name)) {

            if ($value instanceof \Closure) {
                self::set($name, call_user_func_array($value, []));
            } else {
                self::set($name, $value);
            }
        }

        return self::get($name);
    }
}
