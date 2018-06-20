<?php
include './Loader.php';
spl_autoload_register('Loader::_autoload');
use Nezumi\MySQLi;

//load config
$config = include './configs/database.php';

$mysql = new MySQLi();
$mysql->open($config['master']);
// $result = $mysql->select('*', 'cms_category');
echo '<pre>';
$result = $mysql->fields('*')->table('cms_category')->select();
print_r($result);