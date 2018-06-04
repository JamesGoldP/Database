 Database connection classes for mysql.(mysql,mysqli,pdo)

 [![Latest Stable Version](https://poser.pugx.org/yilongpeng/mysql/v/stable)](https://packagist.org/packages/yilongpeng/mysql)


## Installation

Use [composer](http://getcomposer.org) to install yilong/mysql in your project:
```
composer require yilongpeng/mysql
```


## Usage
```php
use yilongpeng\MySQLi;

//load config
$config = include './database.php';

$mysql = new MySQLi();
$mysql->open($config['master']);
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
