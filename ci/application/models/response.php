<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Response extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    function response($code, $data = ''){
        $res['code'] = $code;
        $res['data'] = $data;
        echo json_encode($res);
    }
}