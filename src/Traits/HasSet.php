<?php

namespace Ledc\RedisQueue\Traits;

use Closure;
use Illuminate\Redis\Connections\Connection;
use support\Redis;

/**
 * Redis集合
 */
trait HasSet
{
    /**
     * 集合的前缀
     * @return string
     */
    abstract public static function prefix(): string;

    /**
     * 【获取】集合的key
     * @param string $key
     * @return string
     */
    public static function getSetKey(string $key): string
    {
        return static::prefix() . $key;
    }

    /**
     * 【获取】集合的成员
     * - 如果存在，则返回字段的值
     * - 如果不存在，则调用回调函数，然后把返回值写入缓存
     * @param string $key
     * @param Closure|null $fn 获取值回调
     * @param Closure|null $refresh 是否刷新回调，回调内返回布尔值，true刷新缓存，false不刷新缓存
     * @return false|array|null
     */
    public static function sMembersOrAdd(string $key, ?Closure $fn = null, ?Closure $refresh = null): false|array|null
    {
        $members = static::connection()->sMembers(static::getSetKey($key));
        if ($members && $fn && $refresh && call_user_func($refresh, $key)) {
            // 需同时满足刷新条件：1.字段存在 2.存在回调 3.回调返回true
            goto refresh;
        }
        if (!$members && $fn) {
            refresh:
            $members = call_user_func($fn, $key);
            if (!$members) {
                return null;
            }
            static::connection()->sAdd(static::getSetKey($key), ...$members);
            return $members;
        }
        return $members ?: null;
    }

    /**
     * 从一个集合键中获取所有成员
     * @param string $key
     * @return false|array
     */
    public static function sMembers(string $key): false|array
    {
        return static::connection()->sMembers(static::getSetKey($key));
    }

    /**
     * 向集合添加一个或多个成员
     * @param string $key
     * @param mixed $value
     * @param mixed ...$values
     * @return false|int
     */
    public static function sAdd(string $key, mixed $value, mixed ...$values): false|int
    {
        return static::connection()->sAdd(static::getSetKey($key), $value, ...$values);
    }

    /**
     * 移除集合中一个或多个成员
     * @param string $key
     * @param string $value
     * @param mixed ...$values
     * @return false|int
     */
    public static function sRem(string $key, mixed $value, mixed ...$values): false|int
    {
        return static::connection()->sRem(static::getSetKey($key), $value, ...$values);
    }

    /**
     * 判断 member 元素是否是集合 key 的成员
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function sIsMember(string $key, mixed $value): bool
    {
        return static::connection()->sIsMember(static::getSetKey($key), $value);
    }

    /**
     * 获取集合的成员数
     * @return bool|int
     */
    public static function sCard(string $key): false|int
    {
        return static::connection()->sCard(static::getSetKey($key));
    }

    /**
     * 创建或者移除
     * @param string $key
     * @param mixed $value
     * @param callable $fn 返回值：true添加、false移除
     * @return void
     */
    public static function AddOrRem(string $key, mixed $value, callable $fn): void
    {
        if (call_user_func($fn, $value)) {
            static::sAdd($key, $value);
        } else {
            static::sRem($key, $value);
        }
    }

    /**
     * 获取Redis连接
     * @return Connection|\Redis
     */
    public static function connection(): Connection|\Redis
    {
        return Redis::connection();
    }
}
