<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2018/12/5
 * Time: 5:51 PM
 */

define('SERVER_BASE', realpath(__dir__ . '/..') . '/');

class test
{
    protected static $loop_doctor = true;
    protected static $loop_init = true;

    public static function _init()
    {
        self::_auto_num();
    }

    public static function _auto_num()
    {
        echo date('Y-m-d H:i:s',time()).PHP_EOL;

        if(self::$loop_doctor && self::$loop_init)
        {
            self::$loop_init = false;

            Timer::_init();
            Timer::add(1,array('test','_auto_num'),array(1),true);

            while(true)
            {
                pcntl_signal_dispatch();
            }
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
});test::_init();