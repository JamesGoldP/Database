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
