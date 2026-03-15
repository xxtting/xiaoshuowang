<?php

namespace App\Core;

/**
 * 请求处理类
 */
class Request
{
    private $method;
    private $uri;
    private $headers;
    private $params;
    private $body;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $this->parseUri();
        $this->headers = $this->getAllHeaders();
        $this->params = $this->parseParams();
        $this->body = $this->parseBody();
    }

    /**
     * 解析URI
     */
    private function parseUri()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // 去除查询字符串
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        return $uri;
    }

    /**
     * 获取所有请求头
     */
    private function getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            }
        }
        
        return $headers;
    }

    /**
     * 解析请求参数
     */
    private function parseParams()
    {
        $params = [];
        
        // GET参数
        if (!empty($_GET)) {
            $params = array_merge($params, $_GET);
        }
        
        // POST参数
        if (!empty($_POST)) {
            $params = array_merge($params, $_POST);
        }
        
        return $params;
    }

    /**
     * 解析请求体
     */
    private function parseBody()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $rawBody = file_get_contents('php://input');
        
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode($rawBody, true) ?? [];
        }
        
        return [];
    }

    /**
     * 获取请求方法
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 获取请求URI
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * 获取请求头
     */
    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * 获取参数
     */
    public function getParam($name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * 获取请求体数据
     */
    public function getBody($name = null, $default = null)
    {
        if ($name === null) {
            return $this->body;
        }
        
        return $this->body[$name] ?? $default;
    }

    /**
     * 获取客户端IP
     */
    public function getClientIp()
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
              $_SERVER['HTTP_X_REAL_IP'] ?? 
              $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        return explode(',', $ip)[0];
    }

    /**
     * 获取用户代理
     */
    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}