<?php

namespace App\Controllers;

use App\Models\Novel;
use App\Models\NovelCategory;
use App\Models\NovelChapter;

/**
 * 小说控制器
 */
class NovelController extends BaseController
{
    private $novelModel;
    private $categoryModel;
    private $chapterModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->novelModel = new Novel();
        $this->categoryModel = new NovelCategory();
        $this->chapterModel = new NovelChapter();
    }

    /**
     * 获取小说列表
     */
    public function getList()
    {
        $categoryId = $this->request->getParam('category_id');
        $keyword = $this->request->getParam('keyword');
        $page = $this->request->getParam('page', 1);
        $pageSize = $this->request->getParam('page_size', 20);

        $result = $this->novelModel->getNovels($categoryId, $keyword, $page, $pageSize);
        
        $this->response->success($result);
    }

    /**
     * 获取小说详情
     */
    public function getDetail()
    {
        $id = $this->request->getParam('id');
        
        if (empty($id)) {
            $this->response->error('小说ID不能为空');
        }

        $novel = $this->novelModel->getNovelDetail($id);
        
        if (!$novel) {
            $this->response->error('小说不存在');
        }

        // 增加阅读量
        $this->novelModel->incrementViewCount($id);
        
        $this->response->success($novel);
    }

    /**
     * 获取热门小说
     */
    public function getHotList()
    {
        $limit = $this->request->getParam('limit', 10);
        $novels = $this->novelModel->getHotNovels($limit);
        
        $this->response->success($novels);
    }

    /**
     * 获取最新小说
     */
    public function getLatestList()
    {
        $limit = $this->request->getParam('limit', 10);
        $novels = $this->novelModel->getLatestNovels($limit);
        
        $this->response->success($novels);
    }

    /**
     * 获取小说章节列表
     */
    public function getChapterList()
    {
        $novelId = $this->request->getParam('novel_id');
        $page = $this->request->getParam('page', 1);
        $pageSize = $this->request->getParam('page_size', 50);

        if (empty($novelId)) {
            $this->response->error('小说ID不能为空');
        }

        $result = $this->chapterModel->getChaptersByNovelId($novelId, $page, $pageSize);
        
        $this->response->success($result);
    }

    /**
     * 获取章节详情
     */
    public function getChapterDetail()
    {
        $chapterId = $this->request->getParam('chapter_id');
        $novelId = $this->request->getParam('novel_id');

        if (empty($chapterId)) {
            $this->response->error('章节ID不能为空');
        }

        $chapter = $this->chapterModel->getChapterDetail($chapterId, $novelId);
        
        if (!$chapter) {
            $this->response->error('章节不存在');
        }

        // 获取相邻章节
        $adjacent = $this->chapterModel->getAdjacentChapters($chapter['novel_id'], $chapter['chapter_number']);
        
        $result = [
            'chapter' => $chapter,
            'adjacent' => $adjacent
        ];
        
        $this->response->success($result);
    }

    /**
     * 获取分类列表
     */
    public function getCategoryList()
    {
        $categories = $this->categoryModel->getEnabledCategories();
        
        $this->response->success($categories);
    }

    /**
     * 获取分类统计
     */
    public function getCategoryStats()
    {
        $stats = $this->categoryModel->getCategoryStats();
        
        $this->response->success($stats);
    }
}