<?php

namespace App\Domain;

use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Domain\City as CityDomain;
use Rider\Domain\Evaluate as EvaluateDomain;
use App\Domain\MerchantStoreProduct as MerchantStoreProductDomain;
use App\Domain\MerchantType as MerchantTypeDomain;
use App\Model\MerchantStoreCircle as MerchantStoreCircleModel;
use App\Model\MerchantStoreOrderEvaluate as MerchantStoreOrderEvaluateModel;
use App\Model\MerchantStoreProduct as MerchantStoreProductModel;
use App\Model\MerchantStoreType as MerchantStoreTypeModel;
use App\Domain\User as UserDomain;
use App\Model\MerchantStore as MerchantStoreModel;
use App\Domain\MerchantStoreCart as MerchantStoreCartDomain;
use App\Domain\MerchantStoreCard as MerchantStoreCardDomain;
use App\Model\MerchantStoreIndustry as MerchantStoreIndustryModel;
use App\Model\MerchantType as MerchantTypeModel;
use App\Model\MerchantStorePickup as MerchantStorePickupModel;
use App\Domain\MerchantStoreOrder as MerchantStoreOrderDomain;
use App\Domain\MerchantStoreOrderEvaluate as MerchantStoreOrderEvaluateDomain;
use App\Domain\MerchantStoreProductReply as MerchantStoreProductReplyDomain;


/**
 * @method array getOne(array $where, string $field)
 */
class MerchantStore
{


    /**
     * 计算店铺评价数据
     * @param $store_id
     * @return array
     */
    public function getEvaluationOfStatistical($store_id)
    {
        $MerchantStoreModel = new MerchantStoreModel();

        $field = 'id,name,th_name,stars,sales,remark';
        $store = $MerchantStoreModel->getOne(['id = ?' => $store_id], $field);

        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $otherStars = $MerchantStoreOrderEvaluateDomain->getOne(['store_id = ?' => $store_id], 'count(id) as count,(sum(taste_star)/count(id)) as taste_star,(sum(packaging_star)/count(id)) as packaging_star,(sum(distribution_star)/count(id)) as distribution_star,sum(distribution_star) as distribution_star_sum');
        $EvaluateDomain = new EvaluateDomain();
        $distribution_star = $EvaluateDomain->getAverage(['store_id = ?' => $store_id]);

        $statistical = [
            'stars' => sprintf("%01.1f", $store['stars']),
            'taste_star' => sprintf("%01.1f", $otherStars['taste_star']),
            'packaging_star' => sprintf("%01.1f", $otherStars['packaging_star']),
            'distribution_star' => sprintf("%01.1f", $distribution_star),
        ];
        return $statistical;
    }


    /**
     * 重新计算店铺的点评数,评分,销量等
     * @param $store_id
     * @return void
     */
    public function updateScore($store_id)
    {
        $MerchantStoreModel = new MerchantStoreModel();

        $field = 'id,name,th_name,stars,sales,remark';
        $detail = $MerchantStoreModel->getOne(['id = ?' => $store_id], $field);
        if ($detail) {
            $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
            $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
            $stars = $MerchantStoreOrderEvaluateDomain->getOne(['store_id = ?' => $store_id], '(sum(overall_star)/count(id)) as overall_star');
            //总体星级评分(订单总体评价平均分)
            $stars = round($stars['overall_star'], 1);

            //销量(订单中已完成的数量)
            $sales = $MerchantStoreOrderDomain->getCountOne(['store_id = ?' => $store_id, 'status = ?' => 4], 'sum(total_num) as total_num')['total_num'] ?? 0;
            //(订单中已点评过的)
            $remark = $MerchantStoreOrderEvaluateDomain->getCount(['store_id = ?' => $store_id, 'is_show = ?' => 1]);

            $update = [
                'stars' => $stars,
                'sales' => $sales,
                'remark' => $remark,
            ];
            $MerchantStoreModel->updateOne(['id = ?' => $store_id], $update);

        }
    }


    /**
     * 店铺自提点
     * @param ...$param
     * @return mixed
     */
    public function getPickup(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id] = $param;
        $MerchantStorePickupModel = new MerchantStorePickupModel();
        $list = $MerchantStorePickupModel->selectList(['store_id = ?' => $store_id]);
        return $list;
    }

    /**
     * 服务店铺详情
     * @param ...$param
     * @return array|mixed
     */
    public function getServeDetail(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id, $lng, $lat] = $param;
        $MerchantStoreModel = new MerchantStoreModel();
        $field = 'id,name,th_name,banner,operating_state,lng,lat,stars,remark,open_date,open_time,address,about,environment';
        $detail = $MerchantStoreModel->getOne(['id = ?' => $store_id], $field);
        if (!$detail) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }
        if ($detail['operating_state'] == 1) {
            $detail['operating_state'] = $this->isOpen($detail['open_date'], $detail['open_time']);
        }
        $distance = '--';
        if ($lng && $lat && $detail['lng'] && $detail['lat']) {
            $distance = \App\getDistance($lng, $lat, $detail['lng'], $detail['lat']);
            $distance = round($distance, 2) . 'km';
        }
        $detail['distance'] = $distance;
        $detail['open_date'] = json_decode($detail['open_date'], true) ?? [];
        $detail['open_time'] = json_decode($detail['open_time'], true) ?? [];
        $detail['open_time'] = implode('-', $detail['open_time']);
        $detail['open_date'] = $this->dateOfFormatting($detail['open_date']);

        $detail['banner'] = json_decode($detail['banner'], true) ?? [];
        $banner = [];
        array_walk($detail['banner'], function ($value, $index) use (&$banner) {
            array_push($banner, \App\get_upload_path($value));
        });
        $detail['banner'] = $banner;

        $detail['environment'] = json_decode($detail['environment'], true) ?? [];
        $environment = [];
        array_walk($detail['environment'], function ($value, $index) use (&$environment) {
            array_push($environment, \App\get_upload_path($value));
        });
        $detail['environment'] = $environment;


        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        $cart = $MerchantStoreCartDomain->getStatistics(['store_id = ?' => $detail['id'], 'uid = ?' => $uid]);
        $detail['cart'] = $cart;
        $rs['info'][] = $detail;
        //足迹

        $this->addPageView($uid, $detail['id']);
        return $rs;

    }

    /**
     * 获取当前类型下的所有店铺(平台分类下)
     * @param $type_id
     * @return array
     */
    public function getTypeOfStore($type_id)
    {
        $MerchantTypeDomain = new MerchantTypeDomain();
        $MerchantStoreDomain = new MerchantStoreDomain();
        $typeList = $MerchantTypeDomain->getSelfAndChildren($type_id);

        $typeIdArr = array_column($typeList, 'id');
        $storeList = $MerchantStoreDomain->inTypeSelectList($typeIdArr, [], 'id,name,th_name,type_id');
        return $storeList;
    }

    /**
     * 超市店铺首页
     * @param ...$param
     * @return void
     */
    public function getSupermarketStoreHome(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        [$uid, $store_id] = $param;
        $field = "id,name,th_name,thumb,address,stars,operating_state,banner,type_id,top_type_id,circle_id,lng,lat,phone,open_date,open_time";
        $MerchantStoreModel = new MerchantStoreModel();
        $detail = $MerchantStoreModel->getOne(['id = ?' => $store_id], $field);
        if (!$detail) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }
        if ($detail['operating_state'] == 1) {
            $detail['operating_state'] = $this->isOpen($detail['open_date'], $detail['open_time']);
        }
        $MerchantStoreTypeModel = new MerchantStoreTypeModel();
        $store_type = $MerchantStoreTypeModel->selectList(['store_id = ?' => $store_id, 'pid = ?' => 0], 'id,name,th_name,thumb');
        $store_type_product = $store_type;
        foreach ($store_type as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);

        }
        $MerchantStoreProductDomain = new MerchantStoreProductDomain();
        $recommend_product = $MerchantStoreProductDomain->getAllListByStore($uid, $store_id, 0, 4);
        foreach ($recommend_product as $key2 => &$value2) {
            $value2['cart_num'] = $MerchantStoreProductDomain->getCountInCart($uid, $store_id, $value2['id']);
            if (in_array($detail['top_type_id'], [1, 2, 3, 4, 8])) {
                $value2['repertory'] = 100000;
            }
        }

        foreach ($store_type_product as $key3 => &$value3) {
            $product = $MerchantStoreProductDomain->getListByStore($uid, $store_id, $value3['id'], 0, 0, true);

            if (count($product) <= 0) {
                unset($store_type_product[$key]);
                continue;
            }

            foreach ($product as $key4 => &$value4) {
                $value4['cart_num'] = $MerchantStoreProductDomain->getCountInCart($uid, $store_id, $value4['id']);
                if (in_array($value4['type_id'], [1, 2, 3, 4, 8])) {
                    $value4['repertory'] = 100000;
                }
            }
            $value3['product'] = $product;

        }
        $store_type_product = array_values($store_type_product);
        $rs['info'][] = compact('store_type', 'store_type_product', 'recommend_product');
        return $rs;
    }

    /**
     * 超市店铺详情
     * @param ...$param
     * @return array|void
     */
    public function getSupermarketDetail(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id] = $param;

        $field = "id,name,th_name,thumb,address,stars,banner,type_id,circle_id,lng,lat,phone,open_date,open_time,environment,operating_state";
        $MerchantStoreModel = new MerchantStoreModel();
        $detail = $MerchantStoreModel->getOne(['id = ?' => $store_id], $field);
        if (!$detail) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }
        if ($detail['operating_state'] == 1) {
            $detail['operating_state'] = $this->isOpen($detail['open_date'], $detail['open_time']);
        }
        $detail['banner'] = json_decode($detail['banner'], true) ?? [];
        $banner = [];
        array_walk($detail['banner'], function ($value, $index) use (&$banner) {
            array_push($banner, \App\get_upload_path($value));
        });
        $detail['thumb'] = \App\get_upload_path($detail['thumb']);
        $detail['banner'] = $banner;
        $detail['monthly_sales'] = $this->getMonthlySales($store_id);//月销售
        $detail['open_date'] = json_decode($detail['open_date'], true) ?? [];
        $detail['open_time'] = json_decode($detail['open_time'], true) ?? [];
        $detail['open_time'] = implode('-', $detail['open_time']);
        $detail['open_date'] = $this->dateOfFormatting($detail['open_date']);


        $industryModel = new MerchantStoreIndustryModel();
        $industryDetail = $industryModel->getOne(['store_id  = ?' => $store_id], 'business_image,license_image');
        $detail['industry'] = $industryDetail ?: [];
        $detail['industry']['business_image'] = json_decode($detail['industry']['business_image'], true) ?? [];
        $detail['industry']['license_image'] = json_decode($detail['industry']['license_image'], true) ?? [];
        $businessImageArr = [];
        array_walk($detail['industry']['business_image'], function ($value, $index) use (&$businessImageArr) {
            array_push($businessImageArr, \App\get_upload_path($value));
        });
        $detail['industry']['business_image'] = $businessImageArr;

        $licenseImageArr = [];
        array_walk($detail['industry']['license_image'], function ($value, $index) use (&$licenseImageArr) {
            array_push($licenseImageArr, \App\get_upload_path($value));
        });
        $detail['industry']['license_image'] = $licenseImageArr;

        $detail['environment'] = json_decode($detail['environment'], true) ?? [];
        $environment = [];
        array_walk($detail['environment'], function ($value2, $index) use (&$environment) {
            array_push($environment, \App\get_upload_path($value2));
        });
        $detail['environment'] = $environment;

        $MerchantStoreCartDomain = new MerchantStoreCartDomain();

        $cart = $MerchantStoreCartDomain->getStatistics(['store_id = ?' => $detail['id'], 'uid = ?' => $uid]);

        $MerchantTypeDomain = new MerchantTypeDomain();
        $supermarketTypeId = $MerchantTypeDomain->getSelfAndChildren(5);

        $this->addPageView($uid, $store_id);
        $detail['cart'] = $cart;
        $rs['info'][] = $detail;
        return $rs;
    }

    /**
     * 获取店铺的类型排名 (依据评分)
     * @param $store_id
     * @param $type_id
     * @return int
     */
    public function getRanking($store_id, $type_id)
    {
        $MerchantStoreModel = new MerchantStoreModel();
        $store = $MerchantStoreModel->getRanking($store_id, $type_id);
        return $store;
    }

    /**
     * 找店传照片
     * @param $store_id
     * @param $image
     * @return array
     */
    public function uploadPhoto($uid, $store_id, $image)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T('添加成功'), 'info' => []];
        $MerchantStoreModel = new MerchantStoreModel();
        $store = $MerchantStoreModel->getOne(['id = ?' => $store_id], 'type_id,banner');
        if (!$store) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺类型错误');
            return $rs;
        }

        $MerchantTypeDomain = new MerchantTypeDomain();
        $typeList = $MerchantTypeDomain->getSelfAndChildren(4);
        if (!in_array($store['type_id'], array_column($typeList, 'id'))) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺类型错误');
            return $rs;
        }

        $newImageArr = json_decode($image, true);
        if (!is_array($newImageArr)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('图片参数错误');
            return $rs;
        }

        $oldImageArr = json_decode($store['banner'], true);
        if (!is_array($oldImageArr)) {
            $oldImageArr = [];
        }
        $imageArr = array_merge($newImageArr, $oldImageArr);
        $MerchantStoreModel->updateOne(['id = ?' => $store_id], ['banner' => json_encode(array_chunk($imageArr, 5)[0] ?? [])]);
        return $rs;
    }


    /**
     * 获取店铺列表
     * @return array
     */
    public function getList($where, $field = '*', $p = 0, $limit = 20)
    {
        $MerchantStoreModel = new MerchantStoreModel();

        $list = $MerchantStoreModel->selectList($where, $field, $p, $limit);
        foreach ($list as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
        }
        return $list;
    }

    /**
     * 店铺搜索(平台分类)
     * @param ...$param
     * @return array
     */
    public function getFoodTypeSearchList(...$param)
    {
        [$uid, $lng, $lat, $type_id, $keywords, $p] = $param;
        $MerchantTypeDomain = new MerchantTypeDomain();
        $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);
        $type_id_arr = array_column($storeTypelist, 'id');
        $MerchantStoreModel = new MerchantStoreModel();

        $list = $MerchantStoreModel->cateSelectList($lng, $lat, $type_id_arr, $keywords, $p, 20);

        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();

        foreach ($list as $key => &$value) {
            $value['distance'] = round($value['distance'], 2) . 'km';//距离(千米)
            $value['evaluate'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['store_id = ?' => $value['id']]);//商品评价
            if ($value['sales'] > 500) {//商品销量
                $value['sales'] = '500+';
            }
            $value['monthly_sales'] = $this->getMonthlySales($value['id']);//月销售
            $time = $this->getPresetTime($value['lng'], $value['lat'], $lng, $lat);
            $value['time'] = \App\handellength(round($time * 60));//预计送达时间

            $value['thumb'] = \App\get_upload_path($value['thumb']);

            $typeInfo = $MerchantTypeModel->getOne(['id = ? ' => $value['type_id'] ?? 0], 'name,th_name');
            $value['type_name'] = $typeInfo['use_name'] ?? '--';

            unset($value['lng']);
            unset($value['lat']);
        }
        return $list;

    }

    /**
     * 店铺详情(找店)
     * @param ...$param
     * @return array
     */
    public function getLookShopStoreDetail(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id] = $param;

        $field = "id,name,th_name,thumb,address,stars,banner,operating_state,taste_score,environment,environment_score,service_score,type_id,circle_id,per_capita,lng,lat";
        $MerchantStoreModel = new MerchantStoreModel();
        $detail = $MerchantStoreModel->getOne(['id = ?' => $store_id], $field);
        if (!$detail) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }

        if ($detail['operating_state'] == 1) {
            $detail['operating_state'] = $this->isOpen($detail['open_date'], $detail['open_time']);
        }

        $detail['banner'] = json_decode($detail['banner'], true) ?? [];

        $banner = [];
        array_walk($detail['banner'], function ($value, $index) use (&$banner) {
            array_push($banner, \App\get_upload_path($value));
        });
        $detail['thumb'] = \App\get_upload_path($detail['thumb']);
        $detail['banner'] = $banner;

        $detail['environment'] = json_decode($detail['environment'], true) ?? [];
        $environment = [];
        array_walk($detail['environment'], function ($value2, $index) use (&$environment) {
            array_push($environment, \App\get_upload_path($value2));
        });
        $detail['environment'] = $environment;

        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $detail['circle_id']], 'id,name,th_name');
        $typeInfo = $MerchantTypeModel->getOne(['id = ?' => $detail['type_id']], 'id,name,th_name');
        $detail['circle_name'] = $Circle['use_name'] ?? '--';//商圈名
        $detail['type_name'] = $typeInfo['use_name'] ?? '--';//店铺类型
        $MerchantStoreProductModel = new MerchantStoreProductModel();

        $recommend = $MerchantStoreProductModel->selectList(['recommend = ?' => 1, 'store_id = ?' => $detail['id']], 'image,name,th_name', 'id DESC', 0, 5);
        foreach ($recommend as $key => &$value) {
            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value2, $key2) {
                $value2 = \App\get_upload_path($value2);
            });
            $value['image'] = $image[0] ?? '';
        }


        $MerchantStoreCardDomain = new MerchantStoreCardDomain();
        $card = $MerchantStoreCardDomain->getOne(['store_id = ?' => $detail['id'], 'uid = ?' => $uid]);
        $detail['card'] = $card ? 1 : 0;

        $detail['recommend']['count'] = count($recommend);
        $detail['recommend']['list'] = $recommend;
   
        //足迹
   
        $this->addPageView($uid, $store_id);
        $rs['info'][] = $detail;
        return $rs;
    }

    /**
     * 店铺月销售数量
     * @param $productId
     * @return int
     */
    public function getMonthlySales($storeId)
    {
        $daysAgo = strtotime('-30day');
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $count = $MerchantStoreOrderDomain->getOne(['store_id = ?' => $storeId, 'status = ?' => 4, 'add_time >= ?' => $daysAgo], 'sum(total_num) as total_num')['total_num'] ?? 0;
        return $count;
    }

    /**
     * 店铺详情(美食)
     * @param ...$param
     * @return array
     */
    public function getStoreDetail(...$param)
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        [$uid, $id, $lng, $lat] = $param;
        $field = "id,name,operating_state,thumb,background,environment,address,open_date,open_time,phone,stars,banner,lng,lat,remark,circle_id,
                (
                        6378.138 * 2 * ASIN(
                            SQRT(
                                POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                            ) 
                        ) 
                    ) AS distance";
        $MerchantStoreModel = new MerchantStoreModel();
        $detail = $MerchantStoreModel->getOne(['id = ?' => $id], $field);
      
        if (!$detail) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }
        $industryModel = new MerchantStoreIndustryModel();
        $industryDetail = $industryModel->getOne(['store_id  = ?' => $id], 'id,business_image,license_image');
        $detail['industry'] = $industryDetail ?: [];
        $detail['thumb'] = \App\get_upload_path($detail['thumb']);
        $detail['background'] = \App\get_upload_path($detail['background']);
        if ($detail['operating_state'] == 1) {
            $detail['operating_state'] = $this->isOpen($detail['open_date'], $detail['open_time']);
        }
        $detail['open_date'] = json_decode($detail['open_date'], true) ?? [];
        $detail['open_time'] = json_decode($detail['open_time'], true) ?? [];
        $detail['open_time'] = implode('-', $detail['open_time']);
        $detail['open_date'] = $this->dateOfFormatting($detail['open_date']);

        $detail['distance'] = round($detail['distance'], 2) . 'km';
        $time = $this->getPresetTime($detail['lng'], $detail['lat'], $lng, $lat);
        $detail['time'] = \App\handellength2(round($time * 60));//预计送达时间
        $detail['month_sales'] = $this->getMonthlySales($id);//月销量
        $detail['evaluate_count'] = $detail['remark'];//评价数
        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $detail['circle_id']], 'id,name,th_name');
        $detail['circle_ranking'] = $Circle['use_name'] . \PhalApi\T('第') . $this->getCircleRanking($id, $detail['circle_id']) . \PhalApi\T('名');//商圈名次
        $detail['industry']['business_image'] = json_decode($detail['industry']['business_image'], true) ?? [];
        $detail['industry']['license_image'] = json_decode($detail['industry']['license_image'], true) ?? [];
        $detail['banner'] = json_decode($detail['banner'], true) ?? [];

        $businessImageArr = [];
        array_walk($detail['industry']['business_image'], function ($value, $index) use (&$businessImageArr) {
            array_push($businessImageArr, \App\get_upload_path($value));
        });
        $detail['industry']['business_image'] = array_filter($businessImageArr);

        $licenseImageArr = [];
        array_walk($detail['industry']['license_image'], function ($value, $index) use (&$licenseImageArr) {
            array_push($licenseImageArr, \App\get_upload_path($value));
        });
        $detail['industry']['license_image'] = array_filter($licenseImageArr);

        $banner = [];
        array_walk($detail['banner'], function ($value, $index) use (&$banner) {
            array_push($banner, \App\get_upload_path($value));
        });
        $detail['banner'] = $banner;

        $detail['environment'] = json_decode($detail['environment'], true) ?? [];
        $environment = [];
        array_walk($detail['environment'], function ($value2, $index) use (&$environment) {
            array_push($environment, \App\get_upload_path($value2));
        });
        $detail['environment'] = array_filter($environment);

    
        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        $cart = $MerchantStoreCartDomain->getStatistics(['store_id = ?' => $detail['id'], 'uid = ?' => $uid]);
        $detail['cart'] = $cart;
        $UserDomain = new UserDomain();
        $account = $UserDomain->getInfo(['store_id = ?' => $id], 'id');
        $service = [
            'im_uid' => isset($account['id']) ? "mer_{$account['id']}" : 0
        ];
        $detail['service'] = $service;
 

 
        //评价统计
        $detail['evaluate'] = $this->getEvaluationOfStatistical($detail['id']);

        $this->addPageView($uid, $id);
        $rs['info'][] = $detail;
        return $rs;
    }


    /**
     * 是否在营业时间内
     * @param $openDate
     * @param $openTime
     * @return int
     */
    public function isOpen($openDate, $openTime)
    {

        $openDateArr = json_decode($openDate, true) ?? [];
        $openTimeArr = json_decode($openTime, true) ?? [];

        if (!$openDateArr || !$openTimeArr) {
            return 0;
        }

        $time = time();
        $week = date('w', $time);
        if ($week == 0) $week = 7;
        if (!in_array($week, $openDateArr)) {
            return 0;
        }
        $startTime = strtotime(date("Y-m-d") . " {$openTimeArr[0]}");
        $endTime = strtotime(date("Y-m-d") . " {$openTimeArr[1]}");

        if ($time < $startTime || $time > $endTime) {
            return 0;
        }
        return 1;
    }


    /**
     * 格式化日期
     * @return void
     */
    public function dateOfFormatting($date)
    {
        $array = [
            1 => '一',
            2 => '二',
            3 => '三',
            4 => '四',
            5 => '五',
            6 => '六',
            7 => '日',
        ];
        $str = '';
        asort($date);
        array_walk($date, function ($value) use (&$str, $array) {
            if (array_key_exists($value, $array)) {
                $str .= $array[$value] . ',';
            }
        });
        if ($str) {
            $str = \PhalApi\T('星期') . $str;
        }

        return $str;
    }


    /**
     * 增加店铺的浏览量
     * @return void
     */
    public function addPageView($uid, $storeId)
    {
        $rediskey = 'uid_store_view_' . $uid . '_' . $storeId;
        if (!\App\getcaches($rediskey)) {
            $MerchantStoreModel = new MerchantStoreModel();

            $MerchantStoreModel->updateOne(['id = ?' => $storeId], array('views_day' => new \NotORM_Literal("views_day + 1")));
        }
        \App\setcaches($rediskey, 1, 60 * 60);

    }


    /**
     * 获取送达时长
     * @param $lng1 位置1 经度
     * @param $lat1 位置1 维度
     * @param $lng2 位置2 经度
     * @param $lat2 位置2 维度
     * @return float|int|mixed
     */
    public function getPresetTime($lng1, $lat1, $lng2, $lat2)
    {
        $CityDomain = new CityDomain();
        $config = $CityDomain->getConfig(1);
        //两点间的直线距离
        $lineDistance = \App\getDistance($lng1, $lat1, $lng2, $lat2);
        $distance_basic = $config['distance_basic'];//起始距离配送时长

        if ($lineDistance <= $distance_basic) {
            $time = $config['distance_basic_time'];
            return $time;
        } else {
            $time = $config['distance_basic_time'] + (($lineDistance - $distance_basic) * $config['distance_more_time']);
            return $time;
        }
    }

    /**
     * 获取推荐的店铺
     * @param ...$param
     * @return array
     */
    public function getRecommend(...$param)
    {
        /**
         * $type_id 店铺类型
         * $circle_id 所属商圈
         * $number 查询数量
         */
        [$lng, $lat, $type_id, $circle_id, $p, $number] = $param;

        $MerchantTypeDomain = new MerchantTypeDomain();
        $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);
        $MerchantStoreModel = new MerchantStoreModel();
        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();

        $where = [];
        if ($circle_id > 0) {
            $where['circle_id = ?'] = $circle_id;
        }
        $where['recommend = ?'] = 1;
        $recommendList = $MerchantStoreModel->inTypeSelectList(array_column($storeTypelist, 'id'), $where, 'id,name,th_name,type_id,thumb,stars,sales,up_to_send', 'list_order ASC', $p, $number);
        foreach ($recommendList as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['distance'] = round($value['distance'], 2) . 'km';//距离(千米)
            $value['evaluate'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['store_id = ?' => $value['id']]);//商品评价
            if ($value['sales'] > 500) {//商品销量
                $value['sales'] = '500+';
            }
            $value['monthly_sales'] = $this->getMonthlySales($value['id']);//月销售
            $time = $this->getPresetTime($value['lng'], $value['lat'], $lng, $lat);
            $value['time'] = \App\handellength(round($time * 60));//预计送达时间

            $value['thumb'] = \App\get_upload_path($value['thumb']);

            $typeInfo = $MerchantTypeModel->getOne(['id = ? ' => $value['type_id'] ?? 0], 'name,th_name');
            $value['type_name'] = $typeInfo['use_name'] ?? '--';

            unset($value['lng']);
            unset($value['lat']);
        }


        return $recommendList;
    }

    /**
     * 获取[找店]吃喝玩乐的店铺
     * @param ...$param
     * @return array
     */
    public function getBeerAndSkittles(...$param)
    {
        /**
         * $type_id 店铺类型
         * $circle_id 所属商圈
         * $number 查询数量
         */
        [$type_id, $circle_id, $number] = $param;

        $MerchantTypeDomain = new MerchantTypeDomain();
        $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);
        $MerchantStoreModel = new MerchantStoreModel();
        $where = [];
        if ($circle_id > 0) {
            $where['circle_id = ?'] = $circle_id;
        }
        $where['beer_and_skittles = ?'] = 1;
        $recommendList = $MerchantStoreModel->inTypeSelectList(array_column($storeTypelist, 'id'), $where, 'id,name,thumb', 'list_order ASC', 0, $number);
        foreach ($recommendList as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
        }
        return $recommendList;
    }

    /**
     * 根据距离查询店铺(分页)
     * @param ...$param
     * @return array
     */
    public function getSelectListByDistancePage(...$param)
    {
        [$lng, $lat, $type_id, $overall, $distanc, $evaluate, $keywords, $p, $circle_id] = $param;
        $MerchantTypeDomain = new MerchantTypeDomain();
        $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);

        $MerchantStoreModel = new MerchantStoreModel();
        $list = $MerchantStoreModel->distanceSelectListPage($lng, $lat, array_column($storeTypelist, 'id'), $overall, $distanc, $evaluate, $keywords, $circle_id, $p, 20, 'id,`name`,th_name,thumb,remark,stars,type_id,circle_id,per_capita');

        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        $MerchantTypeModel = new MerchantTypeModel();

        foreach ($list as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['distance'] = round($value['distance'], 1) . 'km';
            $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $value['circle_id']], 'id,name,th_name');

            $typeInfo = $MerchantTypeModel->getOne(['id = ?' => $value['type_id']], 'id,name,th_name');
            $rank = $MerchantStoreModel->getRanking($value['id'], $value['type_id']);
            $value['rank'] = $typeInfo['use_name'] . \PhalApi\T('排名第') . $rank;
            $value['circle_name'] = $Circle['use_name'] ?? '--';//商圈名
            $value['type_name'] = $typeInfo['use_name'] ?? '--';//店铺类型
            $value['comment'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['store_id = ?' => $value['id']]);//点赞最多的评论
            $value['comment_count'] = $value['remark'];//评论数量
            if ($value['comment_count'] > 99) {
                $value['comment_count'] = '99+';//评论数量
            }
            unset($value['circle_id']);
            unset($value['type_id']);
            unset($value['th_name']);
        }
        return $list;
    }


    /**
     * 根据距离查询店铺(查询固定数量)
     * @param ...$param
     * @return array
     */
    public function getSelectListByDistance(...$param)
    {
        [$lng, $lat, $type_id, $number] = $param;
        $MerchantTypeDomain = new MerchantTypeDomain();
        $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);

        $MerchantStoreModel = new MerchantStoreModel();
        $list = $MerchantStoreModel->distanceSelectList($lng, $lat, [], array_column($storeTypelist, 'id'), 'id,name,thumb,stars,per_capita,type_id,store_area_id,top_type_id,circle_id,lng,lat,up_to_send', 'distance ASC', 1, $number ?? 10);

        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $MerchantStoreOrderEvaluateModel = new MerchantStoreOrderEvaluateModel();


        foreach ($list as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['distance'] = round($value['distance'], 1) . 'km';
            $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $value['circle_id']], 'id');
            $typeInfo = $MerchantTypeModel->getOne(['id = ?' => $value['type_id']], 'id,name');
            $time = $this->getPresetTime($value['lng'], $value['lat'], $lng, $lat);
            $value['time'] = \App\handellength2(round($time * 60));//预计送达时间
            $value['monthly_sales'] = $this->getMonthlySales($value['id']);//月销售
            if ($value['top_type_id'] != 4) {//非找店类型店铺需要计算
                $value['per_capita'] = $MerchantStoreOrderDomain->getPerCost($value['id']);//人均消费
            }
            $value['circle_name'] = $Circle['use_name'] ?? '--';//商圈名
            $value['type_name'] = $typeInfo['use_name'] ?? '--';//店铺类型
            $value['comment'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['store_id = ?' => $value['id']]);//点赞最多的评论
            $value['comment_count'] = $MerchantStoreOrderEvaluateModel->getCount(array_merge(['store_id = ?' => $value['id']], []));///订单评论数量
            if ($value['comment_count'] > 99) {
                $value['comment_count'] = '99+';//评论数量
            }
        }
        return $list;
    }


    public function distanceSelect(...$param)
    {
        [$lng, $lat, $p, $number] = $param;
        $MerchantStoreModel = new MerchantStoreModel();
        $list = $MerchantStoreModel->distanceSelect($lng, $lat, [], 'id,name,thumb,stars,type_id,top_type_id,circle_id', 'distance ASC', $p, $number);

        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        $MerchantStoreOrderEvaluateModel = new MerchantStoreOrderEvaluateModel();

        foreach ($list as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['distance'] = round($value['distance'], 1) . 'km';
            $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $value['circle_id']], 'id,name,th_name');
            $typeInfo = $MerchantTypeModel->getOne(['id = ?' => $value['type_id']], 'id,name,th_name');

            $value['month_sales'] = $this->getMonthlySales($value['id']);//月销量

            if ($value['top_type_id'] != 4) {//非找店类型店铺需要计算
                $value['per_capita'] = $MerchantStoreOrderDomain->getPerCost($value['id']);//人均消费
            }
            $value['circle_name'] = $Circle['use_name'] ?? '--';//商圈名
            $value['type_name'] = $typeInfo['use_name'] ?? '--';//店铺类型
            $value['comment'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['store_id = ?' => $value['id']]);//点赞最多的评论
            $value['comment_count'] = $MerchantStoreOrderEvaluateModel->getCount(array_merge(['store_id = ?' => $value['id']], []));///订单评论数量

            if ($value['comment_count'] > 99) {
                $value['comment_count'] = '99+';//评论数量
            }
        }
        return $list;
    }


    /**
     * 根据距离查询店铺(分页)
     * @param ...$param
     * @return array
     */
    public function selectListByDistance(...$param)
    {
        [$lng, $lat, $type_id, $p, $number] = $param;
        $MerchantTypeDomain = new MerchantTypeDomain();
        $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);

        $MerchantStoreModel = new MerchantStoreModel();
        $list = $MerchantStoreModel->distanceSelectList($lng, $lat, [], array_column($storeTypelist, 'id'), 'id,name,thumb,stars,type_id,top_type_id,store_area_id,circle_id', 'distance ASC', $p, $number ?? 10);

        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        $MerchantStoreOrderEvaluateModel = new MerchantStoreOrderEvaluateModel();

        foreach ($list as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['distance'] = round($value['distance'], 1) . 'km';
            $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $value['circle_id']], 'id,name,th_name');
            $typeInfo = $MerchantTypeModel->getOne(['id = ?' => $value['type_id']], 'id,name,th_name');

            $value['month_sales'] = $this->getMonthlySales($value['id']);//月销量

            if ($value['top_type_id'] != 4) {//非找店类型店铺需要计算
                $value['per_capita'] = $MerchantStoreOrderDomain->getPerCost($value['id']);//人均消费
            }
            $value['circle_name'] = $Circle['use_name'] ?? '--';//商圈名
            $value['type_name'] = $typeInfo['use_name'] ?? '--';//店铺类型
            $value['comment'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['store_id = ?' => $value['id']]);//点赞最多的评论
            $value['comment_count'] = $MerchantStoreOrderEvaluateModel->getCount(array_merge(['store_id = ?' => $value['id']], []));///订单评论数量

            if ($value['comment_count'] > 99) {
                $value['comment_count'] = '99+';//评论数量
            }
        }
        return $list;
    }

    /**
     * 根据距离查询推荐店铺
     * @param
     * @return array
     */
    public function getRecommendStoreList($lng, $lat, $type_id, $page, $number)
    {
        if ($type_id > 0) {
            $MerchantTypeDomain = new MerchantTypeDomain();
            $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);
            $where['type_id'] = array_column($storeTypelist, 'id');
        }

        $MerchantStoreModel = new MerchantStoreModel();
        $where['recommend = ?'] = 1;
        $where['top_type_id <> ?'] = 4;
        $list = $MerchantStoreModel->distanceSelect($lng, $lat, $where, 'id,name,thumb,stars,type_id,top_type_id,store_area_id,circle_id,per_capita', 'distance ASC', $page, $number ?? 20);

        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        $MerchantStoreOrderEvaluateModel = new MerchantStoreOrderEvaluateModel();

        foreach ($list as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['distance'] = round($value['distance'], 1) . 'km';
            $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $value['circle_id']], 'id,name,th_name');
            $typeInfo = $MerchantTypeModel->getOne(['id = ?' => $value['type_id']], 'id,name,th_name');
            $value['month_sales'] = $this->getMonthlySales($value['id']);//月销量

            if ($value['top_type_id'] != 4) {//非找店类型店铺需要计算
                $value['per_capita'] = $MerchantStoreOrderDomain->getPerCost($value['id']);//人均消费
            }
            $value['circle_name'] = $Circle['use_name'] ?? '--';//商圈名
            $value['type_name'] = $typeInfo['use_name'] ?? '--';//店铺类型
            $value['comment'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['store_id = ?' => $value['id']]);//点赞最多的评论
            $value['comment_count'] = $MerchantStoreOrderEvaluateModel->getCount(array_merge(['store_id = ?' => $value['id']], []));///订单评论数量
            if ($value['comment_count'] > 99) {
                $value['comment_count'] = '99+';//评论数量
            }
        }
        return $list;
    }

    /**
     * 根据距离查询推荐店铺
     * @param ...$param
     * @return array
     */
    public function getSelectListByRecommend(...$param)
    {
        [$lng, $lat, $type_id, $number] = $param;
        $MerchantTypeDomain = new MerchantTypeDomain();
        $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);

        $MerchantStoreModel = new MerchantStoreModel();
        $where['recommend = ?'] = 1;
        $list = $MerchantStoreModel->distanceSelectList($lng, $lat, $where, array_column($storeTypelist, 'id'), 'id,name,thumb,stars,type_id,top_type_id,store_area_id,circle_id,per_capita', 'distance ASC', 1, $number ?? 10);

        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        $MerchantStoreOrderEvaluateModel = new MerchantStoreOrderEvaluateModel();

        foreach ($list as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['distance'] = round($value['distance'], 1) . 'km';
            $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $value['circle_id']], 'id,name,th_name');
            $typeInfo = $MerchantTypeModel->getOne(['id = ?' => $value['type_id']], 'id,name,th_name');
            $value['month_sales'] = $this->getMonthlySales($value['id']);//月销量

            if ($value['top_type_id'] != 4) {//非找店类型店铺需要计算
                $value['per_capita'] = $MerchantStoreOrderDomain->getPerCost($value['id']);//人均消费
            }
            $value['circle_name'] = $Circle['use_name'] ?? '--';//商圈名
            $value['type_name'] = $typeInfo['use_name'] ?? '--';//店铺类型
            $value['comment'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['store_id = ?' => $value['id']]);//点赞最多的评论
            $value['comment_count'] = $MerchantStoreOrderEvaluateModel->getCount(array_merge(['store_id = ?' => $value['id']], []));///订单评论数量
            if ($value['comment_count'] > 99) {
                $value['comment_count'] = '99+';//评论数量
            }
        }
        return $list;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreProductModel = new MerchantStoreModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreProductModel, $name], $arguments);
    }
}