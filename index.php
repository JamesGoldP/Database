<?php
include './Loader.php';
spl_autoload_register('Loader::_autoload');
require 'vendor/autoload.php';
use Nezimi\Model;
use Nezimi\Db;

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
$model = new admin();

// D
// $result = $model->where(['name'=>'jimmy2'])->delete();

//C
// $insert_array = array(
//     'name'=>'jimmy',
//     'money'=>1000,
// );
// $result = $model->insert($insert_array);

// $update_array = array(
//     'name' => 'jimmy2',
//     'money' => 2000,
// );
// $where = "name='jimmy'";
// $result = $model->where($where)->update($update_array);

$result = $model->field(['name','money'])->limit(99)->order('id desc')->group('name')->having('name=\'jimmy2\'')->select();
p($result);
