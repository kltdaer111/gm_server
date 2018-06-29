<?php

require_once("../sg_gm/db_connect.php");
require_once("../sg_gm/lib/log_debug.php");
require_once("../util/ssh2_obj.php");

$msg_id = $_POST['msg_id'];
$msg_data = json_decode($_POST['msg_data'], true);

function get_ssh_need_info($db_con, $server_id){
	$sql = "SELECT gm_server_list.server_id,server_name,server_ssh.ip,ssh_port,username,passwd,location FROM gm_server_list LEFT OUTER JOIN server_ssh ON gm_server_list.server_id=server_ssh.server_id LEFT OUTER JOIN ssh_user ON server_ssh.ip=ssh_user.ip AND username=user WHERE gm_server_list.server_id={$server_id}";
	$result = $db_con->query($sql);
	if($result == false){
		throw new Exception("QUERY FAILED:" . $sql . $this->m_db_con->error);
	}
	$data = mysqli_fetch_array($result, MYSQLI_ASSOC);
	if($data == false){
		throw new Exception("NO SUCH DATA, SERVER_ID:" . $server_id);
	}
	return $data;
}

function update_last_server_action($db_con, $server_id, $action_id){
	$sql = "UPDATE server_action SET last_server_action={$action_id} WHERE server_id={$server_id};";
	$db_con->query($sql);
}

switch($msg_id){
	case 1:
	{
		$db_con = get_connect('sg_gm');
		if($db_con->connect_errno){
			throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
		}
		$sql = "SELECT gm_server_list.server_id,server_name,server_ssh.ip,username,location,last_server_action FROM gm_server_list LEFT OUTER JOIN server_ssh ON gm_server_list.server_id=server_ssh.server_id LEFT OUTER JOIN server_action ON gm_server_list.server_id=server_action.server_id WHERE gm_server_list.server_id >= {$msg_data['start_id']} AND gm_server_list.server_id <= {$msg_data['end_id']};";
		$result = $db_con->query($sql);
		$result_array = array();
		while($array_data = mysqli_fetch_array($result, MYSQLI_ASSOC)){
			array_push($result_array, $array_data);
		}
		echo json_encode($result_array);
	}
	break;
	case 2:
	{
		log_debug(print_r($msg_data, true));
		$res = array();
		foreach($msg_data['server'] as $server_id=>$choosen){
			if($choosen == 1){
				$db_con = get_connect('sg_gm');
				$data = get_ssh_need_info($db_con, $server_id);
				log_debug(print_r($data, true));
				$ip = $data['ip'];
				$port = $data['ssh_port'];
				$user = $data['username'];
				$passwd = $data['passwd'];
				$location = $data['location'];
				$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
				switch($msg_data['oper']){
					case 'server-start':
						update_last_server_action($db_con, $server_id, 3);
						$cmd = 'sh ' . $location . '/run.sh -y';
						$result_string = $ssh2_obj->sync_operation($cmd);
						log_debug($result_string);
						$res[$server_id] = $result_string;
						update_last_server_action($db_con, $server_id, 4);
						break;
					case 'server-shut':
						update_last_server_action($db_con, $server_id, 1);
						$cmd = 'sh ' . $location . '/stop.sh -y';
						$result_string = $ssh2_obj->sync_operation($cmd);
						log_debug($result_string);
						$res[$server_id] = $result_string;
						update_last_server_action($db_con, $server_id, 2);
						break;
					case 'server-restart':

						break;
				}
			}
		}
		echo json_encode($res);
	}
	break;
	case 3:
	{
		log_debug($msg_data);
		$db_con = get_connect('sg_gm');
		$data = get_ssh_need_info($db_con, $msg_data);
		log_debug(print_r($data, true));
		$ip = $data['ip'];
		$port = $data['ssh_port'];
		$user = $data['username'];
		$passwd = $data['passwd'];
		$location = $data['location'];
		log_debug(print_r($data, true));
		$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
		$cmd = 'sh ' . $location . '/check.sh';
		echo json_encode($ssh2_obj->sync_operation($cmd) . $msg_data);
	}
	break;
	case 4:
	{
		echo json_encode(1);
	}
	break;
	case 5:
	{
		
	}
	break;
	default:
		return;
}

?>