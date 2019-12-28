<?php
class Loader
{
	static $classMap = [];  //to load classes

	static function _autoload($class)
	{
		if(isset(self::$classMap[$class])){
			return true;
		}
		$file = __DIR__.'/'.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
		$file = str_replace('/zero/', '/src/', $file);
		if( file_exists($file) ){
			include $file;
			self::$classMap[$class] = $class;
		} else {
			return false;
		}
	}

}