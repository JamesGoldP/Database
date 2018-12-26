<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/10/27
 * Time: 3:31 AM
 */
include './Loader.php';
spl_autoload_register('Loader::_autoload');
require 'vendor/autoload.php';
use Nezumi\Db;

$config = require_once './configs/database.php';
Db::setConfig($config);
Db::table('cms_account')->where(['name'=>'jimmy2'])->delete();
