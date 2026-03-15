<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

/**
 * 控制器基类
 */
abstract class BaseController
{
    protected $request;
    protected $response;
    protected $db;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->db = Database::getInstance();
    }

    /**
     * 验证请求参数
     */
    protected function validate($rules, $data = null)
    {
        if ($data === null) {
            $data = array_merge($this->request->getParams(), $this->request->getBody());
        }

        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $rulesList = explode('|', $rule);

            foreach ($rulesList as $singleRule) {
                if ($singleRule === 'required' && (empty($value) && $value !== '0')) {
                    $errors[$field] = "{$field} 字段是必须的";
                    break;
                }

                if ($singleRule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "{$field} 必须是有效的邮箱地址";
                    break;
                }

                if (strpos($singleRule, 'min:') === 0 && !empty($value)) {
                    $min = (int) str_replace('min:', '', $singleRule);
                    if (strlen($value) < $min) {
                        $errors[$field] = "{$field} 长度不能少于 {$min} 个字符";
                        break;
                    }
                }

                if (strpos($singleRule, 'max:') === 0 && !empty($value)) {
                    $max = (int) str_replace('max:', '', $singleRule);
                    if (strlen($value) > $max) {
                        $errors[$field] = "{$field} 长度不能超过 {$max} 个字符";
                        break;
                    }
                }
            }
        }

        if (!empty($errors)) {
            $this->response->error('参数验证失败', 400, $errors);
        }

        return $data;
    }

    /**
     * 分页处理
     */
    protected function paginate($query, $params = [])
    {
        $page = max(1, (int) ($this->request->getParam('page', 1)));
        $pageSize = min(100, max(1, (int) ($this->request->getParam('page_size', 20))));
        $offset = ($page - 1) * $pageSize;

        // 获取总数
        $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as t";
        $total = $this->db->fetch($countQuery, $params)['total'];

        // 获取数据
        $dataQuery = "{$query} LIMIT {$offset}, {$pageSize}";
        $data = $this->db->fetchAll($dataQuery, $params);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total' => (int) $total,
                'total_pages' => ceil($total / $pageSize)
            ]
        ];
    }

    /**
     * 记录操作日志
     */
    protected function log($action, $description = '')
    {
        $userId = $this->getUserId();
        
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip' => $this->request->getClientIp(),
            'user_agent' => $this->request->getUserAgent()
        ];

        $this->db->insert('sys_log', $logData);
    }

    /**
     * 获取当前用户ID
     */
    protected function getUserId()
    {
        // 从请求头获取token并验证
        $token = $this->request->getHeader('Authorization');
        if ($token && strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
            // 这里应该验证token并返回用户ID
            // 暂时返回0，实际项目中需要实现完整的JWT验证
            return 0;
        }

        return 0;
    }
}