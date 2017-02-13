<?php
/**
 * @author 		Remco van der Velde
 * @since 		2016-10-19
 * @version		1.0
 * @changeLog
 * 	-	all
 */ 
namespace Priya\Module\Core;

class Cli extends Result {
	
	public function __construct($handler=null, $route=null, $data=null){
		parent::__construct($handler, $route, $data);
	}
	
	public function color($color='', $background=''){
		switch($color){
			case 'red':
				return "\033[31m";
			break;
			case 'green':
				return "\033[32m";
			break;			
			case 'end':
				return "\033[0m";
			break;
			break;
		}
	}
	
	public function parameter($parameter){
		$data = $this->request('data');
		foreach($data as $key => $param){
			$param = ltrim($param,'-');
			$tmp = explode('=', $param);
			if(count($tmp) > 1){
				$param = array_shift($tmp);
				$value = implode('=', $tmp);
			}
			if(strtolower($param) == strtolower($parameter)){
				if(isset($value)){
					return $value;
				}
				return true;
			}
		}
		return false;
	}
	
	
}