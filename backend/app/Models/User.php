<?php

namespace App\Models;

/**
 * 用户模型
 */
class User extends BaseModel
{
    protected $table = 'user';
    
    /**
     * 根据用户名或邮箱或手机号查找用户
     */
    public function findByLogin($login)
    {
        $sql = "
            SELECT * FROM user 
            WHERE (username = ? OR email = ? OR phone = ?) AND status = 1
            LIMIT 1
        ";
        
        return $this->db->fetch($sql, [$login, $login, $login]);
    }
    
    /**
     * 根据OpenID查找用户
     */
    public function findByOpenid($openid)
    {
        return $this->whereFirst('openid = ? AND status = 1', [$openid]);
    }
    
    /**
     * 创建用户
     */
    public function createUser($data)
    {
        // 密码加密
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($data);
    }
    
    /**
     * 验证密码
     */
    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }
    
    /**
     * 更新用户信息
     */
    public function updateUser($id, $data)
    {
        // 如果更新密码，需要重新加密
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * 获取用户统计信息
     */
    public function getUserStats($userId)
    {
        $stats = [];
        
        // 阅读记录统计
        $readStats = $this->db->fetch(
            "SELECT COUNT(*) as read_count, SUM(read_progress) as total_read FROM user_read_history WHERE user_id = ?",
            [$userId]
        );
        
        $stats['read_count'] = (int) $readStats['read_count'];
        $stats['total_read'] = (int) $readStats['total_read'];
        
        // 收藏统计
        $favoriteStats = $this->db->fetch(
            "SELECT COUNT(*) as favorite_count FROM user_favorite WHERE user_id = ?",
            [$userId]
        );
        
        $stats['favorite_count'] = (int) $favoriteStats['favorite_count'];
        
        return $stats;
    }
}