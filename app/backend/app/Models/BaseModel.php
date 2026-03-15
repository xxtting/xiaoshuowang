<?php

namespace App\Models;

use App\Core\Database;

/**
 * 模型基类
 */
abstract class BaseModel
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取所有记录
     */
    public function all($columns = ['*'])
    {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table}";
        return $this->db->fetchAll($sql);
    }

    /**
     * 根据ID查找记录
     */
    public function find($id, $columns = ['*'])
    {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * 创建新记录
     */
    public function create($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * 更新记录
     */
    public function update($id, $data)
    {
        return $this->db->update($this->table, $data, "{$this->primaryKey} = ?", [$id]);
    }

    /**
     * 删除记录
     */
    public function delete($id)
    {
        return $this->db->delete($this->table, "{$this->primaryKey} = ?", [$id]);
    }

    /**
     * 根据条件查询
     */
    public function where($conditions, $params = [], $columns = ['*'])
    {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$conditions}";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 根据条件查询单条记录
     */
    public function whereFirst($conditions, $params = [], $columns = ['*'])
    {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$conditions} LIMIT 1";
        return $this->db->fetch($sql, $params);
    }

    /**
     * 统计记录数
     */
    public function count($conditions = '', $params = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        if (!empty($conditions)) {
            $sql .= " WHERE {$conditions}";
        }
        
        $result = $this->db->fetch($sql, $params);
        return (int) $result['count'];
    }

    /**
     * 分页查询
     */
    public function paginate($page = 1, $pageSize = 20, $conditions = '', $params = [], $columns = ['*'])
    {
        $columns = implode(', ', $columns);
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT {$columns} FROM {$this->table}";
        if (!empty($conditions)) {
            $sql .= " WHERE {$conditions}";
        }
        $sql .= " LIMIT {$offset}, {$pageSize}";
        
        $data = $this->db->fetchAll($sql, $params);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM {$this->table}";
        if (!empty($conditions)) {
            $countSql .= " WHERE {$conditions}";
        }
        $total = $this->db->fetch($countSql, $params)['total'];
        
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
}