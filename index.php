<?php
include './Loader.php';
spl_autoload_register('Loader::_autoload');

//load config
$config = include './database.php';

$mysql = new yilongpeng\PDOMySQL();
$mysql->open($config['master']);
$result = $mysql->select('*', 'cms_category');
echo '<pre>';
print_r($result);
