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
namespace TencentCloud\Faceid\V20180301\Models;
use TencentCloud\Common\AbstractModel;

/**
 * PhoneVerification返回参数结构体
 *
 * @method string getResult() 获取认证结果码:
0: 认证通过
-1: 手机号已实名，但是身份证和姓名均与实名信息不一致 
-2: 手机号已实名，手机号和证件号一致，姓名不一致
-3: 手机号已实名，手机号和姓名一致，身份证不一致
-4: 信息不一致
-5: 手机号未实名
-6: 手机号码不合法
-7: 身份证号码有误
-8: 姓名校验不通过
-9: 没有记录
-10: 认证未通过
-11: 验证中心服务繁忙
 * @method void setResult(string $Result) 设置认证结果码:
0: 认证通过
-1: 手机号已实名，但是身份证和姓名均与实名信息不一致 
-2: 手机号已实名，手机号和证件号一致，姓名不一致
-3: 手机号已实名，手机号和姓名一致，身份证不一致
-4: 信息不一致
-5: 手机号未实名
-6: 手机号码不合法
-7: 身份证号码有误
-8: 姓名校验不通过
-9: 没有记录
-10: 认证未通过
-11: 验证中心服务繁忙
 * @method string getDescription() 获取业务结果描述。
 * @method void setDescription(string $Description) 设置业务结果描述。
 * @method string getRequestId() 获取唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
 * @method void setRequestId(string $RequestId) 设置唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
 */
class PhoneVerificationResponse extends AbstractModel
{
    /**
     * @var string 认证结果码:
0: 认证通过
-1: 手机号已实名，但是身份证和姓名均与实名信息不一致 
-2: 手机号已实名，手机号和证件号一致，姓名不一致
-3: 手机号已实名，手机号和姓名一致，身份证不一致
-4: 信息不一致
-5: 手机号未实名
-6: 手机号码不合法
-7: 身份证号码有误
-8: 姓名校验不通过
-9: 没有记录
-10: 认证未通过
-11: 验证中心服务繁忙
     */
    public $Result;

    /**
     * @var string 业务结果描述。
     */
    public $Description;

    /**
     * @var string 唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
     */
    public $RequestId;

    /**
     * @param string $Result 认证结果码:
0: 认证通过
-1: 手机号已实名，但是身份证和姓名均与实名信息不一致 
-2: 手机号已实名，手机号和证件号一致，姓名不一致
-3: 手机号已实名，手机号和姓名一致，身份证不一致
-4: 信息不一致
-5: 手机号未实名
-6: 手机号码不合法
-7: 身份证号码有误
-8: 姓名校验不通过
-9: 没有记录
-10: 认证未通过
-11: 验证中心服务繁忙
     * @param string $Description 业务结果描述。
     * @param string $RequestId 唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
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
        if (array_key_exists("Result",$param) and $param["Result"] !== null) {
            $this->Result = $param["Result"];
        }

        if (array_key_exists("Description",$param) and $param["Description"] !== null) {
            $this->Description = $param["Description"];
        }

        if (array_key_exists("RequestId",$param) and $param["RequestId"] !== null) {
            $this->RequestId = $param["RequestId"];
        }
    }
}
