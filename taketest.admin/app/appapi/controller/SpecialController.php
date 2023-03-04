<?php

namespace app\appapi\controller;

use think\Db;
use cmf\controller\HomeBaseController;

/**
 * [找店]专题列表
 */
class SpecialController extends HomeBaseController
{
    public function index()
    {
        $id = input('id');

        $list = Db::name('portal_category_post c')
            ->leftJoin('portal_post p', 'c.post_id=p.id')
            ->field('p.id,p.post_title,p.thumbnail,p.update_time')
            ->where([['c.status', '=', 1], ['c.category_id', '=', $id], ['p.post_status', '=', 1]])
            ->order('id desc')
            ->select()->toArray();
        foreach ($list as $key => &$value) {
            $value['thumbnail'] = get_upload_path($value['thumbnail']);
            $value['update_time'] = date('Y-m-d', $value['update_time']);
        }
        $this->assign('list', $list);
        $this->assign('uid', '');
        $this->assign('token', '');
        return $this->fetch();
    }
}