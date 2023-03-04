<?php

namespace app\admin\controller\merchant;

use app\admin\model\merchant\StoreModel;
use app\admin\model\merchant\StoreIndustry;
use app\admin\model\merchant\StoreProduct;
use app\models\UsersModel;
use app\admin\model\merchant\TypeModel;
use app\admin\model\merchant\StoreCircle;
use cmf\controller\AdminBaseController;
use think\Db;
use tree\Tree;

/**
 * 店铺管理-店铺
 */
class StoreController extends AdminBaseController
{


    public function index()
    {
        $list = StoreModel::with(['storeType', 'account'])
            ->order('list_order ASC,id DESC')
            ->paginate(20);
        $list->each(function ($value) {
            $value['or_top_type_id'] = $value->getData('top_type_id');
        });
        $this->assign([
            'list' => $list,
            'page' => $list->render()
        ]);

        return $this->fetch();
    }


    public function add()
    {
        $data = $this->request->param();
        $topTypeID = $data['top_type_id'];
        $gaode_key = getConfigPri()['gaode_key'];
        $gaode_secret = getConfigPri()['gaode_secret'];
        $this->assign('gaode_key', ($gaode_key));
        $this->assign('gaode_secret', ($gaode_secret));

        $pid = $data['pid'] ?? 0;
        $this->assign('pid', $pid);

        $typelist = TypeModel::where('top_id', 'IN', ($topTypeID == 5) ? [5, 6, 7, 8] : $topTypeID)->select();
        $circleList = StoreCircle::getList();
        $this->assign('typelist', sort_list_tier($typelist));
        $this->assign('circleList', ($circleList));
        $this->assign('top_type_id', $topTypeID);


        return $this->fetch();
    }

    public function addPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (!$data['name']) $this->error('请填写名称');
            if (!$data['type_id'] ?? 0) $this->error('请选择店铺类型');
         
            $top_type_info = TypeModel::getTopInfo($data['type_id']);
            if (!$top_type_info) $this->error('店铺类型数据错误');
            if ($top_type_info['id'] != 4) {
                $data['per_capita'] = 0;
                $data['stars'] = 0;
                $data['taste_score'] = 0;
                $data['environment_score'] = 0;
                $data['service_score'] = 0;
            }

            $putaway = 0;
            if ($top_type_info['id'] == 4) {
                $putaway = 1;

                if ($data['stars'] < 0 || $data['stars'] > 5) $this->error('总评分(找店)0~5分');
                if ($data['taste_score'] < 0 || $data['taste_score'] > 5) $this->error('口味评分(找店)0~5分');
                if ($data['environment_score'] < 0 || $data['environment_score'] > 5) $this->error('环境评分(找店)0~5分');
                if ($data['service_score'] < 0 || $data['service_score'] > 5) $this->error('服务评分(找店) 0~5分');
            }

            if ($data['address'] && (!$data['lng'] || !$data['lat'])) $this->error('请点击选择详细地址');

            $newData = [
                'name' => $data['name'],
                'thumb' => $data['thumb'] ?? '',
                'address' => $data['address'] ?? '',
                'lng' => $data['lng'] ?? '',
                'lat' => $data['lat'] ?? '',
                'background' => $data['background'] ?? '',
                'per_capita' => $data['per_capita'],
                'stars' => $data['stars'],
                'taste_score' => $data['taste_score'],
                'environment_score' => $data['environment_score'],
                'service_score' => $data['service_score'],
                'addtime' => time(),
                'type_id' => $data['type_id'],
                'top_type_id' => $top_type_info['id'],
             
                'recommend' => $data['recommend'] ?? 0,
                'putaway' => $putaway,
                'about' => $data['about'] ?? '',
            ];

            $StoreModel = app()->make(StoreModel::class);
            $id = $StoreModel->allowfield([])->insertGetId($newData);

            $industryData = [
                'store_id' => $id,
                'name' => $data['person_name'] ?? '',
                'id_card' => $data['id_card'] ?? '',
                'id_card_image' => json_encode([$data['id_card_image_0'] ?? '', $data['id_card_image_1'] ?? '']),
                'registr_id' => $data['registr_id'] ?? '',
                'business_image' => json_encode([$data['business_image_0'] ?? '']),
                'license_number' => $data['license_number'] ?? '',
                'license_image' => json_encode([$data['license_image_0'] ?? '']),
                'addtime' => time()
            ];
            $StoreIndustry = app()->make(StoreIndustry::class);
            $StoreIndustry->save($industryData);
            if (!$id) {
                $this->error("添加失败！");
            }
            StoreModel::resetcache();
            $this->success("添加成功！");
        }
    }

    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $StoreModel = app()->make(StoreModel::class);

        $data = $StoreModel->with('industry')->where("id", $id)->find();
        if (!$data) {
            $this->error("信息错误");
        }
        $top_type_id = $data->getData('top_type_id');
        $data = $data->toArray();
        $data['environment'] = json_decode($data['environment'], true) ?? [];
        foreach ($data['environment'] as &$va) {
            $va = get_upload_path($va);
        }
        $data['thumb'] = get_upload_path($data['thumb']);
        $data['background'] = get_upload_path($data['background']);
        $industry = $data['industry'];
        $this->assign('data', $data);
        $this->assign('industry', $industry);

        $typelist = TypeModel::where('top_id',$top_type_id)->select();
      
        $this->assign('typelist', sort_list_tier($typelist));
       
        $gaode_key = getConfigPri()['gaode_key'];
        $gaode_secret = getConfigPri()['gaode_secret'];
        $this->assign('gaode_key', ($gaode_key));
        $this->assign('gaode_secret', ($gaode_secret));
        $this->assign('lng', ($data['lng']));
        $this->assign('top_type_id', $top_type_id);
        $this->assign('lat', ($data['lat']));

        return $this->fetch();
    }

    public function editPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $id = $data['id'] ?? 0;
            if (!$id) $this->error('参数错误');

            if (!$data['name']) $this->error('请填写名称');
            if (!$data['type_id'] ?? 0) $this->error('请选择店铺类型');
            
            $top_type_info = TypeModel::getTopInfo($data['type_id']);
            if (!$top_type_info) $this->error('店铺类型数据错误');
            $StoreModel = app()->make(StoreModel::class);
            $store = $StoreModel->where('id', $id)->find();
            if (!$store) $this->error('店铺信息不存在');

            if ($top_type_info['id'] != $store->getData('top_type_id')) {//修改店铺大类时检测
                $product = StoreProduct::where('store_id', $id)->find();
                if ($product) $this->error('请先清除店铺商品,才能修改店铺顶级类型');
            }
            if ($top_type_info['id'] != 4) {
                $data['per_capita'] = 0;
                $data['stars'] = 0;
                $data['taste_score'] = 0;
                $data['environment_score'] = 0;
                $data['service_score'] = 0;
            }
            if ($data['stars'] < 0 || $data['stars'] > 5) $this->error('总评分(找店)0~5分');
            if ($data['taste_score'] < 0 || $data['taste_score'] > 5) $this->error('口味评分(找店)0~5分');
            if ($data['environment_score'] < 0 || $data['environment_score'] > 5) $this->error('环境评分(找店)0~5分');
            if ($data['service_score'] < 0 || $data['service_score'] > 5) $this->error('服务评分(找店) 0~5分');
            if ($data['address'] && (!$data['lng'] || !$data['lat'])) $this->error('请点击选择详细地址');

            $environment = [];
            $environment[] = $data['environment_0'] ?? '';
            $environment[] = $data['environment_1'] ?? '';
            $environment[] = $data['environment_2'] ?? '';
            $environment[] = $data['environment_3'] ?? '';
            $environment[] = $data['environment_4'] ?? '';

            $newData = [
                'name' => $data['name'],
                'address' => $data['address'] ?? '',
                'environment' => json_encode($environment),
                'lng' => $data['lng'] ?? 0,
                'lat' => $data['lat'] ?? 0,
                'thumb' => $data['thumb'] ?? '',
                'background' => $data['background'] ?? '',
                'per_capita' => $data['per_capita'],
                'stars' => $data['stars'],
                'taste_score' => $data['taste_score'],
                'environment_score' => $data['environment_score'],
                'service_score' => $data['service_score'],
                'type_id' => $data['type_id'],
                'top_type_id' => $top_type_info['id'],
          
                'recommend' => $data['recommend'] ?? 0,
                'beer_and_skittles' => $data['beer_and_skittles'] ?? 0,
            ];

            $StoreModel->where('id', $id)->update($newData);

            $StoreIndustry = app()->make(StoreIndustry::class);
            $industryIsExist = $StoreIndustry->where('store_id', $id)->find();

            $industryData = [
                'name' => $data['person_name'] ?? '',
                'id_card' => $data['id_card'] ?? '',
                'id_card_image' => json_encode([$data['id_card_image_0'] ?? '', $data['id_card_image_1'] ?? '']),
                'registr_id' => $data['registr_id'] ?? '',
                'business_image' => json_encode([$data['business_image_0'] ?? '']),
                'license_number' => $data['license_number'] ?? '',
                'license_image' => json_encode([$data['license_image_0'] ?? '']),
            ];
            if ($industryIsExist) {
                $StoreIndustry->where('store_id', $id)->update($industryData);
            } else {
                $industryData['store_id'] = $id;
                $StoreIndustry->insert($industryData);
            }

            StoreModel::resetcache();
            $this->success("修改成功！");
        }
    }

    public function listOrder()
    {
        $model = app()->make(StoreModel::class);
        parent::listOrders($model);
        StoreModel::resetcache();
        $this->success("排序更新成功！");
    }

    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');

        $StoreProduct = app()->make(StoreProduct::class);
        $product = $StoreProduct->where('store_id', $id)->where('is_del', 0)->find();
        if ($product) $this->error("店铺下有商品,删除失败！");
        /** @var StoreModel $storeModel */
        $storeModel = app()->make(StoreModel::class);
        $storeModel->checkDelete($id);
        $rs = $storeModel->where('id', '=',$id)->delete();

        $storeModel::resetcache();
        $this->success("删除成功！");
    }


    /**
     * 商户账号
     * @return array
     */
    public function account()
    {
        $id = $this->request->param('id', 0);
        if (!$id) {
            $this->error("参数错误！");
        }
        $UsersModel = app()->make(UsersModel::class);
        $account = $UsersModel->where('store_id', $id)->where('type', 1)->find();
        $this->assign('data', $account);
        $this->assign('store_id', $id);

        return $this->fetch();
    }


}