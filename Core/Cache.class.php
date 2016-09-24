<?php
namespace Aren\Core;
//缓存类
class Cache
{
    protected $cache = NULL;

    public function __construct()
    {
        $cacheDriver = '\Aren\Driver\\'.Config::get('CACHE');
        $this->cache = new $cacheDriver();
    }

    //读取缓存
    public function get($key)
    {
        return $this->cache->get($key);
    }

    //设置缓存,默认永不过期
    public function set($key, $value, $expire = -1)
    {
        return $this->cache->set($key, $value, $expire);
    }

    //自增1
    public function inc($key, $value = 1)
    {
        return $this->cache->inc($key, $value);
    }

    //自减1
    public function des($key, $value = 1)
    {
        return $this->cache->des($key, $value);
    }

    //删除
    public function del($key)
    {
        return $this->cache->del($key);
    }

    //清空缓存
    public function clear()
    {
        return $this->cache->clear();
    }
}