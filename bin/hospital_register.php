<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2018/11/20
 * Time: 2:03 PM
 */

date_default_timezone_set('PRC');

define('SERVER_BASE', realpath(__dir__ . '/..') . '/');

if(is_file(SERVER_BASE.'config/config.php'))
{
    include_once SERVER_BASE.'config/config.php';
}

class hospital_register
{
    protected $conf;
    protected $mobileNo;
    protected $password;
    protected $yzm;
    protected $isAjax;

    protected $doctor = array();

    protected $patientId;   //就诊人ID

    protected $verify_code;

    protected $iMessages;
    protected $db_path;

    protected $user_conf;
    protected $hospital_conf;
    protected $user_agent;
    protected $helper;

    protected $appoint_day; //预约周期

    protected $display_lenght = 25;

    public function __construct()
    {
        $this->helper = new Thelper();
        $this->db_path  = getenv('HOME').'/Library/Messages/chat.db';
    }

    public function _init()
    {
        $this->helper->showColoredString('程序启动');
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

    public function _checkEnv()
    {
        $this->helper->showColoredString('检查Server环境');

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
        if(posix_access($this->db_path, POSIX_R_OK | POSIX_W_OK))
        {
            $this->helper->showColoredString(sprintf('[%s] is readable and writable',$this->db_path));
        }
        else
        {
            $error = posix_get_last_error();

            $this->helper->showColoredString('Error:'.posix_strerror($error),'red');

            exit(0);
        }

    }

    public function load_conf()
    {
        $this->user_conf     = register_conf::user_conf();
        $this->hospital_conf = register_conf::hospital_conf();
        $this->user_agent    = register_conf::user_agent_conf();
        $this->mobileNo      = base64_encode($this->user_conf['username']);
        $this->password      = base64_encode($this->user_conf['password']);
        $this->yzm           = '';
        $this->isAjax        = 'true';

        if(!empty($this->mobileNo) && !empty($this->password))
        {
            $this->helper->showColoredString('配置文件加载成功');
        }
    }

    public function get_duty_time()
    {
        $user_conf     = $this->user_conf;
        $hospital_conf = $this->hospital_conf;

        $url = sprintf($hospital_conf['appoint_url'],$user_conf['hospitalId'],$user_conf['departmentId']);

        $res = $this->helper->SendGet($url);

        if(!$res)
        {
            $this->helper->showColoredString('请求号源出错,程序退出','error');
            exit(0);
        }

        //获取医院名称
        preg_match('/<p class="ksorder_box_top_p">(.*?)<\/p>/si',$res,$hospital_name_arr);

        preg_match('/<strong>(.*?)<\/strong>/i',$hospital_name_arr[1],$hospital_name);

        $this->helper->showColoredString($hospital_name[1]);

        //获取当前医院放号时间

        preg_match('/<span>更新时间：<\/span>每日(.*?)更新/i',$res,$refreshTime);

        $this->helper->showColoredString("放号时间,每天：{$refreshTime[1]}");

        //获取预约周期

        preg_match('/<span>预约周期：<\/span>(.*?)<script/i',$res,$appoint_day);

        $this->appoint_day = $appoint_day[1];

        $this->helper->showColoredString("预约周期：{$this->appoint_day}天");

    }

    public function auth_login()
    {
        $this->helper->showColoredString('开始登陆');

        $this->helper->showColoredString('优先使用Cookies登录');

        if(self::is_login())
        {
            $this->helper->showColoredString('使用Cookies登录成功');
        }
        else
        {
            $this->helper->showColoredString('Cookies登录失败,开始使用用户名密码登录');

            $args = array(
                'mobileNo'=>$this->mobileNo,
                'password'=>$this->password,
                'yzm'     =>$this->yzm,
                'isAjax'  =>$this->isAjax
            );

            $url = $this->hospital_conf['login_url'];

            $res = $this->helper->sendPost($url,$args,true);

            $res = json_decode($res,true);

            if($res['code'] == 200 && $res['msg'] == 'OK')
            {
                $this->helper->showColoredString('登陆成功');
            }
            else
            {
                $this->helper->showColoredString($res['msg'].' 程序退出');
                exit(0);
            }
        }
    }

    public function is_login()
    {
        $args = array(
            'hospitalId'    =>$this->user_conf['hospitalId'],
            'departmentId'  =>$this->user_conf['departmentId'],
            'dutyCode'      =>$this->user_conf['dutyCode'],
            'dutyDate'      =>$this->user_conf['dutyDate'],
            'isAjax'        =>true,
        );

        $url = $this->hospital_conf['part_duty_url'];

        $res = $this->helper->sendPost($url,$args,false,true);

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

    public function choose_doctor()
    {
        $this->helper->showColoredString('当前挂号日期：'.$this->user_conf['dutyDate'].$this->user_conf['dutyCodeMsg']);
        $this->helper->showColoredString('当前最晚可挂：'.date("Y-m-d",strtotime("+{$this->appoint_day} day")));

        $args = array(
            'hospitalId'    =>$this->user_conf['hospitalId'],
            'departmentId'  =>$this->user_conf['departmentId'],
            'dutyCode'      =>$this->user_conf['dutyCode'],
            'dutyDate'      =>$this->user_conf['dutyDate'],
            'isAjax'        =>true,
        );

        $url = $this->hospital_conf['part_duty_url'];

        $res = $this->helper->sendPost($url,$args,false,true);

        $res = json_decode($res,true);

        if($res['code'] == 200 && $res['msg'] == 'OK')
        {
            $this->helper->showColoredString('正在获取医生列表');
            $this->helper->safeEcho("-----------------------医生列表---------------------------------------------\n");
            $this->helper->safeEcho("hospital_register version:1.0          PHP version:". PHP_VERSION. "\n");
            $this->helper->safeEcho("----------------------------------------------------------------------------\n");
            $this->helper->safeEcho(
                "ID". str_pad('',$this->display_lenght + 2 - strlen('ID')).
                "医生ID". str_pad('',$this->display_lenght + 2 - strlen('医生ID')).
                "挂号ID". str_pad('',$this->display_lenght + 2 - strlen('医生ID')).
                "医生姓名". str_pad('',$this->display_lenght + 2 - strlen('医生姓名')).
                "医生级别". str_pad('', $this->display_lenght + 2 - strlen('医生级别')).
                "擅长技能". str_pad('', $this->display_lenght + 2 - strlen('擅长技能')).
                "号余量". str_pad('',$this->display_lenght + 2 - strlen('号余量'))."\n");

            //医生和挂号信息
            $this->doctor     = $res['data'];

            foreach ($res['data'] as $k=>$v)
            {
                echo $k.$this->helper->str_pad_fill(25,$k).
                     $v['doctorId'].$this->helper->str_pad_fill(25,$v['doctorId']).
                     $v['dutySourceId'].$this->helper->str_pad_fill(25,$v['dutySourceId']).
                     $v['doctorName'].$this->helper->str_pad_fill(25,$v['doctorName']).
                     $v['doctorTitleName'].$this->helper->str_pad_fill(25,$v['doctorTitleName']).
                     $v['skill'].$this->helper->str_pad_fill(25,$v['skill']).
                     $this->helper->str_pad_fill(5,$v['remainAvailableNumber']).$v['remainAvailableNumber'].
                     PHP_EOL;
            }

            $this->helper->showColoredString('医生列表获取成功,请选择医生ID：');

            $choose_doctor_id = trim(fgets(STDIN));

            if(!is_numeric($choose_doctor_id) || $choose_doctor_id > count($this->doctor))
            {
                $this->helper->showColoredString('指令输入错误请从新输入:');
                $choose_doctor_id = trim(fgets(STDIN));
            }

            $this->doctor     = @$res['data'][$choose_doctor_id];

        }
        else
        {
            $this->helper->showColoredString("获取医生列表失败,{$res['msg']},code:{$res['code']}");
            exit(0);
        }
    }

    public function get_patient_id()
    {
        $this->helper->showColoredString('开始获取就诊人ID');

        $url = sprintf($this->hospital_conf['patient_form_url'],$this->user_conf['hospitalId'],$this->user_conf['departmentId'],
            $this->doctor['doctorId'],$this->doctor['dutySourceId']
            );

        $res = $this->helper->SendGet($url,true);

        preg_match('/<input type="radio" name="hzr" value="(.*?)"/si',$res,$patientId);

        if(!empty($patientId[1]))
        {
            $this->patientId = $patientId[1];

            $this->helper->showColoredString('就诊人ID获取成功,当前就诊人ID为：'.$patientId[1]);
        }
        else
        {
            $this->helper->showColoredString('就诊人ID获取失败');
            exit(0);
        }
    }

    public function get_sms_verify_code()
    {
        $this->helper->showColoredString('开始获取获取短信验证码');

        $args = array();
        $url = $this->hospital_conf['send_order_url'];

        $res = $this->helper->SendPost($url,$args,false,true);
        $res = json_decode($res,true);

        if($res['code'] == 200)
        {
            //TUDO
            $this->helper->showColoredString('短信验证码获取成功');
            $this->helper->showColoredString('正在等待填充验证码');

            $iMessages = new iMessage();

            $this->verify_code = $iMessages->_return_verify_code();

            if($this->verify_code)
            {
                $this->helper->showColoredString('iMessage读取成功');
                $this->helper->showColoredString('验证码自动填充成功:'.$this->verify_code);
            }
            else
            {
                $this->helper->showColoredString('iMessage读取失败');
            }
        }
        else
        {
            $this->helper->showColoredString($res['msg']);
            exit(0);
        }
    }

    public function do_register()
    {
        $args = array(
            'dutySourceId'      =>$this->doctor['dutySourceId'],
            'hospitalId'        =>$this->user_conf['hospitalId'],
            'departmentId'      =>$this->user_conf['departmentId'],
            'doctorId'          =>$this->doctor['doctorId'],
            'patientId'         =>$this->patientId,
            'hospitalCardId'    =>'',
            'medicareCardId'    =>$this->user_conf['medicareCardId'],
            'reimbursementType' =>10,   //报销类型
            'smsVerifyCode'     =>$this->verify_code,
            'childrenBirthday'  =>'',
            'isAjax'            =>true,
        );

        $url = $this->hospital_conf['confirm_url'];

        $res = $this->helper->SendPost($url,$args,false,true);

        $res = json_decode($res,true);

        if($res['code'] == 1 && !empty($res['orderId']))
        {
            $this->helper->showColoredString('挂号成功');
        }
        else
        {
            $this->helper->showColoredString('挂号失败,'.$res['msg']);
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
});
$start = new hospital_register();
$start->_init();