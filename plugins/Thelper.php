<?php
    
/*
 * display info to CLI, Command Tools.
 * created by guoyexuan 
 * 2017-01-19 11:48:18
 * LastModify:YexuanGuo at WangJing!
 * LastModifyTime: 2017-07-20 11:00:49
 */

class Thelper{

        private $foreground_colors = array();

        private $background_colors = array();  


        protected $dbh;
        protected $display_lenght = 70;
        protected $notice_msg = array(
            'method_https_post_msg'    =>'当前请求为HTTPS...',
            'setting_cookies_msg'      =>"Cookie设置成功,Method is post...",
            'header_setting_msg'       =>'Header设置成功...'
        );
        public function __construct()
        {
            $this->foreground_colors['black'] = '0;30';
            $this->foreground_colors['dark_gray'] = '1;30';  
            $this->foreground_colors['blue'] = '0;34';  
            $this->foreground_colors['light_blue'] = '1;34';  
            $this->foreground_colors['green'] = '0;32';  
            $this->foreground_colors['light_green'] = '1;32';  
            $this->foreground_colors['cyan'] = '0;36';  
            $this->foreground_colors['light_cyan'] = '1;36';  
            $this->foreground_colors['red'] = '0;31';  
            $this->foreground_colors['light_red'] = '1;31';  
            $this->foreground_colors['purple'] = '0;35';  
            $this->foreground_colors['light_purple'] = '1;35';  
            $this->foreground_colors['brown'] = '0;33';  
            $this->foreground_colors['yellow'] = '1;33';  
            $this->foreground_colors['light_gray'] = '0;37';  
            $this->foreground_colors['white'] = '1;37';  
   
            $this->background_colors['black'] = '40';  
            $this->background_colors['red'] = '41';  
            $this->background_colors['green'] = '42';  
            $this->background_colors['yellow'] = '43';  
            $this->background_colors['blue'] = '44';  
            $this->background_colors['magenta'] = '45';  
            $this->background_colors['cyan'] = '46';  
            $this->background_colors['light_gray'] = '47';
        }  

        /*
         * CLI显示带颜色的dialog,！！！！！稍后把打印的东西要全部记录下log文件里边！！！！！！！！！！！！！！！！！！
         */
        public function showColoredString($string,$type='info',$background_color = null)
        {
            switch ($type)
            {
                case 'info':
                    $foreground_color = 'green';
                    break;
                case 'error':
                    $foreground_color = 'red';
                    break;
                case 'warning':
                    $foreground_color = 'yellow';
                    break;
                case null:
                    $foreground_color = 'green';
                    break;
            }

            $colored_string = "";  
   
            if (isset($this->foreground_colors[$foreground_color]))
            {
                $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";  
            }    
            if (isset($this->background_colors[$background_color]))
            {
                $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";  
            }  
   
            $colored_string .=  $string . "\033[0m";  
   
            echo sprintf($this->getLocalTime().' [%s] '.$colored_string.PHP_EOL,strtoupper($type));
        }  
   
        public function getForegroundColors()
        {
            return array_keys($this->foreground_colors);  
        }  

        public function getBackgroundColors()
        {
            return array_keys($this->background_colors);  
        }

        public function str_rand($length = null, $char = '0123456789abcdefghijklmnopqrstuvwxyz') {
            if(!is_int($length) || $length < 0)
            {
                return false;
            }

            $string = '';
            for($i = $length; $i > 0; $i--)
            {
                $string .= $char[mt_rand(0, strlen($char) - 1)];
            }
            return $string;
        }

        public function SendGet($url,$cookies=false,$headers=null,$time_out = 30)
        {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
            if($headers !== null)
            {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if($cookies)
            {
                $cookie_file = realpath(__dir__ . '/..') .'/logs/cookies.txt';
                $cookie_file = realpath($cookie_file);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            }

            $output = curl_exec($ch);

            curl_close($ch);
            if($output === false)
            {
                 return false;
            }
            else
            {
                 return $output;
            }
        }

        /**
         * @param $FullHttpUrl
         * @param $Req
         * @param bool $is_save_cookies
         * @param bool $set_cookies
         * @param bool $is_login_act
         * @param bool $isHttps
         * @param null $header
         * @return mixed
         */
        public function SendPost($FullHttpUrl,$Req,$is_save_cookies=false,$set_cookies=false,$isHttps=false,$header = null)
        {
            $ch = curl_init();

            $cookie_jar  = realpath(__dir__ . '/..') .'/logs/cookies.txt';

            curl_setopt($ch, CURLOPT_POST, 1);

            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($Req));

            if($header !== null)
            {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);  //post header
            }
            //cookies保存到文件
            if($is_save_cookies)
            {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
            }
            if($set_cookies)
            {
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
            }

            curl_setopt($ch, CURLOPT_URL, $FullHttpUrl);
            curl_setopt($ch, CURLOPT_HEADER, false); //表示需要response header
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            if ($isHttps === true)
            {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);
            }

            $result = curl_exec($ch);

            if($result === false)
            {
                echo 'Curl error: ' . curl_error($ch);
            }
            return $result;
        }

        /*
         * curl下载文件,成功return 1 失败return 0;
         */
        public function curl_download($url, $dir)
        {
            $ch = curl_init($url);
            $fp = fopen($dir, "wb");
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $res=curl_exec($ch);
            $curl_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($curl_code == 200)
            {
                curl_close($ch);
                fclose($fp);
                return true;
            }
            else
            {
                return false;
            }
        }

        public function getTime()
        {
            list($t1, $t2) = explode(' ', microtime());
            return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
        }

        public function writeLog($log,$logType,$logName = false)
        {
            file_put_contents('./run_log/'.date('Y-m-d').'_'.$logName.".log",date('H:i:s')." - [$logType] - ".$log.PHP_EOL,FILE_APPEND);
        }

        public function getLocalTime()
        {
            return date('Y-m-d H:i:s',time());
        }


        public function write_log($msg,$log_name)
        {
            error_log($msg,3,$log_name);
        }

        public function sendPostByPayload($url,$postData,$header = null)
        {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $postData
                )
            );

            if($header !== null)
            {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);  //post header
            }

            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,  false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,  false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);  //post header

            $response = curl_exec($curl);
            if($response === false)
            {
                echo 'Curl error: ' . curl_error($curl);
            }
            return $response;
        }

    public function sendPut($url,$postData,$header = null)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => $postData
            )
        );

        if($header !== null)
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);  //post header
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,  false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,  false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);  //post header

        $response = curl_exec($curl);
        if($response === false)
        {
            echo 'Curl error: ' . curl_error($curl);
        }
        return $response;
    }

    public function safeEcho($msg)
    {
        if (!function_exists('posix_isatty') || posix_isatty(STDOUT))
        {
            echo $msg;
        }
    }

    public function str_pad_fill($display_length,$str,$fill_str=false)
    {

        if($fill_str)
        {
            return str_pad($fill_str,$display_length + 2 - strlen($str));
        }
        else
        {
            return str_pad('',$display_length + 2 - strlen($str));

        }

    }
}
?>