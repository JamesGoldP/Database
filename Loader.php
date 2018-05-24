<?php
class Loader
{
	static $classMap = array();  //要加载的类

	static function _autoload($class)
	{
		if(isset(self::$classMap[$class])){
			return true;
		}

		$file = './'.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
		$file = str_replace('/mysql/', '/src/', $file);
		if( file_exists($file) ){
			include $file;
			self::$classMap[$class] = $class;
		} else {
			return false;
		}
	}

}