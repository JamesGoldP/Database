Database connection classes for mysql.(mysql,mysqli,pdo)


## Installation

Use [composer](http://getcomposer.org) to install Nezumi/mysql in your project:
```
composer require Nezumi/mysql
```


## Usage
```php
use Nezumi\MySQLi;

//load config
$config = include './configs/database.php';

$mysql = new MySQLi();
$mysql->open($config['master']);
// $result = $mysql->select('*', 'cms_category');
echo '<pre>';

// D
$mysql->options['table'] = 'cms_account';
$result = $mysql->where(['name'=>'jimmy2'])->delete();

//C
$insert_array = array(
	'name'=>'jimmy',
	'money'=>1000,
);
$result = $mysql->insert($insert_array, 'cms_account');

$update_array = array(
	'name' => 'jimmy2',
	'money' => 2000,
);
$where = "name='jimmy'";
$result = $mysql->where($where)->update($update_array);

$result = $mysql->fields(['name','money'])->limit(99)->order('id desc')->group('name')->having('name=\'jimmy2\'')->select();
print_r($result);
$mysql->close();
```



## sample database.php 
```php
return array (
	'master' => array (
		'hostname' => 'localhost',
		'database' => 'mycms',
		'username' => 'root',
		'password' => 'root',
		'tablepre' => 'cms_',
		'charset' => 'utf8',
		'type' => 'mysql',
		'debug' => true,
		'pconnect' => 0,
		'autoconnect' => 0
	),
);

```
