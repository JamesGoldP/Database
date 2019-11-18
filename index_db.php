<?php
/**
 * User: PengYilong
 * Date: 2018/10/27
 * Time: 3:31 AM
 */
include './Loader.php';
spl_autoload_register('Loader::_autoload');
require 'vendor/autoload.php';
use Nezimi\Db;

$config = require_once './configs/database.php';
Db::setConfig($config);

// //D
// Db::table('cms_account')->where(['name'=>'jimmy2'])->delete();

// //C
// $insert_array = [
//     'name'=>'jimmy',
//     'money'=>1000,
// ];
// Db::table('cms_account')->insert($insert_array);

// $update_array = array(
//     'name' => 'jimmy2',
//     'money' => 2000,
// );
// $where = "name='jimmy'";
// Db::table('cms_account')->where($where)->update($update_array);

// $result = Db::table('cms_account')->fields(['name','money'])->limit(99)->order('id desc')->group('name')->having('name=\'jimmy2\'')->select();
// p($result);

// $result = Db::table('cms_account')->fetchSql()->group('name')->count();
// $result = Db::table('zz_good')->alias('good')->join('zz_good_pic as good_pic', 'good.id = good_pic.good_id')->join('zz_good_spec as good_spec', 'good.id = good_spec.good_id')->select();
// p($result);

// $result = Db::table('cms_news')->find();
$result = Db::query('select * from cms_news');
p($result);