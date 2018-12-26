<?php
include './Loader.php';
spl_autoload_register('Loader::_autoload');
require 'vendor/autoload.php';
use Nezumi\Model;
use Nezumi\Db;

class admin extends Model{

    protected $table = 'cms_account';

    public function __constuct()
    {
        // $this->table = 'cms_account';
    }

    public function test()
    {
        p('run here!');
    }

}
$config = require_once './configs/database.php';
Db::setConfig($config);
$mysql = new admin();

// D
$result = $mysql->where(['name'=>'jimmy2'])->delete();

// //C
// $insert_array = array(
//     'name'=>'jimmy',
//     'money'=>1000,
// );
// $result = $mysql->insert($insert_array, 'cms_account');

// $update_array = array(
//     'name' => 'jimmy2',
//     'money' => 2000,
// );
// $where = "name='jimmy'";
// $result = $mysql->where($where)->update($update_array);

// $result = $mysql->fields(['name','money'])->limit(99)->order('id desc')->group('name')->having('name=\'jimmy2\'')->select();
// print_r($result);
// $mysql->close();

