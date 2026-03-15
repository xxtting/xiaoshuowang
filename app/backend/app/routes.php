<?php

/**
 * 小说网系统路由配置
 */

// 小说相关接口
$router->get('/api/novel/list', 'NovelController@getList');
$router->get('/api/novel/detail', 'NovelController@getDetail');
$router->get('/api/novel/hot', 'NovelController@getHotList');
$router->get('/api/novel/latest', 'NovelController@getLatestList');
$router->get('/api/novel/chapters', 'NovelController@getChapterList');
$router->get('/api/novel/chapter/detail', 'NovelController@getChapterDetail');
$router->get('/api/novel/categories', 'NovelController@getCategoryList');
$router->get('/api/novel/category/stats', 'NovelController@getCategoryStats');

// 用户相关接口
$router->post('/api/user/register', 'UserController@register');
$router->post('/api/user/login', 'UserController@login');
$router->post('/api/user/wechat-login', 'UserController@wechatLogin');
$router->get('/api/user/profile', 'UserController@getProfile');
$router->post('/api/user/profile', 'UserController@updateProfile');

// AI小说生成接口
$router->post('/api/ai/generate', 'AIController@generateNovel');
$router->get('/api/ai/records', 'AIController@getGenerateRecords');
$router->get('/api/ai/detail', 'AIController@getGenerateDetail');

// 微信公众号接口
$router->get('/api/wechat/auth', 'WechatController@auth');
$router->post('/api/wechat/callback', 'WechatController@callback');

// 系统管理接口（需要管理员权限）
$router->get('/api/admin/novels', 'AdminController@getNovels');
$router->post('/api/admin/novel', 'AdminController@createNovel');
$router->put('/api/admin/novel', 'AdminController@updateNovel');
$router->delete('/api/admin/novel', 'AdminController@deleteNovel');

$router->get('/api/admin/users', 'AdminController@getUsers');
$router->post('/api/admin/user', 'AdminController@createUser');
$router->put('/api/admin/user', 'AdminController@updateUser');
$router->delete('/api/admin/user', 'AdminController@deleteUser');

$router->get('/api/admin/categories', 'AdminController@getCategories');
$router->post('/api/admin/category', 'AdminController@createCategory');
$router->put('/api/admin/category', 'AdminController@updateCategory');
$router->delete('/api/admin/category', 'AdminController@deleteCategory');

// 系统配置接口
$router->get('/api/admin/config', 'AdminController@getConfig');
$router->post('/api/admin/config', 'AdminController@updateConfig');

// 健康检查接口
$router->get('/api/health', function($request, $response) {
    $response->success(['status' => 'ok', 'timestamp' => time()]);
});