<?php

namespace App\Controllers;

use App\Models\User;

/**
 * 用户控制器
 */
class UserController extends BaseController
{
    private $userModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->userModel = new User();
    }

    /**
     * 用户注册
     */
    public function register()
    {
        $data = $this->validate([
            'username' => 'required|min:3|max:20',
            'password' => 'required|min:6',
            'nickname' => 'max:20',
            'phone' => 'max:20',
            'email' => 'email'
        ]);

        // 检查用户名是否已存在
        $existingUser = $this->userModel->findByLogin($data['username']);
        if ($existingUser) {
            $this->response->error('用户名已存在');
        }

        // 创建用户
        $userId = $this->userModel->createUser($data);
        
        if ($userId) {
            $this->log('user_register', '用户注册: ' . $data['username']);
            $this->response->success(['user_id' => $userId], '注册成功');
        } else {
            $this->response->error('注册失败');
        }
    }

    /**
     * 用户登录
     */
    public function login()
    {
        $data = $this->validate([
            'login' => 'required',
            'password' => 'required'
        ]);

        // 查找用户
        $user = $this->userModel->findByLogin($data['login']);
        if (!$user) {
            $this->response->error('用户不存在');
        }

        // 验证密码
        if (!$this->userModel->verifyPassword($data['password'], $user['password'])) {
            $this->response->error('密码错误');
        }

        // 生成token（简化版，实际项目应使用JWT）
        $token = $this->generateToken($user['id']);
        
        // 更新登录信息
        $this->userModel->update($user['id'], [
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip' => $this->request->getClientIp()
        ]);

        $this->log('user_login', '用户登录: ' . $user['username']);
        
        $result = [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar'],
                'vip_level' => $user['vip_level']
            ],
            'token' => $token
        ];
        
        $this->response->success($result, '登录成功');
    }

    /**
     * 获取用户信息
     */
    public function getProfile()
    {
        $userId = $this->getUserId();
        
        if (!$userId) {
            $this->response->error('请先登录');
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            $this->response->error('用户不存在');
        }

        // 获取用户统计信息
        $stats = $this->userModel->getUserStats($userId);
        
        unset($user['password']);
        $user['stats'] = $stats;
        
        $this->response->success($user);
    }

    /**
     * 更新用户信息
     */
    public function updateProfile()
    {
        $userId = $this->getUserId();
        
        if (!$userId) {
            $this->response->error('请先登录');
        }

        $data = $this->validate([
            'nickname' => 'max:20',
            'avatar' => 'max:255',
            'phone' => 'max:20',
            'email' => 'email'
        ]);

        // 移除空值
        $data = array_filter($data);
        
        if (empty($data)) {
            $this->response->error('没有要更新的数据');
        }

        $result = $this->userModel->updateUser($userId, $data);
        
        if ($result) {
            $this->log('user_update', '用户更新个人信息');
            $this->response->success(null, '更新成功');
        } else {
            $this->response->error('更新失败');
        }
    }

    /**
     * 微信公众号登录
     */
    public function wechatLogin()
    {
        $data = $this->validate([
            'openid' => 'required',
            'nickname' => 'max:20',
            'avatar' => 'max:255'
        ]);

        // 查找用户
        $user = $this->userModel->findByOpenid($data['openid']);
        
        if ($user) {
            // 更新用户信息
            $this->userModel->update($user['id'], [
                'last_login_time' => date('Y-m-d H:i:s'),
                'last_login_ip' => $this->request->getClientIp()
            ]);
        } else {
            // 创建新用户
            $userData = [
                'username' => 'wx_' . substr($data['openid'], 0, 8),
                'password' => md5(uniqid()), // 随机密码
                'openid' => $data['openid'],
                'nickname' => $data['nickname'] ?? '微信用户',
                'avatar' => $data['avatar'] ?? ''
            ];
            
            $userId = $this->userModel->createUser($userData);
            $user = $this->userModel->find($userId);
        }

        // 生成token
        $token = $this->generateToken($user['id']);
        
        $this->log('wechat_login', '微信登录: ' . $user['username']);
        
        $result = [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar'],
                'vip_level' => $user['vip_level']
            ],
            'token' => $token
        ];
        
        $this->response->success($result, '登录成功');
    }

    /**
     * 生成简易token（实际项目应使用JWT）
     */
    private function generateToken($userId)
    {
        $token = base64_encode(json_encode([
            'user_id' => $userId,
            'timestamp' => time(),
            'expire' => time() + 86400 * 7 // 7天过期
        ]));
        
        return $token;
    }
}