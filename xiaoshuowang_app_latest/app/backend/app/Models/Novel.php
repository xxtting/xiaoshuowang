<?php

namespace App\Models;

/**
 * 小说模型
 */
class Novel extends BaseModel
{
    protected $table = 'novel';
    
    /**
     * 获取小说列表
     */
    public function getNovels($categoryId = null, $keyword = null, $page = 1, $pageSize = 20)
    {
        $conditions = 'n.status = 1';
        $params = [];
        
        if ($categoryId) {
            $conditions .= ' AND n.category_id = ?';
            $params[] = $categoryId;
        }
        
        if ($keyword) {
            $conditions .= ' AND (n.title LIKE ? OR n.author LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        
        $sql = "
            SELECT 
                n.*,
                c.name as category_name
            FROM novel n
            LEFT JOIN novel_category c ON n.category_id = c.id
            WHERE {$conditions}
            ORDER BY n.update_time DESC
        ";
        
        return $this->paginate($sql, $params, $page, $pageSize);
    }
    
    /**
     * 获取热门小说
     */
    public function getHotNovels($limit = 10)
    {
        $sql = "
            SELECT 
                n.*,
                c.name as category_name
            FROM novel n
            LEFT JOIN novel_category c ON n.category_id = c.id
            WHERE n.status = 1
            ORDER BY n.view_count DESC, n.update_time DESC
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * 获取最新小说
     */
    public function getLatestNovels($limit = 10)
    {
        $sql = "
            SELECT 
                n.*,
                c.name as category_name
            FROM novel n
            LEFT JOIN novel_category c ON n.category_id = c.id
            WHERE n.status = 1
            ORDER BY n.create_time DESC
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * 获取小说详情
     */
    public function getNovelDetail($id)
    {
        $sql = "
            SELECT 
                n.*,
                c.name as category_name
            FROM novel n
            LEFT JOIN novel_category c ON n.category_id = c.id
            WHERE n.id = ? AND n.status = 1
        ";
        
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * 增加阅读量
     */
    public function incrementViewCount($id)
    {
        $sql = "UPDATE novel SET view_count = view_count + 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * 更新章节信息
     */
    public function updateChapterInfo($id, $chapterCount, $wordCount)
    {
        $data = [
            'chapter_count' => $chapterCount,
            'word_count' => $wordCount,
            'update_time' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($id, $data);
    }
}