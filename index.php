<?php
include './Loader.php';
spl_autoload_register('Loader::_autoload');
require 'vendor/autoload.php';
use Nezimi\Model;
use Nezimi\Db;

class admin extends Model{

    protected $table = 'cms_news';

    public function test()
    {
        p('run here!');
    }

}
$config = require_once './config.php';

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
// $result = $model->where($where)->fetchSql()->update($update_array);
// p($result);

// $result = $model->field(['name','money'])->limit(99)->order('id desc')->group('name')->having('name=\'jimmy2\'')->select();


// $result = $model->where('id>7')->find();
// $result = $model->where('id', '>', 7)->find();
// $result = $model->where('title', 'like', '%what%')->find();
// $result = $model->where(function($query){
//     $query->where('id', '>', 1);
// })->find();
// $result = $model->where([
//     'title'	=>	'what is html2',
//     'id'=>	172,
// ])->find();
// $result = $model->where('id', 'between', '6,7')->fetchSql(false)->find();
// $result = $model->where('thumb&title', 'like', '%what%')->find();
// $result = $model->where('id', '<', 7)->whereOr('id', '>', 1)->find();

$result = $model->where('id', ['>', 1], ['<', 7], 'and')->find();
p($result->getLastSql());
p($result);
