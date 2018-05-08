<?php
$config = include '../database.php';
include '../MySql.class.php';
include '../mysqli_object_oriented.class.php';
include '../mysqli_procedural.class.php';
include '../PdoMySql.class.php';

//打开自动连接
$config['default']['autoconnect'] = 1;

$total_connect_time = 1000;
$array = array();
$start_time = microtime(true);
for($i=1;$i<=$total_connect_time;$i++){
	$mysql = new mysql();
	$mysql->open($config['default']);
}
$end_time = microtime(true);
$mysql_connect_time = $end_time-$start_time;
array_push($array, array('mysql'=>$mysql_connect_time));


$start_time = microtime(true);
for($i=1;$i<=$total_connect_time;$i++){
	$mysqli_object_oriented = new mysqli_object_oriented();
	$mysqli_object_oriented->open($config['default']);
}
$end_time = microtime(true);
$mysqli_object_oriented_connect_time = $end_time-$start_time;
array_push($array, array('mysqli_object_oriented'=>$mysqli_object_oriented_connect_time));

$start_time = microtime(true);
for($i=1;$i<=$total_connect_time;$i++){
	$mysqli_procedural = new mysqli_procedural();
	$mysqli_procedural->open($config['default']);
}
$end_time = microtime(true);
$mysqli_procedural_connect_time = $end_time-$start_time;
array_push($array, array('mysqli_procedural'=>$mysqli_procedural_connect_time));

$start_time = microtime(true);
for($i=1;$i<=$total_connect_time;$i++){
	$PdoMySql = new PdoMySql();
	$PdoMySql->open($config['default']);
}
$end_time = microtime(true);
$PdoMySql_connect_time = $end_time-$start_time;
array_push($array, array('myPdoMySqlsql'=>$PdoMySql_connect_time));

echo json_encode($array);






