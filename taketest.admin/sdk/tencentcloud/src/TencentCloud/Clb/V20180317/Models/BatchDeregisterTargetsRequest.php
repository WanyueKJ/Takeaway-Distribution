<?php
/*
 * Copyright (c) 2017-2018 THL A29 Limited, a Tencent company. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace TencentCloud\Clb\V20180317\Models;
use TencentCloud\Common\AbstractModel;

/**
 * BatchDeregisterTargets请求参数结构体
 *
 * @method string getLoadBalancerId() 获取负载均衡ID
 * @method void setLoadBalancerId(string $LoadBalancerId) 设置负载均衡ID
 * @method array getTargets() 获取解绑目标
 * @method void setTargets(array $Targets) 设置解绑目标
 */
class BatchDeregisterTargetsRequest extends AbstractModel
{
    /**
     * @var string 负载均衡ID
     */
    public $LoadBalancerId;

    /**
     * @var array 解绑目标
     */
    public $Targets;

    /**
     * @param string $LoadBalancerId 负载均衡ID
     * @param array $Targets 解绑目标
     */
    function __construct()
    {

    }

    /**
     * For internal only. DO NOT USE IT.
     */
    public function deserialize($param)
    {
        if ($param === null) {
            return;
        }
        if (array_key_exists("LoadBalancerId",$param) and $param["LoadBalancerId"] !== null) {
            $this->LoadBalancerId = $param["LoadBalancerId"];
        }

        if (array_key_exists("Targets",$param) and $param["Targets"] !== null) {
            $this->Targets = [];
            foreach ($param["Targets"] as $key => $value){
                $obj = new BatchTarget();
                $obj->deserialize($value);
                array_push($this->Targets, $obj);
            }
        }
    }
}
