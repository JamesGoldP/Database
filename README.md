 Database connection classes for mysql.(mysql,mysqli,pdo)

## Get Started

1. Use [composer](http://getcomposer.org) to install yilong/mysql in your project:
```
composer require yilong/mysql
```


2. Usage
```php
use mysql\MySQLi;

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
