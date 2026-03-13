<?php

namespace App\Controllers;

use App\Models\AINovelGenerate;

/**
 * AI小说生成控制器
 */
class AIController extends BaseController
{
    private $aiModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->aiModel = new AINovelGenerate();
    }

    /**
     * 生成AI小说
     */
    public function generateNovel()
    {
        $userId = $this->getUserId();
        
        if (!$userId) {
            $this->response->error('请先登录');
        }

        $data = $this->validate([
            'prompt' => 'required|min:10|max:500',
            'category_id' => 'required',
            'title' => 'max:100',
            'style' => 'max:50'
        ]);

        // 创建生成记录
        $generateId = $this->aiModel->create([
            'user_id' => $userId,
            'prompt' => $data['prompt'],
            'status' => 0, // 生成中
            'create_time' => date('Y-m-d H:i:s')
        ]);

        if (!$generateId) {
            $this->response->error('生成任务创建失败');
        }

        // 异步调用AI生成（简化版，实际项目应使用队列）
        $this->asyncGenerateNovel($generateId, $data);
        
        $this->log('ai_generate', 'AI小说生成: ' . $data['prompt']);
        
        $this->response->success([
            'generate_id' => $generateId,
            'status' => 'generating'
        ], 'AI小说生成中，请稍后查看结果');
    }

    /**
     * 异步生成小说
     */
    private function asyncGenerateNovel($generateId, $data)
    {
        // 这里应该调用真正的AI API
        // 简化版：模拟生成过程
        
        $generatedContent = $this->callAIAPI($data['prompt']);
        
        if ($generatedContent) {
            // 生成成功
            $this->aiModel->update($generateId, [
                'generated_content' => $generatedContent,
                'status' => 1,
                'cost_tokens' => 100 // 模拟消耗
            ]);
            
            // 创建小说记录
            $novelId = $this->createNovelFromAI($data, $generatedContent, $generateId);
            
            if ($novelId) {
                $this->aiModel->update($generateId, [
                    'novel_id' => $novelId
                ]);
            }
        } else {
            // 生成失败
            $this->aiModel->update($generateId, [
                'status' => 2
            ]);
        }
    }

    /**
     * 调用AI API（简化版）
     */
    private function callAIAPI($prompt)
    {
        // 实际项目应调用OpenAI、百度AI等接口
        // 这里返回模拟内容
        
        $templates = [
            "在一个遥远的魔法世界，{$prompt}。主角踏上了寻找真相的旅程。",
            "现代都市背景下，{$prompt}。一段意想不到的故事就此展开。",
            "古代仙侠世界，{$prompt}。主人公历经磨难，最终成就传奇。"
        ];
        
        $template = $templates[array_rand($templates)];
        
        return $template . "\n\n" . $this->generateChapterContent();
    }

    /**
     * 生成章节内容
     */
    private function generateChapterContent()
    {
        $contents = [
            "月光如水，洒在古老的城墙上。主人公独自站在城头，望着远方，心中充满了复杂的情绪。",
            "雨后的清晨，空气中弥漫着泥土的芬芳。主人公开始了新的一天，却不知道今天将会有怎样的奇遇。",
            "繁华的都市中，霓虹闪烁。主人公穿梭在人群中，寻找着属于自己的答案。"
        ];
        
        return $contents[array_rand($contents)];
    }

    /**
     * 从AI内容创建小说
     */
    private function createNovelFromAI($data, $content, $generateId)
    {
        $novelModel = new \App\Models\Novel();
        
        $novelData = [
            'title' => $data['title'] ?: 'AI生成小说_' . date('YmdHis'),
            'author' => 'AI创作',
            'category_id' => $data['category_id'],
            'description' => substr($content, 0, 200) . '...',
            'status' => 1,
            'word_count' => mb_strlen($content),
            'chapter_count' => 1,
            'create_time' => date('Y-m-d H:i:s')
        ];
        
        $novelId = $novelModel->create($novelData);
        
        if ($novelId) {
            // 创建第一章
            $chapterModel = new \App\Models\NovelChapter();
            $chapterModel->create([
                'novel_id' => $novelId,
                'chapter_title' => '第一章 开始',
                'chapter_content' => $content,
                'chapter_number' => 1,
                'word_count' => mb_strlen($content),
                'is_free' => 1,
                'create_time' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $novelId;
    }

    /**
     * 获取生成记录
     */
    public function getGenerateRecords()
    {
        $userId = $this->getUserId();
        
        if (!$userId) {
            $this->response->error('请先登录');
        }

        $page = $this->request->getParam('page', 1);
        $pageSize = $this->request->getParam('page_size', 20);

        $result = $this->aiModel->paginate($page, $pageSize, 'user_id = ?', [$userId]);
        
        $this->response->success($result);
    }

    /**
     * 获取生成详情
     */
    public function getGenerateDetail()
    {
        $generateId = $this->request->getParam('generate_id');
        $userId = $this->getUserId();
        
        if (empty($generateId)) {
            $this->response->error('生成记录ID不能为空');
        }

        $record = $this->aiModel->find($generateId);
        
        if (!$record || $record['user_id'] != $userId) {
            $this->response->error('生成记录不存在');
        }
        
        $this->response->success($record);
    }
}