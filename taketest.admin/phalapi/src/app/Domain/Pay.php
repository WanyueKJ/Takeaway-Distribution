<?php
namespace App\Domain;

use App\Domain\Wxpay as Domain_Wxpay;
use App\Domain\Alipay as Domain_Alipay;

/* 三方支付 */
class Pay {

    public function pay($orderno,$money,$payid,$title,$backmodel,$openid='',$is_mer = 0){

        $rs = ['code' => 0, 'msg' => '', 'info' => [] ];

        $ali=[
            'orderinfo'=>'',
        ];
        $wx=[
            'appid'=>'',
            'noncestr'=>'',
            'package'=>'',
            'partnerid'=>'',
            'prepayid'=>'',
            'timestamp'=>'',
        ];

        $ios=[
            'notifyurl'=>'',
        ];

        $small='';
        $h5='';
        $mp='';
        $Domain_Wxpay=new Domain_Wxpay();

        if($payid ==1){
            /* 支付宝 */
            $Domain_Alipay=new Domain_Alipay();

            $url=\App\get_upload_path("/appapi/{$backmodel}/notify_ali");
            $res=$Domain_Alipay->apppay($orderno,$money,$url,$title);
            if($res['code']!=0){
                return $res;
            }
            $ali=$res['info'];

        }
        if($payid ==2){
            /* 微信app */
            $url=\App\get_upload_path("/appapi/{$backmodel}/notify_wx");
            $res=$Domain_Wxpay->wxPay($orderno,$money,$url,$title,$is_mer);
            if($res['code']!=0){
                return $res;
            }
            $wx=$res['info'];
        }

        if($payid == 3) {
            //UNIAPP端小程序支付
            $url=\App\get_upload_path("/appapi/{$backmodel}/notify_small");
            $res=$Domain_Wxpay->smallPay($orderno,$money,$url,$title, $openid,$is_mer);
            if($res['code']!=0){
                return $res;
            }
            $small=$res['info'];

        }
        if($payid == 4) {
            //UNIAPP端 H5支付
            $url=\App\get_upload_path("/appapi/{$backmodel}/notify_hfive");
            $res=$Domain_Wxpay->hfivePay($orderno,$money,$url,$title, $openid);
            if($res['code']!=0){
                return $res;
            }
            $h5=$res['info'];
        }

        if($payid == 5){
            /* 微信内支付 */
            $url=\App\get_upload_path("/appapi/{$backmodel}/notify_mp");
            $res=$Domain_Wxpay->mpPay($orderno,$money,$url,$title, $openid);
            if($res['code']!=0){
                return $res;
            }
            $mp=$res['info'];
        }

        if($payid==6){
            /* 苹果支付 */
            $ios=[
                'notifyurl'=>\App\get_upload_path("/appapi/{$backmodel}/notify_ios"),
            ];
        }

        $rs['info'][0]['orderid']=$orderno;
        $rs['info'][0]['money']=(string)$money;
        $rs['info'][0]['ali']=$ali;
        $rs['info'][0]['wx']=$wx;
        $rs['info'][0]['ios']=$ios;
        $rs['info'][0]['small']=$small;
        $rs['info'][0]['h5']=$h5;
        $rs['info'][0]['mp']=$mp;

        return $rs;
    }

}
