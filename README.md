 Database connection classes for mysql.(mysql,mysqli,pdo)

```php
//load config
$config = include './database.php';

$mysql = new driver\PDOMySQL();
$mysql->open($config['master']);
```

