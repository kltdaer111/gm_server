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
		$res = $this->MarkModel->get_mark_info($_POST['filter']);
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
			if ($table == 'platform_info') {
				$this->MarkModel->update($table, array($key => $val), $mark);
			} else {
				$this->MarkModel->insert($table, array($key => $val), $mark);
			}
		}
	}

	public function insert()
	{
		$this->load->model('MarkModel', '', $this->db_config);

		$data = $_POST;
		if (!isset($data['mark_type'])) {
			$this->Response->response(500);
			return;
		}

		$platform_name = key_exists('platform_name', $data) ? $data['platform_name'] : '';
		$channel_name = key_exists('channel_name', $data) ? $data['channel_name'] : '';
		$version = key_exists('version', $data) ? $data['version'] : '';
		$compatible_version = key_exists('compatible_version', $data) ? $data['compatible_version'] : '';
		if ($this->MarkModel->insertIntoPlatform($data['mark_type'], $platform_name, $channel_name, $version, $compatible_version) === false) {
			$this->Response->response(500);
			return;
		}

		$this->load->helper('common_func');

		if (is_array_key_valid($data, 'bbs_url', array(''))) {
			$urls = explode(';', $data['bbs_url']);
			if ($this->MarkModel->insertIntoBBS($data['mark_type'], $urls) === false) {
				$this->Response->response(500);
				return;
			}
		}

		if (is_array_key_valid($data, 'plist_url', array(''))) {
			$urls = explode(';', $data['plist_url']);
			if ($this->MarkModel->insertIntoPList($data['mark_type'], $urls) === false) {
				$this->Response->response(500);
				return;
			}
		}

		if (is_array_key_valid($data, 'notice_url', array(''))) {
			$urls = explode(';', $data['notice_url']);
			if ($this->MarkModel->insertIntoNotice($data['mark_type'], $urls) === false) {
				$this->Response->response(500);
				return;
			}
		}

		if (is_array_key_valid($data, 'res_url', array(''))) {
			$urls = explode(';', $data['res_url']);
			if ($this->MarkModel->insertIntoRes($data['mark_type'], $urls) === false) {
				$this->Response->response(500);
				return;
			}
		}

		if (is_array_key_valid($data, 'combat_url', array(''))) {
			$urls = explode(';', $data['combat_url']);
			if ($this->MarkModel->insertIntoCombat($data['mark_type'], $urls) === false) {
				$this->Response->response(500);
				return;
			}
		}

		if (is_array_key_valid($data, 'login_server_name', array(''))) {
			$names = explode(';', $data['login_server_name']);
			//TODO
			// if ($this->MarkModel->insertIntoLogin($data['mark_type'], $data['combat_url']) === false) {
			// 	$this->Response->response(500);
			// 	return;
			// }
		}

		if (is_array_key_valid($data, 'account_server_name', array(''))) {
			$names = explode(';', $data['account_server_name']);
			foreach($names as $name){
				$data = $this->MarkModel->get_account_table_by_name($name);
				$this->MarkModel->insertIntoAccount($mark_type, $data[0]['server_name'], $data[0]['ip'], $data[0]['port']);
			}
			//TODO结果检查
		}

		$this->Response->response(200);
	}
}