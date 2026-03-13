<?php

namespace App\Core;

/**
 * 配置管理类
 */
class Config
{
    private static $configs = [];

    /**
     * 加载配置文件
     */
    public static function load($configFile)
    {
        if (!file_exists($configFile)) {
            throw new \Exception("配置文件不存在: " . $configFile);
        }
        
        $config = require $configFile;
        self::$configs = array_merge(self::$configs, $config);
    }

    /**
     * 获取配置项
     */
    public static function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$configs;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    /**
     * 设置配置项
     */
    public static function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &self::$configs;
        
        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
}