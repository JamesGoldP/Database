<?php
include './Loader.php';
spl_autoload_register('Database\Loader::_autoload');

//load config
$config = include './database.php';

$mysql = new driver\PDOMySQL();
$mysql->open($config['master']);
$result = $mysql->select('*', 'cms_category');
echo '<pre>';
print_r($result);
