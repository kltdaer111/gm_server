<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mark extends CI_Controller
{

	private $db_config;
	function __construct()
	{
		parent::__construct();

		$this->db_config = array();
		$this->db_config['hostname'] = "139.196.41.108";
		$this->db_config['username'] = 'wsy';
		$this->db_config['password'] = 'Wsy1985!';
		$this->db_config['database'] = 'sg_up';
		$this->db_config['dbdriver'] = "mysql";
		$this->db_config['db_debug'] = false;
        // $this->db_config['dbprefix'] = "";
        // $this->db_config['pconnect'] = TRUE;
	}

	public function query_mark()
	{
		$this->load->model('MarkModel', '', $this->db_config);
		$res = $this->MarkModel->get_mark_info();
		$this->Response->response(200, $res);
	}

	public function query_account()
	{
		$this->load->model('MarkModel', '', $this->db_config);
		$res = $this->MarkModel->get_account_info();
		$this->Response->response(200, $res);
	}

	public function query_login()
	{
		$this->load->model('MarkModel', '', $this->db_config);
		$res = $this->MarkModel->get_login_info();
		$this->Response->response(200, $res);
	}

	public function query_all()
	{
		$this->load->model('MarkModel', '', $this->db_config);
		$res = $this->MarkModel->get_all_info($_POST['mark_type']);
		$this->Response->response(200, $res);
	}

	public function update()
	{
		$this->load->model('MarkModel', '', $this->db_config);
		$mark = $_POST['mark_type'];
		foreach ($_POST['changed'] as $key => $val) {
			$table = $this->MarkModel->judge_col_belong_table($key);
			if($table == 'platform_info'){
				$this->MarkModel->update($table, array($key => $val), $mark);
			}
			else{
				$this->MarkModel->insert($table, array($key => $val), $mark);
			}
		}
	}
}