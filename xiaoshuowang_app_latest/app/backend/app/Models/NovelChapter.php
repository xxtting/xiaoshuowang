<?php

namespace App\Models;

/**
 * 小说章节模型
 */
class NovelChapter extends BaseModel
{
    protected $table = 'novel_chapter';
    
    /**
     * 获取小说章节列表
     */
    public function getChaptersByNovelId($novelId, $page = 1, $pageSize = 50)
    {
        $sql = "
            SELECT 
                id, chapter_title, chapter_number, is_free, create_time
            FROM novel_chapter 
            WHERE novel_id = ? 
            ORDER BY chapter_number ASC
        ";
        
        return $this->paginate($sql, [$novelId], $page, $pageSize);
    }
    
    /**
     * 获取章节详情
     */
    public function getChapterDetail($chapterId, $novelId = null)
    {
        $sql = "
            SELECT 
                nc.*,
                n.title as novel_title,
                n.author as novel_author
            FROM novel_chapter nc
            LEFT JOIN novel n ON nc.novel_id = n.id
            WHERE nc.id = ?
        ";
        
        $params = [$chapterId];
        
        if ($novelId) {
            $sql .= " AND nc.novel_id = ?";
            $params[] = $novelId;
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    /**
     * 获取上一章和下一章
     */
    public function getAdjacentChapters($novelId, $chapterNumber)
    {
        $prevChapter = $this->db->fetch(
            "SELECT id, chapter_title FROM novel_chapter WHERE novel_id = ? AND chapter_number < ? ORDER BY chapter_number DESC LIMIT 1",
            [$novelId, $chapterNumber]
        );
        
        $nextChapter = $this->db->fetch(
            "SELECT id, chapter_title FROM novel_chapter WHERE novel_id = ? AND chapter_number > ? ORDER BY chapter_number ASC LIMIT 1",
            [$novelId, $chapterNumber]
        );
        
        return [
            'prev' => $prevChapter,
            'next' => $nextChapter
        ];
    }
    
    /**
     * 获取小说总字数
     */
    public function getNovelWordCount($novelId)
    {
        $result = $this->db->fetch(
            "SELECT SUM(word_count) as total_words FROM novel_chapter WHERE novel_id = ?",
            [$novelId]
        );
        
        return (int) $result['total_words'];
    }
}