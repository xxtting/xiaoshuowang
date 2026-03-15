<?php

namespace App\Core;

/**
 * 路由处理类
 */
class Router
{
    private $request;
    private $response;
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => []
    ];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * 注册GET路由
     */
    public function get($path, $handler)
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * 注册POST路由
     */
    public function post($path, $handler)
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * 注册PUT路由
     */
    public function put($path, $handler)
    {
        $this->routes['PUT'][$path] = $handler;
    }

    /**
     * 注册DELETE路由
     */
    public function delete($path, $handler)
    {
        $this->routes['DELETE'][$path] = $handler;
    }

    /**
     * 注册PATCH路由
     */
    public function patch($path, $handler)
    {
        $this->routes['PATCH'][$path] = $handler;
    }

    /**
     * 分发路由
     */
    public function dispatch()
    {
        $method = $this->request->getMethod();
        $uri = $this->request->getUri();
        
        // 检查路由是否匹配
        $handler = $this->findRoute($method, $uri);
        
        if ($handler === null) {
            $this->response->error('接口不存在', 404);
        }
        
        // 执行控制器方法
        $this->callHandler($handler);
    }

    /**
     * 查找匹配的路由
     */
    private function findRoute($method, $uri)
    {
        if (!isset($this->routes[$method])) {
            return null;
        }
        
        // 精确匹配
        if (isset($this->routes[$method][$uri])) {
            return $this->routes[$method][$uri];
        }
        
        // 参数匹配
        foreach ($this->routes[$method] as $route => $handler) {
            $pattern = $this->convertRouteToPattern($route);
            
            if (preg_match($pattern, $uri, $matches)) {
                // 提取参数
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                
                return [
                    'handler' => $handler,
                    'params' => $params
                ];
            }
        }
        
        return null;
    }

    /**
     * 将路由转换为正则模式
     */
    private function convertRouteToPattern($route)
    {
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $route);
        return '#^' . $pattern . '$#';
    }

    /**
     * 调用处理器
     */
    private function callHandler($handler)
    {
        if (is_array($handler) && isset($handler['handler'])) {
            $params = $handler['params'] ?? [];
            $handler = $handler['handler'];
        } else {
            $params = [];
        }
        
        if (is_string($handler)) {
            // 控制器方法格式: Controller@method
            if (strpos($handler, '@') !== false) {
                list($controller, $method) = explode('@', $handler);
                $controllerClass = "App\\Controllers\\{$controller}";
                
                if (!class_exists($controllerClass)) {
                    $this->response->error('控制器不存在: ' . $controllerClass, 500);
                }
                
                $controllerInstance = new $controllerClass($this->request, $this->response);
                
                if (!method_exists($controllerInstance, $method)) {
                    $this->response->error('方法不存在: ' . $method, 500);
                }
                
                call_user_func_array([$controllerInstance, $method], $params);
            }
        } elseif (is_callable($handler)) {
            // 闭包函数
            call_user_func_array($handler, array_merge([$this->request, $this->response], $params));
        } else {
            $this->response->error('无效的路由处理器', 500);
        }
    }
}