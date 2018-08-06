<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ServerOper extends CI_Controller {

    private $db_config;
	function __construct()
	{
		parent::__construct();

		$this->db_config = array();
		$this->db_config['hostname'] = "139.196.41.108";
		$this->db_config['username'] = 'wsy';
		$this->db_config['password'] = 'Wsy1985!';
		$this->db_config['database'] = 'sg_gm';
		$this->db_config['dbdriver'] = "mysql";
		$this->db_config['db_debug'] = false;
	}

    public function query_group()
    {
        $this->load->model('ServerGroupModel', '', $this->db_config);
		$res = $this->ServerGroupModel->get_group_info();
		$this->Response->response(200, $res);
    }
}