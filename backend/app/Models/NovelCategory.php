<?php

namespace App\Models;

/**
 * 小说分类模型
 */
class NovelCategory extends BaseModel
{
    protected $table = 'novel_category';
    
    /**
     * 获取启用的分类列表
     */
    public function getEnabledCategories()
    {
        return $this->where('status = 1', [], ['id', 'name', 'description', 'sort_order']);
    }
    
    /**
     * 根据ID获取分类信息
     */
    public function getCategoryById($id)
    {
        return $this->find($id);
    }
    
    /**
     * 获取分类统计信息
     */
    public function getCategoryStats()
    {
        $sql = "
            SELECT 
                c.id, 
                c.name, 
                COUNT(n.id) as novel_count,
                COALESCE(SUM(n.view_count), 0) as total_views
            FROM novel_category c
            LEFT JOIN novel n ON c.id = n.category_id AND n.status = 1
            WHERE c.status = 1
            GROUP BY c.id, c.name
            ORDER BY c.sort_order ASC
        ";
        
        return $this->db->fetchAll($sql);
    }
}