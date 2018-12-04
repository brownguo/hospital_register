<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2018/11/20
 * Time: 2:03 PM
 */

date_default_timezone_set('PRC');

define('SERVER_BASE', realpath(__dir__ . '/..') . '/');

class hospital_register
{
    protected static $conf;
    protected static $mobileNo;
    protected static $password;
    protected static $yzm;
    protected static $isAjax;

    protected static $doctor = array();

    protected static $patientId;   //就诊人ID

    protected static $verify_code;

    protected static $iMessages;
    protected static $db_path;

    protected static $user_conf;
    protected static $hospital_conf;
    protected static $user_agent;
    protected static $helper;

    protected static $appoint_day; //预约周期

    protected static $display_lenght = 25;

    public function _init()
    {
        logger::notice('程序启动');
        //sqlite路径
        self::$db_path = getenv('HOME').'/Library/Messages/chat.db';
        //检查环境
        self::_checkEnv();
        //加载配置文件
        self::load_conf();
        //获取挂号信息
        self::get_duty_time();
        //开始登陆
        self::auth_login();
        //选择医生
        self::choose_doctor();
        //获取就诊人ID
        self::get_patient_id();
        //发送短信验证码
        self::get_sms_verify_code();
        //开始挂号
        self::do_register();
    }

    public static function _checkEnv()
    {
        logger::notice('检查Server环境');

        $pad_length = 26;

        $need_map = array(
            'sqlite3'    =>true,
            'pdo_sqlite' =>true,
            'posix'      =>true,
            'pcntl'      =>true,
            'curl'       =>true,
            'date'       =>true,
        );

        foreach ($need_map as $ext_name=>$must_required)
        {
            $suport = extension_loaded($ext_name);

            if($must_required && !$suport)
            {
                exit($ext_name. " \033[31;40m [NOT SUPORT BUT REQUIRED] \033[0m\n\n\033[31;40mYou have to compile CLI version of PHP with --enable-{$ext_name}\n\n");
            }
            echo str_pad($ext_name, $pad_length), "\033[32;40m [OK] \033[0m\n";
        }

        //检查sqlite文件是否可读权限
        if(posix_access(self::$db_path, POSIX_R_OK | POSIX_W_OK))
        {
            logger::notice(sprintf('[%s] is readable and writable',self::$db_path));
        }
        else
        {
            $error = posix_get_last_error();

            logger::notice('Error:'.posix_strerror($error),'error');

            exit(0);
        }

    }

    public static function load_conf()
    {
        self::$user_conf     = register_conf::user_conf();
        self::$hospital_conf = register_conf::hospital_conf();
        self::$user_agent    = register_conf::user_agent_conf();
        self::$mobileNo      = base64_encode(self::$user_conf['username']);
        self::$password      = base64_encode(self::$user_conf['password']);
        self::$yzm           = '';
        self::$isAjax        = 'true';

        if(!empty(self::$mobileNo) && !empty(self::$password))
        {
            logger::notice('配置文件加载成功');
        }
    }

    public static function get_duty_time()
    {
        $user_conf     = self::$user_conf;
        $hospital_conf = self::$hospital_conf;

        $url = sprintf($hospital_conf['appoint_url'],$user_conf['hospitalId'],$user_conf['departmentId']);

        $res = requests::get($url);

        if(!$res)
        {
            logger::info(sprintf('请求号源出错,res:%s',$res));
            logger::notice('请求号源出错,程序退出','error');
            exit(0);
        }

        //获取医院名称
        preg_match('/<p class="ksorder_box_top_p">(.*?)<\/p>/si',$res,$hospital_name_arr);

        preg_match('/<strong>(.*?)<\/strong>/i',$hospital_name_arr[1],$hospital_name);

        logger::notice($hospital_name[1]);

        //获取当前医院放号时间

        preg_match('/<span>更新时间：<\/span>每日(.*?)更新/i',$res,$refreshTime);

        logger::notice("放号时间,每天：{$refreshTime[1]}");

        //获取预约周期

        preg_match('/<span>预约周期：<\/span>(.*?)<script/i',$res,$appoint_day);

        self::$appoint_day = $appoint_day[1];

        logger::notice(sprintf("预约周期:[%s]天",self::$appoint_day));

    }

    public static function auth_login()
    {
        logger::notice('开始登陆');

        logger::notice('优先使用Cookies登录');

        if(self::is_login())
        {
            logger::notice('使用Cookies登录成功');
        }
        else
        {
            logger::notice('Cookies登录失败,开始使用用户名密码登录');

            $args = array(
                'mobileNo'=>self::$mobileNo,
                'password'=>self::$password,
                'yzm'     =>self::$yzm,
                'isAjax'  =>self::$isAjax
            );

            $url = self::$hospital_conf['login_url'];

            $res = requests::post($url,$args,true,false);

            logger::info(sprintf('cookies登陆失败,开始使用账号密码登录,Args:%s',$res));

            $res = json_decode($res,true);

            if($res['code'] == 200 && $res['msg'] == 'OK')
            {
                logger::notice('登陆成功');
            }
            else
            {
                logger::notice($res['msg'].' 程序退出');
                exit(0);
            }
        }
    }

    public static function is_login()
    {
        $args = array(
            'hospitalId'    =>self::$user_conf['hospitalId'],
            'departmentId'  =>self::$user_conf['departmentId'],
            'dutyCode'      =>self::$user_conf['dutyCode'],
            'dutyDate'      =>self::$user_conf['dutyDate'],
            'isAjax'        =>true,
        );

        $url = self::$hospital_conf['part_duty_url'];

        $res = requests::post($url,$args,false,true);

        $res = json_decode($res,true);

        if($res['code'] == 200 && $res['msg'] == 'OK')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function choose_doctor()
    {
        logger::notice('当前挂号日期：'.self::$user_conf['dutyDate'].self::$user_conf['dutyCodeMsg']);
        $appoint_day = self::$appoint_day;
        logger::notice('当前最晚可挂：'.date("Y-m-d",strtotime("+{$appoint_day} day")));

        $args = array(
            'hospitalId'    =>self::$user_conf['hospitalId'],
            'departmentId'  =>self::$user_conf['departmentId'],
            'dutyCode'      =>self::$user_conf['dutyCode'],
            'dutyDate'      =>self::$user_conf['dutyDate'],
            'isAjax'        =>true,
        );

        $url = self::$hospital_conf['part_duty_url'];

        $res = requests::post($url,$args,false,true);

        $res = json_decode($res,true);

        if($res['code'] == 200 && $res['msg'] == 'OK')
        {
            logger::notice('正在获取医生列表');
            logger::safeEcho("-----------------------医生列表---------------------------------------------\n");
            logger::safeEcho("hospital_register version:1.0          PHP version:". PHP_VERSION. "\n");
            logger::safeEcho("----------------------------------------------------------------------------\n");
            logger::safeEcho(
                "ID". str_pad('',self::$display_lenght + 2 - strlen('ID')).
                "医生ID". str_pad('',self::$display_lenght + 2 - strlen('医生ID')).
                "挂号ID". str_pad('',self::$display_lenght + 2 - strlen('医生ID')).
                "医生姓名". str_pad('',self::$display_lenght + 2 - strlen('医生姓名')).
                "医生级别". str_pad('', self::$display_lenght + 2 - strlen('医生级别')).
                "擅长技能". str_pad('', self::$display_lenght + 2 - strlen('擅长技能')).
                "号余量". str_pad('',self::$display_lenght + 2 - strlen('号余量'))."\n");

            //医生和挂号信息
            self::$doctor     = $res['data'];

            foreach ($res['data'] as $k=>$v)
            {
                echo $k.logger::str_pad_fill(25,$k).
                     $v['doctorId'].logger::str_pad_fill(25,$v['doctorId']).
                     $v['dutySourceId'].logger::str_pad_fill(25,$v['dutySourceId']).
                     $v['doctorName'].logger::str_pad_fill(25,$v['doctorName']).
                     $v['doctorTitleName'].logger::str_pad_fill(25,$v['doctorTitleName']).
                     $v['skill'].logger::str_pad_fill(25,$v['skill']).
                     logger::str_pad_fill(5,$v['remainAvailableNumber']).$v['remainAvailableNumber'].
                     PHP_EOL;
            }

            logger::notice('医生列表获取成功,请选择医生ID：');

            $choose_doctor_id = trim(fgets(STDIN));

            if(!is_numeric($choose_doctor_id) || $choose_doctor_id > count(self::$doctor))
            {
                logger::notice('指令输入错误请从新输入:');
                $choose_doctor_id = trim(fgets(STDIN));
            }

            self::$doctor     = @$res['data'][$choose_doctor_id];

        }
        else
        {
            logger::notice("获取医生列表失败,{$res['msg']},code:{$res['code']}");
            exit(0);
        }
    }

    public static function get_patient_id()
    {
        logger::notice('开始获取就诊人ID');

        $url = sprintf(self::$hospital_conf['patient_form_url'],self::$user_conf['hospitalId'],self::$user_conf['departmentId'],
            self::$doctor['doctorId'],self::$doctor['dutySourceId']
            );

        $res = requests::get($url,null,false,true);

        preg_match('/<input type="radio" name="hzr" value="(.*?)"/si',$res,$patientId);

        if(!empty($patientId[1]))
        {
            self::$patientId = $patientId[1];

            logger::notice('就诊人ID获取成功,当前就诊人ID为：'.$patientId[1]);
        }
        else
        {
            logger::notice('就诊人ID获取失败');
            exit(0);
        }
    }

    public static function get_sms_verify_code()
    {
        logger::notice('开始获取获取短信验证码');

        $args = array();
        $url = self::$hospital_conf['send_order_url'];

        $res = requests::post($url,$args,false,true);
        $res = json_decode($res,true);

        if($res['code'] == 200)
        {
            //TUDO
            logger::notice('短信验证码获取成功');
            logger::notice('正在等待填充验证码');

            $iMessages = new iMessage();

            self::$verify_code = $iMessages->_return_verify_code();

            if(self::$verify_code)
            {
                logger::notice('iMessage读取成功');
                logger::notice('验证码自动填充成功:'.self::$verify_code);
            }
            else
            {
                logger::notice('iMessage读取失败');
            }
        }
        else
        {
            logger::notice($res['msg']);
            exit(0);
        }
    }

    public static function do_register()
    {
        $args = array(
            'dutySourceId'      =>self::$doctor['dutySourceId'],
            'hospitalId'        =>self::$user_conf['hospitalId'],
            'departmentId'      =>self::$user_conf['departmentId'],
            'doctorId'          =>self::$doctor['doctorId'],
            'patientId'         =>self::$patientId,
            'hospitalCardId'    =>'',
            'medicareCardId'    =>self::$user_conf['medicareCardId'],
            'reimbursementType' =>10,   //报销类型
            'smsVerifyCode'     =>self::$verify_code,
            'childrenBirthday'  =>'',
            'isAjax'            =>true,
        );

        $url = self::$hospital_conf['confirm_url'];

        $res = requests::post($url,$args,false,true);

        $res = json_decode($res,true);

        if($res['code'] == 1 && !empty($res['orderId']))
        {
            logger::info('挂号成功,info:'.print_r($res,true));
            logger::notice('挂号成功');
        }
        else
        {
            logger::notice('挂号失败,'.$res['msg']);
            exit(0);
        }
    }
}
$LoadableModules = array('config','plugins');

spl_autoload_register(function($name)
{
    global $LoadableModules;

    foreach ($LoadableModules as $module)
    {
        $filename =  SERVER_BASE.$module.'/'.$name . '.php';
        if (file_exists($filename))
            require_once $filename;
    }
});hospital_register::_init();