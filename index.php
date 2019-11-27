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


// $result = $model->field(['name','money'])->limit(99)->order('id desc')->group('name')->having('name=\'jimmy2\'')->select();

/**
 * R
 * 
 */
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
// $result = $model->where('id', ['>', 1], ['<', 7], 'and')->find();


/**
 * U
 */
// $update_array = array(
//     'title' => 'linux2',
//     'thumb' => '/images/',
// );
// $result = $model->where('id', '=', 166)->update($update_array);


/**
 * C
 */

// $insertArray = array(
//     'title' => 'linux4',
//     'thumb' => '/images4/',
// );
// $result = $model->insert($insertArray);

// D
$result = $model->where('id=175')->delete();
p($model->getLastSql());
p($result);
