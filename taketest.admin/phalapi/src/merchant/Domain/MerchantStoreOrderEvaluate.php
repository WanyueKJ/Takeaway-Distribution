<?php

namespace Merchant\Domain;

use App\ApiException;
use Merchant\Model\MerchantStoreOrderEvaluate as MerchantStoreOrderEvaluateModel;
use Merchant\Model\Users as UsersModel;
use App\Domain\MerchantStore as AppMerchantStoreDomain;
use Merchant\Model\MerchantStoreOrder as MerchantStoreOrderModel;
use Merchant\Model\MerchantStoreOrderCartInfo as MerchantStoreOrderCartInfoModel;
use Merchant\Model\MerchantStoreProduct as MerchantStoreProductModel;
use Rider\Domain\Evaluate as EvaluateDomain;
use App\Domain\MerchantStoreProductReply as MerchantStoreProductReply;
use Rider\Model\User as Rider_User;

/**
 * 店铺评论
 */
class MerchantStoreOrderEvaluate
{


    public function getNumber($uid)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        $order = $this->checkStoreIdentity($uid);
        $orderId = $order['id'] ?? 0;
        $count = $this->getCount(['store_id = ?' => $orderId]);
        $rs['info'][] = $count;
        return $rs;
    }


    public function getDetail($uid, $id)
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('操作成功!'), 'info' => array());
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $info = $this->getOne(['id = ?' => $id, 'store_id = ?' => $store_id], 'id,uid,oid,`content`,taste_star,overall_star,packaging_star,pics,`like`,merchant_reply_content,merchant_reply_time,is_reply,addtime,video');
        if (!$info) {
            throw new ApiException(\PhalApi\T('评价不存在'));
        }
        $AppMerchantStoreDomain = new AppMerchantStoreDomain();
        $storeInfo = $AppMerchantStoreDomain->getOne(['id = ?' => $store_id], 'top_type_id');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺不存在!'));

    
  
        $orderId = $info['oid'];
        $pics = json_decode($info['pics'], true);
        foreach ($pics as &$value3) {
            $value3 = \App\get_upload_path($value3);
        }
        $info['pics'] = $pics;
        $info['video'] = json_decode($info['video'], true) ?? [];
        if ($info['video'] && isset($info['video']['url'])) {
            $info['video']['url'] = \App\get_upload_path($info['video']['url']);
            $info['video']['thumb'] = $this->substituteSuffixes($info['video']['url'], '.jpg');
        }


        $Rider_User = new Rider_User();

        $users = [];
        $UsersModel = new UsersModel();
        if ($info['uid'] > 0) {
            $users = $UsersModel->getOne(['id = ?' => $info['uid']], 'id,avatar,user_nickname');
            if ($users) $users['avatar'] = \App\get_upload_path($users['avatar']);
        }
        $info['users'] = $users ?? [];
        $productReplyWhere = [];
        if ($storeInfo['top_type_id'] == 1) {
            $productReplyWhere['tags'] = [1, -1];
        }
        $MerchantStoreOrderCartInfoModel = new MerchantStoreOrderCartInfoModel();
        $cart_info = $MerchantStoreOrderCartInfoModel->selectList(['oid = ?' => $info['oid']], 'product_id,cart_num,cart_info');
        $MerchantStoreOrderModel = new MerchantStoreOrderModel();
        $order = $MerchantStoreOrderModel->getOne(['id = ?' => $orderId], 'id,order_id,total_num,end_time');

        $EvaluateDomain = new EvaluateDomain();
        $riderelEvaluate = $EvaluateDomain->getInfo(['store_oid = ?' => $info['oid']], 'content,rid,star as distribution_star');
        
        $info['distribution_star'] = $riderelEvaluate['distribution_star'] ?? '';
        $info['rider_evaluate'] = $riderelEvaluate ?? [];
        if ($riderelEvaluate) {
            $rider = $Rider_User->getInfo(['id = ?' => $riderelEvaluate['rid']], 'user_nickname,avatar');
            $info['rider_evaluate']['end_time'] = date('Y-m-d H:i:s', $order['end_time']);
            if ($rider) $rider['avatar'] = \App\get_upload_path($rider['avatar']);
            $info['rider_evaluate']['rider'] = $rider;
        }

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        foreach ($cart_info as &$value) {
            $product = $MerchantStoreProductModel->getOne(['id = ?' => $value['product_id']], 'id,image,name,th_name,price');
            $product['use_price'] = json_decode($value['cart_info'], true)['product']['price'];
            $image = json_decode($product['image'], true);
            array_walk($image, function (&$product, $key) {
                $product = \App\get_upload_path($product);
            });
            $product['image'] = $image[0] ?? '';
            $value['product'] = $product;
            unset($value['cart_info']);
        }
        $info['cart_info'] = $cart_info;
        $info['order'] = $order;
        $info['merchant_reply_time'] = $info['merchant_reply_time'] > 0 ? \App\timeFormatting($info['merchant_reply_time'], $info['addtime']) : '';
        $info['addtime'] = date('Y-m-d H:i:s', $info['addtime']);
        $MerchantStoreProductReply = new MerchantStoreProductReply();

        $productReply = $MerchantStoreProductReply->selectList(array_merge(['oid = ?' => $info['oid']], $productReplyWhere), 'id,product_id,product_attr_id,tags,comment,pics,video');
        array_walk($productReply, function (&$valu) use ($MerchantStoreProductModel) {
            $product = $MerchantStoreProductModel->getOne(['id = ?' => $valu['product_id']], 'name,th_name,price,image');
            $image = json_decode($product['image'], true);
            array_walk($image, function (&$product, $key) {
                $product = \App\get_upload_path($product);
            });
            $product['image'] = $image[0] ?? '';
            $valu['prodcut'] = $product;

            $pics = json_decode($valu['pics'], true);
            foreach ($pics as &$value3) {
                $value3 = \App\get_upload_path($value3);
            }
            $valu['pics'] = $pics;
            $valu['video'] = json_decode($valu['video'], true) ?? [];
            if ($valu['video'] && isset($valu['video']['url'])) {
                $valu['video']['url'] = \App\get_upload_path($valu['video']['url']);
                $valu['video']['thumb'] = $this->substituteSuffixes($valu['video']['url'], '.jpg');
            }
        });
        $info['product'] = $productReply;
        $info['product_reply'] = $this->handleTags($productReply);


        $rs['info'][] = $info;
        return $rs;
    }

    /**
     * 商家回复评论
     * @param $uid
     * @param $id
     * @param $merchant_reply_content
     * @return void
     */
    public function businessReply($uid, $id, $merchant_reply_content)
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('操作成功!'), 'info' => array());
        $order = $this->checkStoreIdentity($uid);
        $orderId = $order['id'] ?? 0;
        $updateData = [
            'merchant_reply_content' => $merchant_reply_content,
            'merchant_reply_time' => time(),
            'is_reply' => 1
        ];

        $update = $this->updateOne(['id = ?' => $id, 'store_id = ?' => $orderId], $updateData);
        $info = $this->getOne(['id = ?' => $id, 'store_id = ?' => $orderId], 'addtime,merchant_reply_time');

        $rs['info'][0]['merchant_reply_time'] = '';
        if($info){
            $rs['info'][0]['merchant_reply_time'] = \App\timeFormatting($info['merchant_reply_time'],$info['addtime']);
        }

        return $rs;

    }

    /**
     * 订单评论列表
     * @param $uid
     * @param $reply 回复状态
     * @param $evaluate 是否好评
     * @param $content 是否有内容
     * @param $month 时间
     * @param $p
     * @return array
     * @throws ApiException
     */
    public function getEvaluateList($uid, $reply, $evaluate, $content, $month, $p)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        $order = $this->checkStoreIdentity($uid);
        $storeId = $order['id'] ?? 0;
        $where = [];
        $where['store_id = ?'] = $storeId;
        if ($reply == 1) {//已回复
            $where['is_reply = ?'] = 1;
        } else if ($reply == 2) {//未回复
            $where['is_reply = ?'] = 0;
        }

        if ($evaluate == 1) {//好评
            $where['overall_star'] = [5, 4.5, 4, 3.5, 3];
        } else if ($evaluate == 2) {//差评
            $where['overall_star'] = [1, 1.5, 2];
        }

        if ($content == 1) {//有内容
            $where['content <> ?'] = '';
        } else if ($content == 2) {//无内容
            $where['overall_star = ?'] = '';
        }
        $month = $month ?? 1;

        if ($month > 0) {
            $monthDate = $this->getMonthDate($month);
            $where['addtime >= ?'] = $monthDate[0];
            $where['addtime <= ?'] = $monthDate[1];
        }

        $UsersModel = new UsersModel();
        $MerchantStoreOrderModel = new MerchantStoreOrderModel();
        $MerchantStoreOrderCartInfoModel = new MerchantStoreOrderCartInfoModel();
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $list = $this->selectList($where, 'id,uid,oid,`content`,pics,video,taste_star,overall_star,packaging_star,`like`,merchant_reply_content,merchant_reply_time,video,is_reply,addtime,is_anonymous', 'id DESC', $p, 20);
        $count = $this->getCount($where);

        $AppMerchantStoreDomain = new AppMerchantStoreDomain();
        $evaluate = $AppMerchantStoreDomain->getEvaluationOfStatistical($storeId);
        $storeInfo = $AppMerchantStoreDomain->getOne(['id = ?' => $storeId], 'top_type_id');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺不存在!'));

        $EvaluateDomain = new EvaluateDomain();
        $MerchantStoreProductReply = new MerchantStoreProductReply();

        foreach ($list as &$value) {
            $users = [
                'avatar' => '',
            ];
            if ($value['uid'] > 0 && $value['is_anonymous'] == 0) {
                $users = $UsersModel->getOne(['id = ?' => $value['uid']], 'id,avatar,user_nickname');
                if ($users) $users['avatar'] = \App\get_upload_path($users['avatar']);
            }
            $value['users'] = $users ?? [];

            $order = $MerchantStoreOrderModel->getOne(['id = ?' => $value['oid']], 'id,order_id,total_num');
            $value['order'] = $order ?? [];

            $cart_info = $MerchantStoreOrderCartInfoModel->selectList(['oid = ?' => $value['oid']], 'product_id,product_attr_id,cart_num,cart_info');
            foreach ($cart_info as &$value3) {
                $cartInfoArr = json_decode($value3['cart_info'], true);
                $product = [
                    'use_name' => $cartInfoArr['product']['use_name'],
                    'use_price' => $cartInfoArr['product']['use_price']
                ];
                $productAttr = [
                    'use_name' => $cartInfoArr['product_attr']['use_attr_name'],
                    'use_price' => $cartInfoArr['product_attr']['use_price']
                ];
                $value3['product'] = $product;
                $value3['product_attr'] = $productAttr;
                unset($value3['cart_info']);
            }
            $value['cart_info'] = $cart_info;

            $pics = json_decode($value['pics'], true);
            foreach ($pics as &$value3) {
                $value3 = \App\get_upload_path($value3);
            }
            $value['pics'] = $pics;
            $value['video'] = json_decode($value['video'], true);
            if ($value['video'] && isset($value['video']['url'])) {
                $value['video']['url'] = \App\get_upload_path($value['video']['url']);
                $value['video']['thumb'] = $this->substituteSuffixes($value['video']['url'], '.jpg');
            }

            $riderelEvaluate = $EvaluateDomain->getInfo(['store_oid = ?' => $value['oid']], 'star as distribution_star');
            $value['distribution_star'] = $riderelEvaluate['distribution_star'] ?? '';

            $productReply = $MerchantStoreProductReply->selectList(['oid = ?' => $value['oid']], 'id,product_id,product_attr_id,tags');
            array_walk($productReply, function (&$valu) use ($MerchantStoreProductModel) {
                $product = $MerchantStoreProductModel->getOne(['id = ?' => $valu['product_id']], 'name,th_name,price');
                $valu['prodcut'] = $product;
            });
            $value['product_reply'] = $this->handleTags($productReply);
            $is_product_reply = 0;
            if (in_array($storeInfo['top_type_id'], [3, 5, 6, 7, 8])) {
                $isProductReply = $MerchantStoreProductReply->getOne(['oid = ?' => $value['oid'], 'comment <> ?' => ''], 'id');
                $is_product_reply = $isProductReply ? 1 : 0;
            }

            $value['is_product_reply'] = $is_product_reply;


            $value['merchant_reply_time'] = $value['merchant_reply_time'] > 0 ? \App\timeFormatting($value['merchant_reply_time'],$value['addtime']) : '';
            $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
        }

        $rs['info'][0] = $list;
        $rs['info'][1] = ['count' => $count, 'evaluate' => $evaluate];
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
     * 近几个月的开始结束时间
     * @param $number
     * @return array
     */
    public function getMonthDate($number = 1)
    {
        $time = time();
        $startTime = strtotime("-$number month", $time);
        $endTime = $time;

        if ($number > 1) {
            --$number;
            $endTime = strtotime("-$number month", $time);
        }
        return [
            $startTime,
            $endTime,
        ];
    }

    /**
     * 处理评价的状态分组
     * @param $list
     * @return array
     */
    public function handleTags($list)
    {
        $newList = [];
        foreach ($list as $value) {
            if (!array_key_exists((string)$value['tags'], $newList)) $newList[(string)$value['tags']] = [];
            array_push($newList[(string)$value['tags']], $value);
        }
        return $newList;
    }


    /**
     * 检测用户身份
     * @param $uid
     * @return array
     * @throws ApiException
     */
    public function checkStoreIdentity($uid)
    {
        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            throw new ApiException(\PhalApi\T('店铺信息错误!'), 995);
        }
        return $loginInfo['store'] ?? [];
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