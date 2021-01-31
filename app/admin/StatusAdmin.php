<?php


namespace app\admin;

use core\lib\Data;
use core\lib\Date;

class StatusAdmin extends DaoAdmin
{

    protected $menu_desc = [
        'dashboard' => "仪表盘"
    ];

    public function dashboard()
    {
        $this->callback['type'] = 'dashboard';
        $data = [];

        $user = new Data('`user`');
        $data['用户'] = [
            '注册' => [
                '今日' => $user->getByTime("reg_time", 1, 'state IN (0,1)'),
                '本周' => $user->getByTime("reg_time", 7, 'state IN (0,1)'),
                '本月' => $user->getByTime("reg_time", 30, 'state IN (0,1)'),
                '总数' => $user->count('state IN (0,1)')
            ],
            '活跃' => [
                '今日' => $user->getByTime("last_time", 1, 'state IN (0,1)'),
                '本周' => $user->getByTime("last_time", 7, 'state IN (0,1)'),
                '本月' => $user->getByTime("last_time", 30, 'state IN (0,1)')
            ]
        ];

        $topic = new Data('`topic`');
        $data['话题'] = [
            '发布' => [
                '今日' => $topic->getByTime("time", 1, 'state IN (0,1)'),
                '本周' => $topic->getByTime("time", 7, 'state IN (0,1)'),
                '本月' => $topic->getByTime("time", 30, 'state IN (0,1)'),
                '总数' => $topic->count('state IN (0,1)')
            ]
        ];

        $review = new Data('`review`');
        $data['评论'] = [
            '发布' => [
                '今日' => $review->getByTime("time", 1, 'state IN (0,1)'),
                '本周' => $review->getByTime("time", 7, 'state IN (0,1)'),
                '本月' => $review->getByTime("time", 30, 'state IN (0,1)'),
                '总数' => $review->count('state IN (0,1)')
            ]
        ];

        $article = new Data('`article`');
        $data['文档'] = [
            '发布' => [
                '今日' => $article->getByTime("time", 1, 'state IN (0,1)'),
                '本周' => $article->getByTime("time", 7, 'state IN (0,1)'),
                '本月' => $article->getByTime("time", 30, 'state IN (0,1)'),
                '总数' => $article->count('state IN (0,1)')
            ]
        ];
        $this->callback['data'] = json_encode($data);
    }
}