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
				$sql = "SELECT gm_server_list.server_id,server_ssh.ip,port,username,passwd,location FROM gm_server_list LEFT OUTER JOIN server_ssh ON gm_server_list.server_id=server_ssh.server_id LEFT OUTER JOIN ssh_user ON server_ssh.ip=ssh_user.ip AND username=user";
				$result = $db_con->query($sql);
				$data = mysqli_fetch_array($result, $MYSQLI_ASSOC);
				$ip = $data['ip'];
				$port = $data['port'];
				$user = $data['username'];
				$passwd = $data['passwd'];
				$location = $data['location'];
				$conn = ssh2_connect($ip,$port);
				ssh2_auth_password($conn, $user, $passwd);
				$cmd = "cd {$location}";
				ssh2_exec($conn, $cmd);
				switch($msg_data['oper']){
					case 'server_start':
						$cmd = 'sh run.sh';
						ssh2_exec($conn, $cmd);
						$stream = ssh2_exec($conn, 'y');
						stream_set_blocking($stream, true);
						$result_string = stream_get_contents($stream);
						echo {};
						break;
					case 'server_shut':
						$cm = 'sh stop.sh';
						ssh2_exec($conn, $cmd);
						ssh2_exec($conn, $y);
						echo {};
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