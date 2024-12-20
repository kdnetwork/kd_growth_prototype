<?php

if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}
/**
 * 安装插件时会被调用
 */
function callback_install()
{
    //在这里做点事
    
    global $m;
    $m->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kd_growth` (
            `id` BIGINT NOT NULL AUTO_INCREMENT ,
            `uid` BIGINT NOT NULL ,
            `pid` BIGINT NOT NULL ,
            `status` TEXT NULL DEFAULT NULL ,
            `log` TEXT NULL DEFAULT NULL ,
            `date` INT NOT NULL DEFAULT '0' ,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
	");
}

/**
 * 激活插件时会被调用
 */
function callback_init()
{
    option::set('kd_growth_offset', 0);
    cron::set('kd_growth_do', 'plugins/kd_growth/cron/dopost.php', 0, 0, 0);
}

/**
 * 禁用插件时会被调用
 */
function callback_inactive()
{
    option::del('kd_growth_offset');
    cron::del('kd_growth_do');
}

/**
 * 卸载插件时会被调用
 * 卸载插件前，如果插件是激活的，会自动禁用并调用 callback_inactive()
 */
function callback_remove()
{
    //在这里做点事
    global $m;
    $m->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kd_growth`");
    // 删掉用户配置
    $m->query("DELETE FROM `" . DB_PREFIX . "users_options` WHERE `name` = 'kd_growth_sign_only'");
}

/**
 * 升级插件时会被调用
 * 系统会传入当前数据库的版本号、当前插件文件中说明的版本号
 * 必须有返回值，如果返回新的版本号，新版本号由系统记录到数据库；如果返回false，将终止操作且不记录到数据库
 */
function callback_update($ver1, $ver2)
{
    //ver1 是当前数据库的版本号
    //ver2 是当前插件文件中说明的版本号，即 插件名_desc.php 的 ['plugin']['version'] 的值
    //在这里做点事
    return false;
}

/**
 * 插件自定义保存设置函数
 * 插件调用方法：setting.php?mod=setplugin:插件名称
 * 然后系统会调用 插件名_callback.php 的 callback_setting()
 */
function callback_setting()
{
    //在这里做点事
    //dump($_rank); //看看前端给我rank了什么东西
}
