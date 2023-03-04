<?php

namespace App\Domain;

use App\ApiException;
use App\Model\MerchantStoreOrder as MerchantStoreOrderModel;
use App\Model\MerchantStoreOrderEvaluate as MerchantStoreOrderEvaluateModel;
use App\Domain\MerchantStoreProductReply as MerchantStoreProductReplyDomain;
use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Domain\MerchantStoreProduct as MerchantStoreProductDomain;
use App\Model\MerchantStoreOrderCartInfo as MerchantStoreOrderCartInfoDomain;
use App\Model\Rider as RiderModel;
use App\Model\User as UserModel;
use Rider\Model\User as Model_User;

/**
 * @method array getOne(array $where, $field = '*')
 */
class MerchantStoreOrderEvaluate
{


    public function getProductNumber($uid, $store_id, $product_id)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $productWhere = [];
        if ($product_id > 0) {
            $productWhere['product_id = ?'] = $product_id;
        }

        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $store_id], 'top_type_id');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺不存在!'), 400);

        if ($storeInfo['top_type_id'] == 1) {
            $productWhere['tags'] = [-1, 1];
        }
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        //全部
        $all = $MerchantStoreProductReplyDomain->getCount(array_merge(['store_id = ?' => $store_id], $productWhere));
        //最新
        $new = $MerchantStoreProductReplyDomain->getCount(array_merge(['store_id = ?' => $store_id,
            'addtime >= ?' => strtotime(date('Y-m-d')),
            'addtime <= ?' => time()
        ], $productWhere));
        //有图
        $figure = $MerchantStoreProductReplyDomain->getCount(array_merge([
            'store_id = ?' => $store_id,
            'pics <> ? OR video <> ?' => ['[]', '[]'],
        ], $productWhere));

        //好评
        $good = $MerchantStoreProductReplyDomain->getCount(array_merge([
            'store_id = ?' => $store_id,
            'tags = ?' => 1,
        ], $productWhere));

        if ($storeInfo['top_type_id'] == 4) {
            $good = $MerchantStoreProductReplyDomain->getCount(array_merge([
                'store_id = ?' => $store_id,
                'overall_star >= ?' => 5,
            ], $productWhere));
        }

        //中评
        $medium = $MerchantStoreProductReplyDomain->getCount(array_merge([
            'store_id = ?' => $store_id,
            'tags = ?' => 0,
        ], $productWhere));
        //差评
        $negative = $MerchantStoreProductReplyDomain->getCount(array_merge([
            'store_id = ?' => $store_id,
            'tags = ?' => -1,
        ], $productWhere));

        $rs['info'][0] = compact('all', 'new', 'figure', 'good', 'medium', 'negative');
        return $rs;
    }

    /**
     * 订单评论点赞
     * @param $uid
     * @param $id
     * @param $status
     * @return array
     */
    public function setOrderReplyLike($uid, $id, $status)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $MerchantStoreOrderEvaluateModel = new MerchantStoreOrderEvaluateModel();
        try {
            if ($status == 0) {
                $set = $MerchantStoreOrderEvaluateModel->deleteReplyLikeOne(['uid = ?' => $uid, 'order_evaluate_id = ?' => $id]);
            } else if ($status == 1) {
                $set = $MerchantStoreOrderEvaluateModel->saveReplyLikeOne(['uid' => $uid, 'order_evaluate_id' => $id, 'addtime' => time()]);

            }
        } catch (\Exception $exception) {

        }


        $info = $MerchantStoreOrderEvaluateModel->getReplyLikeOne(['uid = ?' => $uid, 'order_evaluate_id = ?' => $id]);
        $count = $MerchantStoreOrderEvaluateModel->getReplyLikeOne(['order_evaluate_id = ?' => $id], 'count(id) as count');
        $MerchantStoreOrderEvaluateModel->updateOne(['id = ?' => $id], ['like' => $count['count'] ?? 0]);

        $res = [
            'status' => $info ? 1 : 0,
            'count' => $count['count'] ?? 0,
        ];
        $rs['msg'] = \PhalApi\T('操作成功!');
        $rs['info'][] = $res;
        return $rs;
    }


    /**
     * 获取订单评价
     * @param $uid
     * @param $store_id
     * @param $type
     * @param $p
     * @return array
     */
    public function getOrderReplyList($uid, $store_id, $type, $p)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $MerchantStoreOrderEvaluateModel = new MerchantStoreOrderEvaluateModel();

        $where = [];
        $where['store_id = ?'] = $store_id;

        if ($type == 1) {//最新(今天)
            $where['addtime >= ?'] = strtotime(date('Y-m-d') . '00:00:00');
            $where['addtime <= ?'] = time();
        } else if ($type == 2) {//有图
            $where['pics <> ? OR video <> ?'] = ['[]', '[]'];
        } else if ($type == 3) {//好评
            $where['overall_star >= 4 AND overall_star <= ?'] = 5;
        } else if ($type == 4) {//差评
            $where['overall_star >= 0 AND overall_star <= ?'] = 2;
        } else if ($type == 5) {//中评
            $where['overall_star'] = [3, "3.5"];
        }
        $list = $MerchantStoreOrderEvaluateModel->selectList($where, 'id,`content` as `comment`,uid,pics,video,overall_star,addtime,is_reply,merchant_reply_content,is_anonymous,`like`,merchant_reply_time', 'id DESC', $p, 20);
        $UserModel = new UserModel();

        foreach ($list as $key => &$value) {
            $reply = [];
            if ($value['is_reply'] == 1) {
                $reply['merchant_reply_time'] = \App\timeFormatting($value['merchant_reply_time'],$value['addtime']);
                $reply['merchant_reply_content'] = $value['merchant_reply_content'];
                unset($value['merchant_reply_time']);
                unset($value['merchant_reply_content']);
            }
            $value['reply'] = $reply;
            $value['overall_star_txt'] = $this->getStartTxt($value['overall_star']);

            $value['addtime'] = date('Y-m-d', $value['addtime']);
            $set = $MerchantStoreOrderEvaluateModel->getOrderReplyLikeOne(['uid' => $uid, 'order_evaluate_id' => $value['id']]);
            $value['is_like'] = $set ? 1 : 0;

            $user = [];
            if ($value['is_anonymous'] == 0) {
                $user = $UserModel->getInfo(['id = ?' => $value['uid']], 'user_nickname,avatar');
                $user['avatar'] = \App\get_upload_path($user['avatar']);
            }
            $value['user'] = $user;

            $pics = json_decode($value['pics'], true) ?? [];
            array_walk($pics, function (&$value2, $index2) {
                $value2 = \App\get_upload_path($value2);
            });
            $value['pics'] = $pics;

            $video = json_decode($value['video'], true) ?? [];
            if ($video) {
                $video['url'] = \App\get_upload_path($video['url']);
                $video['thumb'] = $this->substituteSuffixes($video['url'], '.jpg');
            }
            $value['video'] = $video;
        }
        $rs['info'][0] = $list;
        return $rs;
    }


    /**
     * 评价列表(商品)
     * @param $uid
     * @param $store_id
     * @param $product_id
     * @param $type
     * @param $p
     * @return array
     */
    public function getProductReplyList($uid, $store_id, $product_id, $type, $p)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();

        $where = [];
        $where['store_id = ?'] = $store_id;
        if ($product_id > 0) $where['product_id = ?'] = $product_id;
        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $store_id], 'top_type_id');
        if (!$storeInfo) {
            throw new ApiException(\PhalApi\T('店铺不存在'));
        }
        if ($storeInfo['top_type_id'] == 1) {
            $where['tags'] = [1, -1];
        }
        if ($type === '') {//默认全部

        } else if ($type == 100) {//最新(今天)
            $where['addtime >= ?'] = strtotime(date('Y-m-d'));
            $where['addtime <= ?'] = time();
        } else if ($type == 101) {//有图
            $where['pics <> ? OR video <> ?'] = ['[]', '[]'];
        } else if ($type == -1) {//差评
            $where['tags = ?'] = -1;
        } else if ($type == 0) {//中等
            $where['tags = ?'] = 0;
        } else if ($type == 1) {//好评
            $where['tags = ?'] = 1;
        }
        if ($storeInfo['top_type_id'] == 4) {
            $where = [];
            $where['store_id = ?'] = $store_id;
            $where['overall_star >= ?'] = 5;
        }
        $list = $MerchantStoreProductReplyDomain->selectList($where, 'id,`comment`,uid,pics,video,tags,overall_star,addtime,is_reply,merchant_reply_content,is_anonymous,`like`,merchant_reply_time', 'id DESC', $p, 3);
        $UserModel = new UserModel();
        foreach ($list as $key => &$value) {
            $reply = [];
            if ($value['is_reply'] == 1) {
                $reply['merchant_reply_time'] = \App\handellength2(time() - $value['merchant_reply_time']) . '后';
                $reply['merchant_reply_content'] = $value['merchant_reply_content'];
                unset($value['merchant_reply_time']);
                unset($value['merchant_reply_content']);
            }
            $value['reply'] = $reply;
            $value['overall_star_txt'] = $this->getStartTxt($value['overall_star']);

            $value['addtime'] = date('Y-m-d', $value['addtime']);
            $set = $MerchantStoreProductReplyDomain->getReplyLikeOne(['uid' => $uid, 'product_reply_id' => $value['id']]);
            $value['is_like'] = $set ? 1 : 0;

            $user = [];
            if ($value['is_anonymous'] == 0) {
                $user = $UserModel->getInfo(['id = ?' => $value['uid']], 'user_nickname,avatar');
                $user['avatar'] = \App\get_upload_path($user['avatar']);
            }
            $value['user'] = $user;

            $pics = json_decode($value['pics'], true) ?? [];
            array_walk($pics, function (&$value2, $index2) {
                $value2 = \App\get_upload_path($value2);
            });
            $value['pics'] = $pics;

            $video = json_decode($value['video'], true) ?? [];
            if ($video) {
                $video['url'] = \App\get_upload_path($video['url']);
                $video['thumb'] = $this->substituteSuffixes($video['url'], '.jpg');
            }
            $value['video'] = $video;
        }
        $rs['info'][0] = $list;
        return $rs;
    }


    /**
     * 后缀替换
     * @param $url
     * @param $suffix
     * @return void
     */
    protected function substituteSuffixes($url, $suffix = "")
    {
        $endNum = strrpos($url, '.');
        $cutOut = substr($url, 0, $endNum);
        $mchKey = $cutOut . $suffix;
        return $mchKey;
    }

    /**
     * 评论点赞 取消点赞
     * @param $uid
     * @param $id
     * @param $status
     * @return array
     */
    public function setReplyLike($uid, $id, $status)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        try {
            if ($status == 0) {
                $set = $MerchantStoreProductReplyDomain->deleteReplyLikeOne(['uid = ?' => $uid, 'product_reply_id = ?' => $id]);
            } else if ($status == 1) {
                $set = $MerchantStoreProductReplyDomain->saveReplyLikeOne(['uid' => $uid, 'product_reply_id' => $id, 'addtime' => time()]);

            }
        } catch (\Exception $exception) {

        }


        $info = $MerchantStoreProductReplyDomain->getReplyLikeOne(['uid = ?' => $uid, 'product_reply_id = ?' => $id]);
        $count = $MerchantStoreProductReplyDomain->getReplyLikeOne(['product_reply_id = ?' => $id], 'count(id) as count');
        $MerchantStoreProductReplyDomain->updateOne(['id = ?' => $id], ['like' => $count['count'] ?? 0]);

        $res = [
            'status' => $info ? 1 : 0,
            'count' => $count['count'] ?? 0,
        ];
        $rs['msg'] = \PhalApi\T('操作成功!');
        $rs['info'][] = $res;
        return $rs;

    }

    public function getStartTxt($starts)
    {
        if ($starts >= 0 && $starts < 2) {
            return \PhalApi\T('差!');
        } else if ($starts >= 2 && $starts < 3) {
            return \PhalApi\T('一般!');
        } else if ($starts >= 3 && $starts < 4) {
            return \PhalApi\T('良好!');
        } else if ($starts >= 4 && $starts < 5) {
            return \PhalApi\T('满意!');
        } else if ($starts >= 5) {
            return \PhalApi\T('非常满意!');
        }

    }


    /**
     * 统计评价数据
     * @param $uid
     * @param $store_id
     * @param $product_id
     * @return void
     */
    public function getNumber($uid, $store_id)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $productWhere = [];
        $MerchantStoreOrderEvaluateModel = new MerchantStoreOrderEvaluateModel();
        $all = $MerchantStoreOrderEvaluateModel->getCount(array_merge(['store_id = ?' => $store_id], $productWhere));

        //最新
        $new = $MerchantStoreOrderEvaluateModel->getCount(array_merge(['store_id = ?' => $store_id,
            'addtime >= ?' => strtotime(date('Y-m-d')),
            'addtime <= ?' => time()
        ], $productWhere));

        //有图
        $figure = $MerchantStoreOrderEvaluateModel->getCount(array_merge([
            'store_id = ?' => $store_id,
            'pics <> ? || video <> ?' => ['[]', '[]'],
        ], $productWhere));

        //好评
        $good = $MerchantStoreOrderEvaluateModel->getCount(array_merge([
            'store_id = ?' => $store_id,
            'overall_star >= 4 AND overall_star <= ?' => 5,
        ], $productWhere));

        //中评
        $medium = $MerchantStoreOrderEvaluateModel->getCount(array_merge([
            'store_id = ?' => $store_id,
            'overall_star' => [3, '3.5'],
        ], $productWhere));

        //差评
        $poor = $MerchantStoreOrderEvaluateModel->getCount(array_merge([
            'store_id = ?' => $store_id,
            'overall_star >= 1 AND overall_star <= ?' => 2,
        ], $productWhere));

        $rs['info'][0] = compact('all', 'new', 'figure', 'good', 'medium', 'poor');
        return $rs;
    }


    /**
     * 检测订单能否评价
     * @param $uid
     * @param $id
     * @return array
     */
    public function testOrderEvaluation($uid, $id)
    {
        $MerchantStoreOrderModel = new MerchantStoreOrderModel();
        $order = $MerchantStoreOrderModel->getOne(['id = ?' => $id, 'uid = ?' => $uid], 'id,status,end_time,shipping_type,delivery_uid,shipping_type,store_id');
        if (!$order) {
            throw new ApiException(\PhalApi\T('订单已不存在!'), 400);
        }
        if ($order['status'] != 4) {
            throw new ApiException(\PhalApi\T('订单暂时无法评价!'), 400);
        }

        $evaluate = $this->getOne(['oid = ?' => $id, 'uid = ?' => $uid], 'id');
        if ($evaluate) {
            throw new ApiException(\PhalApi\T('您已经评价过!'), 400);
        }
        return $order;
    }


    /**
     * 新增评价
     * @param $uid
     * @param $id
     * @param $rider_id
     * @param $rider_star
     * @param $rider_comment
     * @param $rider_anonymous
     * @param $is_anonymous
     * @param $order_pics
     * @param $order_comment
     * @param $order_overall_star
     * @param $order_taste_star
     * @param $order_packaging_star
     * @param $order_distribution_star
     * @param $order_anonymous
     * @param $product_json
     * @return void
     */
    public function addEvaluate($uid, $id, $rider_id, $rider_star, $rider_comment, $rider_anonymous, $order_id, $order_pics, $order_video, $order_comment, $order_overall_star, $order_taste_star, $order_packaging_star, $order_anonymous, $product_json)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $order = $this->testOrderEvaluation($uid, $id);

        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $order['store_id']], 'top_type_id');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺不存在!'), 400);

        if ($rider_id > 0) {
            $RiderModel = new RiderModel();
            $rider = $RiderModel->getOne(['id = ?' => $rider_id], 'id');
            if (!$rider) throw new ApiException(\PhalApi\T('配送员信息错误!'), 400);
            if (($rider_star < 0) || ($rider_star > 5)) throw new ApiException(\PhalApi\T('配送员评分错误!'), 400);
            if (!in_array($rider_anonymous, [0, 1])) $rider_anonymous = 0;//默认匿名
        }

        if ($order_id > 0) {
            if ($order_pics != '' && !is_array(json_decode($order_pics, true))) throw new ApiException(\PhalApi\T('订单图片格式错误!'), 400);
            $order_pics = json_decode($order_pics, true) ?? [];
            if ($order_video != '' && !is_array(json_decode($order_video, true))) throw new ApiException(\PhalApi\T('订单视频格式错误!'), 400);
            if (($order_overall_star < 0) || ($order_overall_star > 5)) throw new ApiException(\PhalApi\T('评分不正确0~5分!'), 400);
            if (($order_taste_star < 0) || ($order_taste_star > 5)) throw new ApiException(\PhalApi\T('评分不正确0~5分!!'), 400);
            if (($order_packaging_star < 0) || ($order_packaging_star > 5)) throw new ApiException(\PhalApi\T('评分不正确0~5分!!'), 400);
            if (!in_array($order_anonymous, [0, 1])) $order_anonymous = 0;//默认匿名
            $order_video = json_decode($order_video, true) ?? [];
            if ($order_video) {
                $order_video['thumb'] = $this->substituteSuffixes($order_video['url'] ?? '', '.jpg');
            }
        }

        $productArr = json_decode($product_json, true);
        if (is_array($productArr)) {
            array_walk($productArr, function (&$value2, $index2) {
                if (!array_key_exists('id', $value2) || !array_key_exists('product_attr_id', $value2)) throw new ApiException(\PhalApi\T('商品json数据格式不正确!'), 400);
                if (!array_key_exists('tags', $value2)) {
                    $value2['tags'] = '';
                }
                if (!in_array($value2['tags'], [-1, 0, 1])) {
                    $value2['tags'] = '';
                }
            });
        }

        \PhalApi\DI()->notorm->beginTransaction('db_master');
        try {
            if ($rider_id > 0) {

                $riderAdd = [
                    'uid' => $uid,
                    'rid' => $rider_id,
                    'content' => $rider_comment,
                    'star' => $rider_star,
                    'cityid' => 1,
                    'store_oid' => $id,
                    'store_id' => $order['store_id'],
                    'is_anonymous' => $rider_anonymous,
                    'addtime' => time(),
                ];
                $RiderModel->saveEvaluateOne($riderAdd);

                $Model_User = new Model_User();
                $where = [
                    'id' => $rider_id
                ];
                $Model_User->upStar($where, $rider_star, 1);
            }

            if ($order_id > 0) {
                $orderAdd = [
                    'uid' => $uid,
                    'oid' => $id,
                    'content' => $order_comment,
                    'store_id' => $order['store_id'],
                    'taste_star' => $order_taste_star,
                    'overall_star' => $order_overall_star,
                    'packaging_star' => $order_packaging_star,
                    'pics' => json_encode($order_pics),
                    'video' => json_encode($order_video),
                    'addtime' => time(),
                    'is_anonymous' => $order_anonymous,
                ];
//                var_dump($orderAdd);
                $this->saveOne($orderAdd);

            }
            if (is_array($productArr)) {

                foreach ($productArr as $key => $value) {
                    $pics = $value['pics'] ?? [];
                    $video = $value['video'] ?? [];
                    if ($video) {
                        $video['thumb'] = $this->substituteSuffixes($video['url'], '.jpg');
                    }
                    $comment = $value['comment'] ?? '';
                    $tags = $value['tags'] ?? '';

                    //美食
                    if ($storeInfo['top_type_id'] == 1) {
                        $comment = $order_comment;
                        $pics = $order_pics;
                        $video = $order_video;
                    }

                    $addTmp = [
                        'uid' => $uid,
                        'oid' => $id,
                        'product_id' => $value['id'],
                        'product_attr_id' => $value['product_attr_id'],
                        'comment' => $comment,
                        'pics' => json_encode($pics),
                        'video' => json_encode($video),
                        'overall_star' => $order_overall_star,
                        'store_id' => $order['store_id'],
                        'addtime' => time(),
                        'is_anonymous' => $order_anonymous,
                        'tags' => $tags,
                    ];
                    $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
                    $MerchantStoreProductReplyDomain->saveOne($addTmp);

                    $MerchantStoreProductDomain = new MerchantStoreProductDomain();//更新商品销量,评分
                    $MerchantStoreProductDomain->updateSales($value['id']);
                }
            }

        } catch (\Exception $exception) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 400;
            $rs['msg'] = \PhalApi\T($exception->getMessage());
            return $rs;
        }
        \PhalApi\DI()->notorm->commit('db_master');

        $MerchantStoreDomain = new MerchantStoreDomain();//更新店铺商品销量,评分
        $MerchantStoreDomain->updateScore($order['store_id']);

        $rs['msg'] = \PhalApi\T('评价成功');
        return $rs;
    }


    /**
     * 获取订单待评价的信息
     * @param $uid
     * @param $id 订单id
     * @return void
     */
    public function getEvaluate($uid, $id)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $this->testOrderEvaluation($uid, $id);

        $MerchantStoreOrderModel = new MerchantStoreOrderModel();
        $order = $MerchantStoreOrderModel->getOne(['id = ?' => $id, 'uid = ?' => $uid], 'id,status,end_time,shipping_type,delivery_uid,shipping_type,store_id,top_type_id');
        $rider = [];
        if ($order['shipping_type'] == 1 && $order['delivery_uid'] > 0) {
            $RiderModel = new RiderModel();
            $rider = $RiderModel->getOne(['id = ?' => $order['delivery_uid']], 'id,user_nickname,avatar');
            $rider['avatar'] = \App\get_upload_path($rider['avatar']);
            $rider['end_time'] = date("m" . \PhalApi\T('月') . "d" . \PhalApi\T('日') . " H:i");
            $rider['shipping_type_txt'] = $order['shipping_type'] == 1 ? \PhalApi\T('平台配送') : \PhalApi\T('到店自提');
        }

        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne(['id = ?' => $order['store_id']], 'id,name,th_name,thumb,top_type_id');
        $store['thumb'] = \App\get_upload_path($store['thumb']);

        $MerchantStoreOrderCartInfoDomain = new MerchantStoreOrderCartInfoDomain();

        $product_cart = $MerchantStoreOrderCartInfoDomain->selectList(['oid = ?' => $order['id']], 'id,product_id,product_attr_id');
        $MerchantStoreProductDomain = new MerchantStoreProductDomain();
        foreach ($product_cart as &$value) {
            $product = $MerchantStoreProductDomain->getOne(['id = ?' => $value['product_id']], 'name,th_name,image,price,id');
            $image = json_decode($product['image'], true);
            array_walk($image, function (&$value2, $key2) {
                $value2 = \App\get_upload_path($value2);
            });
            $product['image'] = $image[0] ?? '';

            $value['product'] = $product;

        }
        $rs['info'][0] = compact('rider', 'store', 'product_cart', 'order');
        return $rs;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $MerchantStoreOrderEvaluateModel = new MerchantStoreOrderEvaluateModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreOrderEvaluateModel, $name], $arguments);
    }
}