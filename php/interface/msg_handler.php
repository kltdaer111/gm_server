<?php

require_once("../sg_gm/db_connect.php");

$msg_id = $_POST['msg_id'];
$msg_data = $_POST['msg_data'];



switch($msg_id){
	case 1:
	{
		$db_con = get_connect('sg_gm');
		if($db_con->connect_errno){
			throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
		}
		$sql = "SELECT gm_server_list.server_id,server_name,server_ssh.ip,username,location FROM gm_server_list LEFT OUTER JOIN server_ssh ON gm_server_list.server_id=server_ssh.server_id WHERE gm_server_list.server_id >= {$msg_data['start_id']} AND gm_server_list.server_id <= {$msg_data['end_id']};";
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
		foreach($msg_data['server'] as $server_id=>$choosen){
			if($choosen == 1){
				$conn = ssh2_connect($ip,$port);
				ssh2_auth_password($conn, $user $passwd);
				$cmd = "cd {$location}";
				ssh2_exec($conn, $cmd);
				switch($msg_data['oper']){
					case 'server_start':
					$cmd = 'sh run.sh';
					ssh2_exec($conn, $cmd);
					ssh2_exec($conn, 'y');
					break;
					case 'server_shut':
					$cm = 'sh stop.sh';
					ssh2_exec($conn, $cmd);
					ssh2_exec($conn, $y);
					break;
				}
			}
		}
	}
	break;
	default:
		return;
}

?>