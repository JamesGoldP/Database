<?php
class Loader
{
	static $classMap = array();  //to load classes

	static function _autoload($class)
	{
		if(isset(self::$classMap[$class])){
			return true;
		}
		$file = __DIR__.'/'.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
		$file = str_replace('/Nezumi/', '/src/', $file);
		if( file_exists($file) ){
			include $file;
			self::$classMap[$class] = $class;
		} else {
			return false;
		}
	}

}