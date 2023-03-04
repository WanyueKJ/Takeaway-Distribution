<?php

namespace App\enum;

abstract class BaseEnum
{
    /**
     * 根据值，获取常量名，失败返回null.
     *
     * @param mixed $value
     *
     * @return string|null
     */
    public static function getName($value)
    {
        $map = static::getAllConstants();
        $key = array_search($value, $map);

        return $key ?? null;
    }

    /**
     * 获取值
     *
     * @param string $name
     *
     * @return mixed
     */
    public static function getValue($name)
    {
        return \constant(static::class . '::' . $name);
    }

    /**
     * 获取文本.
     *
     * @param mixed $value
     *
     * @return string|null
     */
    public static function getText($value)
    {

    }


    /**
     * 获取所有名称.
     *
     * @return string[]
     */
    public static function getNames()
    {
        $map =  static::getAllConstants();
        return array_keys($map) ?? [];
    }

    /**
     * 获取所有值
     *
     * @return array
     */
    public static function getValues()
    {
        $map =  static::getAllConstants();
        return array_values($map) ?? [];
    }

    /**
     * 获取键值对应数组.
     *
     * @return array
     */
    public static function getMap()
    {
        return static::getAllConstants();
    }

    /**
     * 获取类的所有常量
     * @return array
     */
    protected static function getAllConstants()
    {
        $rcs = new \ReflectionClass(static::class);
        return $rcs->getConstants();
    }
}
