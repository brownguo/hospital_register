#北京市预约挂号统一平台脚本(PHP)

![](https://img.shields.io/badge/hospital_register-v1.0.0-519dd9.svg)
![](https://img.shields.io/badge/Language-php-blue.svg)
![](https://img.shields.io/travis/php-v/symfony/symfony.svg)
![](https://img.shields.io/travis/rust-lang/rust.svg)
![](https://img.shields.io/badge/platform-OSX-red.svg)
![](https://img.shields.io/github/size/webcaetano/craft/build/phaser-craft.min.js.svg)


- 本程序用于北京市预约挂号统一平台挂号,目前只支持北京地区医院挂号。


## 运行环境

- PHP >= 7.1
- OSX
- Sqlite3

##使用方法

- php hospital_register.php

## 配置文件

```php
      $userConf = array(
            'username'      =>'114用户名',
            'password'      =>'11密码',
            'dutyDate'      =>'挂号日期',    //挂号日期,格式为：2018-11-30
            'hospitalId'    =>'xxx',        //医院ID
            'departmentId'  =>'xxx',        //科室ID
            'dutyCode'      =>'1',          //1、上午.2、下午
            'medicareCardId'=>'xxx',        //社保卡号
        );
```

## 更新日志

- 2018年11月24日 V1.0.0

