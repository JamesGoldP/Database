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
require_once './src/functions.php';

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
$result = collection($model->where('id', ['>=', 1], ['<=', 7], 'and')->select())->toArray();


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
//     ['title' => 'linux7','thumb' => '/images7/'],
//     ['title' => 'linux8','thumb' => '/images8/'],
// );

// foreach($insertArray as $key=>$value){
//     $result = $model->insert($value);
//     p($model->getLastSql());
//     p($result);
// }


// D
// $result = $model->where('id=175')->delete();
p($result);


