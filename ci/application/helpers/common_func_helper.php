<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('my_isset'))
{
	function is_array_key_valid($array, $key, $data_seen_as_not_set)
	{
		if(!array_key_exists($key, $array)){
            return false;
        }
        foreach($data_seen_as_not_set as $ele){
            if($ele === $array[$key]){
                return false;
            }
        }

		return true;
	}
}