<?php
error_reporting(-1);
include '../Loader.php';
spl_autoload_register('Loader::_autoload');
use Nezumi\PDOMySQL;

//load config
$config = include '../configs/database.php';
$mysql = new PDOMySQL();
$link =  $mysql->open($config['master']);

// D
$mysql->options['table'] = 'cms_account';
$result = $mysql->where(['name'=>'jimmy2'])->delete();

//C
$insert_array = array(
	'name'=>'jimmy',
	'money'=>1000,
);
$result = $mysql->insert($insert_array, 'cms_account');

// U
$update_array = array(
	'name' => 'jimmy2',
	'money' => 2000,
);
$where = "name='jimmy'";
$result = $mysql->update($update_array, 'cms_account', $where);

// R
$result = $mysql->get_primary('cms_account');
$result = $mysql->select(array('name','money'), 'cms_account', '', 99, 'id desc','name','','name=\'jimmy2\'');

print_r($result);
$mysql->close();