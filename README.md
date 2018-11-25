# 北京市预约挂号统一平台脚本(PHP)

![](https://img.shields.io/badge/hospital_register-v1.0.0-519dd9.svg)
![](https://img.shields.io/badge/Language-php-blue.svg)
![](https://img.shields.io/travis/php-v/symfony/symfony.svg)
![](https://img.shields.io/travis/rust-lang/rust.svg)
![](https://img.shields.io/badge/platform-OSX-red.svg)

[![GitHub repo size in bytes](https://img.shields.io/github/repo-size/badges/shields.svg)](https://github.com/brownguo/hospital_register)

- 本程序用于北京市预约挂号统一平台挂号,目前只支持北京地区医院挂号。

- 就医挂号是刚需。北京挂号不亚于春运回家抢票，每次放号瞬间被秒杀一空，拼手速很难挂到号，因此有了此脚本。

## 运行截图

![Image text](https://github.com/brownguo/hospital_register/blob/master/img/desc2.png)

## 运行环境

- PHP >= 7.1
- OSX
- Sqlite3

## 使用方法

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

## 注意

- ！！！脚本目前只支持在Mac使用，并且手机一定要是iPhone，其他手机读取不到验证码！！！

- 验证码读取原理：手机接收到验证码之后需要把短信转发到Mac上，然后脚本自动读取iMessage消息，最后匹配114验证码。

## 更新日志

- 2018年11月24日 V1.0.0

