<?php

namespace App\Core;

/**
 * 响应处理类
 */
class Response
{
    private $statusCode = 200;
    private $headers = [];
    private $data = [];

    /**
     * 设置状态码
     */
    public function status($code)
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * 设置响应头
     */
    public function header($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * 返回JSON响应
     */
    public function json($data, $code = 200)
    {
        $this->statusCode = $code;
        $this->data = $data;
        
        $this->header('Content-Type', 'application/json; charset=utf-8');
        $this->send();
    }

    /**
     * 返回成功响应
     */
    public function success($data = null, $message = '操作成功')
    {
        $response = [
            'code' => 200,
            'message' => $message,
            'data' => $data
        ];
        
        $this->json($response, 200);
    }

    /**
     * 返回错误响应
     */
    public function error($message = '操作失败', $code = 400, $data = null)
    {
        $response = [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];
        
        $this->json($response, $code);
    }

    /**
     * 发送响应
     */
    public function send()
    {
        // 设置状态码
        http_response_code($this->statusCode);
        
        // 设置响应头
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        // 发送数据
        if (!empty($this->data)) {
            echo json_encode($this->data, JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }

    /**
     * 重定向
     */
    public function redirect($url, $statusCode = 302)
    {
        $this->statusCode = $statusCode;
        $this->header('Location', $url);
        $this->send();
    }

    /**
     * 下载文件
     */
    public function download($filePath, $fileName = null)
    {
        if (!file_exists($filePath)) {
            $this->error('文件不存在', 404);
        }
        
        if ($fileName === null) {
            $fileName = basename($filePath);
        }
        
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $this->header('Content-Length', filesize($filePath));
        
        readfile($filePath);
        exit;
    }
}