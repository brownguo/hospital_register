<?php

class register_conf
{
    static public function user_conf()
    {
        //肾内科,200003979,妇科内分泌门诊,200046684,内分泌王海宁科室200039566,普通内分泌门诊200039564
        $userConf = array(
            'username'      =>'xxxxxxxxx',    //114用户名
            'password'      =>'xxxxxxxxx',    //114密码
            'dutyDate'      =>'2018-11-30',   //挂号日期
            'hospitalId'    =>'142',          //医院ID
            'departmentId'  =>'200039600',    //科室ID
            'dutyCode'      =>'1',            //上午/下午
            'medicareCardId'=>'xxxxxxxxx',    //社保卡号
            'autoChoose'    =>true,           //系统自动选择医生
        );

        if($userConf['dutyCode'] == 1)
        {
            $userConf['dutyCodeMsg'] = '上午';
        }
        else
        {
            $userConf['dutyCodeMsg'] = '下午';
        }

        return $userConf;
    }

    static public function hospital_conf()
    {
        $domin = 'http://www.bjguahao.gov.cn';

        return array(
            'domain'        => $domin,
            'login_url'     => $domin.'/quicklogin.htm',    //登录
            'part_duty_url' => $domin.'/dpt/partduty.htm',  //获取号源信息
            'send_order_url'=> $domin.'/v/sendorder.htm',   //发送短信验证码
            'confirm_url'   => $domin.'/order/confirmV1.htm',//挂号
            'appoint_url'   => $domin.'/dpt/appoint/%s-%s.htm', //预约信息页
            'patient_form_url' => $domin.'/order/confirm/%s-%s-%s-%s.htm',//就诊人预约页
        );
    }

    static public function user_agent_conf()
    {
        return array(
            'Accept:application/json, text/javascript, */*; q=0.01',
            'Accept-Language: zh-CN,zh;q=0.9',
            'Upgrade-Insecure-Requests:1',
            'X-Requested-With: XMLHttpRequest',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36'
        );
    }
}

?>