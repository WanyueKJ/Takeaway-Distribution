<?php

/* 帮助中心 */

namespace app\appapi\controller;

use cmf\controller\HomeBaseController;
use app\portal\service\PostService;
use think\Db;

class HelpController extends HomebaseController
{


    /***展示页面 */
    public function index()
    {

        $list = Db::name('portal_category_post c')
            ->leftJoin('portal_post p', 'c.post_id=p.id')
            ->field('p.id,p.post_title,p.update_time')
            ->where([['c.status', '=', 1], ['c.category_id', '=', 1], ['p.post_status', '=', 1]])
            ->order('id desc')
            ->limit(0, 10)
            ->select();
        foreach ($list as $key => &$value) {
            $value['update_time'] = date('Y-m-d', $value['update_time']);
        }

        $this->assign('list', $list);
        $this->assign('uid', '');
        $this->assign('token', '');
        return $this->fetch();
    }

    /***详情页 */
    public function detail()
    {

        $postService = new PostService();
        $pageId = $this->request->param('id', 0, 'intval');
        $page = $postService->publishedArticle($pageId);

        $this->assign('uid', '');
        $this->assign('token', '');

        if (empty($page)) {
            $this->assign('reason', lang('页面不存在'));
            return $this->fetch(':error');
        }

        $this->assign('page', $page);

        return $this->fetch();
    }

}