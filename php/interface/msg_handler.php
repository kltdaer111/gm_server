<?php

require_once("../sg_gm/db_connect.php");
require_once("../sg_gm/lib/log_debug.php");
require_once("../util/ssh2_obj.php");

$msg_id = $_POST['msg_id'];
$msg_data = json_decode($_POST['msg_data'], true);

function get_ssh_need_info($db_con, $server_id)
{
	$sql = "SELECT server_id, ip, port, username, passwd, location FROM ssh_user LEFT OUTER JOIN server_ssh ON ssh_user.id=server_ssh.ssh_id WHERE server_id={$server_id}";
	$result = $db_con->query($sql);
	if ($result == false) {
		throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
	}
	$data = mysqli_fetch_array($result, MYSQLI_ASSOC);
	if ($data == false) {
		throw new Exception("NO SUCH DATA, SERVER_ID:" . $server_id);
	}
	return $data;
}

function update_last_server_action($db_con, $server_id, $action_id, $type, $server_type = 'server')
{
	$sql = "UPDATE server_action SET last_server_action={$action_id} WHERE server_id={$server_id} AND type='{$type}' AND server_type='{$server_type}';";
	$result = $db_con->query($sql);
	if ($result == false) {
		throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
	}
}

function response($code, $data)
{
	$res = array();
	$res['code'] = $code;
	$res['data'] = $data;
	echo json_encode($res);
}

function get_log_struct()
{
	$log = array();
	$log['error_out'] = array();
	$log['standard_out'] = array();
	return $log;
}

switch ($msg_id) {
	case 1:
		{
			$db_con = get_connect('sg_gm');
			if ($db_con->connect_errno) {
				throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
			}
			$sql = "SELECT server_ssh.server_id, server_ssh.server_type, gm_server_list.ip, gm_server_list.server_name, location, last_server_action FROM server_ssh LEFT OUTER JOIN gm_server_list ON gm_server_list.server_id=server_ssh.server_id LEFT OUTER JOIN server_action ON gm_server_list.server_id=server_action.server_id AND server_action.type='action' WHERE server_ssh.server_id >= {$msg_data['start_id']} AND server_ssh.server_id <= {$msg_data['end_id']} AND server_ssh.server_type = '{$msg_data['server_type']}';";
			$result = $db_con->query($sql);
			if ($result == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			$result_array = array();
			while ($array_data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				array_push($result_array, $array_data);
			}
			echo json_encode($result_array);
		}
		break;
	case 2:
		{
			// log_debug(print_r($msg_data, true));
			$db_con = get_connect('sg_gm');
			$res = array();
			$now = time();
			foreach ($msg_data['server'] as $server_id => $choosen) {
				if ($choosen == 1) {
					log_debug('do server:' . $server_id);
				//进行操作
					$data = get_ssh_need_info($db_con, $server_id);
					// log_debug(print_r($data, true));
					$ip = $data['ip'];
					$port = $data['port'];
					$user = $data['username'];
					$passwd = $data['passwd'];
					$location = $data['location'];
					$extra_data = '';
					switch ($msg_data['oper']) {
						case '开启服务器':
							$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
							update_last_server_action($db_con, $server_id, 3, 'action');
							$cmd = 'sh ' . $location . '/run.sh -y';
							// log_debug($cmd);
							$res[$server_id] = array();
							$return_array = $ssh2_obj->sync_oper($cmd);
							$res[$server_id]['standard_out'] = array();
							$res[$server_id]['standard_out']['open_server'] = $return_array[0];
							$res[$server_id]['error_out'] = array();
							$res[$server_id]['error_out']['open'] = $return_array[1];
							update_last_server_action($db_con, $server_id, 4, 'action');
							break;
						case '开启全局服':
							$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
							update_last_server_action($db_con, $server_id, 3, 'action');
							$cmd = 'sh ' . $location . '/run.sh -gy';
							// log_debug($cmd);
							$res[$server_id] = array();
							$return_array = $ssh2_obj->sync_oper($cmd);
							$res[$server_id]['standard_out'] = array();
							$res[$server_id]['standard_out']['open_global'] = $return_array[0];
							$res[$server_id]['error_out'] = array();
							$res[$server_id]['error_out']['open_global'] = $return_array[1];
							update_last_server_action($db_con, $server_id, 4, 'action', 'global');
							break;
						case '关闭服务器':
							$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
							update_last_server_action($db_con, $server_id, 1, 'action');
							$cmd = 'sh ' . $location . '/stop.sh -y';
							$res[$server_id] = array();
							$return_array = $ssh2_obj->sync_oper($cmd);
							$res[$server_id]['standard_out'] = array();
							$res[$server_id]['standard_out']['shut_server'] = $return_array[0];
							$res[$server_id]['error_out'] = array();
							$res[$server_id]['error_out']['shut_server'] = $return_array[1];
							update_last_server_action($db_con, $server_id, 2, 'action');
							break;
						case '关闭全局服':
							$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
							update_last_server_action($db_con, $server_id, 1, 'action');
							$cmd = 'sh ' . $location . '/stop.sh -gy';
							$res[$server_id] = array();
							$return_array = $ssh2_obj->sync_oper($cmd);
							$res[$server_id]['standard_out'] = array();
							$res[$server_id]['standard_out']['shut_global'] = $return_array[0];
							$res[$server_id]['error_out'] = array();
							$res[$server_id]['error_out']['shut_global'] = $return_array[1];
							update_last_server_action($db_con, $server_id, 2, 'action', 'global');
							break;
						case '重启服务器':
							$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
							update_last_server_action($db_con, $server_id, 5, 'action');
							$cmd = 'sh ' . $location . '/stop.sh -y';
							$res[$server_id] = array();
							$return_array = $ssh2_obj->sync_oper($cmd);
							$res[$server_id]['standard_out'] = array();
							$res[$server_id]['standard_out']['shut_server'] = $return_array[0];
							$res[$server_id]['error_out'] = array();
							$res[$server_id]['error_out']['shut_server'] = $return_array[1];
							update_last_server_action($db_con, $server_id, 7, 'action');
							$cmd = 'sh ' . $location . '/run.sh -y';
							$return_array = $ssh2_obj->sync_oper($cmd);
							$res[$server_id]['standard_out']['open'] = $return_array[0];
							$res[$server_id]['error_out']['open'] = $return_array[1];
							update_last_server_action($db_con, $server_id, 8, 'action');
							break;
						case '关服更新':
						//先关服
							$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
							update_last_server_action($db_con, $server_id, 1, 'action');
							$cmd = 'sh ' . $location . '/stop.sh -y';
							$res[$server_id] = array();
							$return_array = $ssh2_obj->sync_oper($cmd);
							// $res[$server_id]['standard_out'] = $return_array[0];
							// $res[$server_id]['error_out'] = $return_array[1];
							update_last_server_action($db_con, $server_id, 2, 'action');
						//再更新
						case '不关服更新':
							update_last_server_action($db_con, $server_id, 1, 'update');
							$server_source = $msg_data['para1'];
							$server_version = $msg_data['para2'];
							$extra_data = $server_source . ';' . $server_version;
							$standard_output = "./copylog/{$server_id}_{$now}_standard.log";
							$error_output = "./copylog/{$server_id}_{$now}_error.log";
							switch ($server_source) {
								case '内网Branches':
									$this_obj = new SSH2Obj('192.168.1.5', 22, 'branches', 'branches');
									// log_debug(__LINE__);
									$res[$server_id]['modify'] = $this_obj->sync_operation('cd ~/sg_server/sh && sh modify_version.sh ' . $server_version);
									// log_debug($res[$server_id]['modify']);
									$this_obj->sync_operation("cd ~/sg_server/nohup_run && yes Yes | sh copy.sh {$ip} {$user} all 1>{$standard_output} 2>{$error_output}");
									$res[$server_id]['standard_out'] = array();
									$res[$server_id]['error_out'] = array();
									$res[$server_id]['standard_out']['copy'] = $this_obj->sync_operation("cat ~/sg_server/nohup_run/{$standard_output}");
									$res[$server_id]['error_out']['copy'] = $this_obj->sync_operation("cat ~/sg_server/nohup_run/{$error_output}");
									// log_debug($res[$server_id]['standard_out']);
									// log_debug($res[$server_id]['error_out']);
									break;
								case '内网Trunk':
									$this_obj = new SSH2Obj('192.168.1.5', 22, 'nei', 'nei');
									$this_obj->sync_operation('cd ~/sg_server/nohup_run');
									$this_obj->sync_operation("cd ~/sg_server/nohup_run && yes Yes | sh copy.sh {$ip} {$user} all 1>{$standard_output} 2>{$error_output}");
									$res[$server_id]['standard_out'] = array();
									$res[$server_id]['error_out'] = array();
									$res[$server_id]['standard_out']['copy'] = $this_obj->sync_operation("cat ~/sg_server/nohup_run/{$standard_output}");
									$res[$server_id]['error_out']['copy'] = $this_obj->sync_operation("cat ~/sg_server/nohup_run/{$error_output}");
									// log_debug($res[$server_id]['standard_out']);
									// log_debug($res[$server_id]['error_out']);
									break;
							}

							update_last_server_action($db_con, $server_id, 2, 'update');
							break;
						case '执行更新':
							update_last_server_action($db_con, $server_id, 3, 'update');
							$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
							$res[$server_id]['standard_out'] = array();
							$res[$server_id]['error_out'] = array();
							if ($msg_data['code'] !== '') {
								$extra_data .= 'code:';
								$extra_data .= $msg_data['code'];
								$pieces = explode(';', $msg_data['code']);
								$cmd = "cd {$location}/../trunk && svn up";
								foreach ($pieces as $file) {
									$cmd .= ' ';
									$cmd .= $file;
								}
								$cmd .= ' && make -j11';
								$return_array = $ssh2_obj->sync_oper($cmd);
								$res[$server_id]['standard_out']['code'] = $return_array[0];
								$res[$server_id]['error_out']['code'] = $return_array[1];
								$extra_data .= ',';
							}
							if ($msg_data['map'] !== '') {
								$extra_data .= 'map:';
								$extra_data .= $msg_data['map'];
								$pieces = explode(';', $msg_data['map']);
								$standard_out = '';
								$error_out = '';
								foreach ($pieces as $file) {
									$cmd = "cd {$location}/../trunk && sh update.sh map {$file}";
									$return_array = $ssh2_obj->sync_oper($cmd);
									$standard_out .= $return_array[0];
									//$standard_out .= ',';
									$error_out .= $return_array[1];
									//$error_out .= ',';
								}
								$res[$server_id]['standard_out']['map'] = substr($standard_out, 0, strlen($standard_out) - 1);
								$res[$server_id]['error_out']['map'] = substr($error_out, 0, strlen($error_out) - 1);
							}
							if ($msg_data['lua'] !== '') {
								$extra_data .= 'lua:';
								$extra_data .= $msg_data['lua'];
								$extra_data .= ',';
								$pieces = explode(';', $msg_data['lua']);
								$standard_out = '';
								$error_out = '';
								foreach ($pieces as $file) {
									$cmd = "cd {$location}/../trunk && sh update.sh lua {$file}";
									$return_array = $ssh2_obj->sync_oper($cmd);
									$standard_out .= $return_array[0];
									//$standard_out .= ',';
									$error_out .= $return_array[1];
									//$error_out .= ',';
								}
								$res[$server_id]['standard_out']['lua'] = substr($standard_out, 0, strlen($standard_out) - 1);
								$res[$server_id]['error_out']['lua'] = substr($error_out, 0, strlen($error_out) - 1);
							}
							if ($msg_data['tbl'] !== '') {
								$extra_data .= 'tbl:';
								$extra_data .= $msg_data['tbl'];
								$pieces = explode(';', $msg_data['tbl']);
								$standard_out = '';
								$error_out = '';
								foreach ($pieces as $file) {
									$cmd = "cd {$location}/../trunk && sh update.sh tbl {$file}";
									$return_array = $ssh2_obj->sync_oper($cmd);
									log_debug($cmd);
									$standard_out .= $return_array[0];
									//$standard_out .= ',';
									$error_out .= $return_array[1];
									//$error_out .= ',';
								}
								$res[$server_id]['standard_out']['tbl'] = substr($standard_out, 0, strlen($standard_out) - 1);
								$res[$server_id]['error_out']['tbl'] = substr($error_out, 0, strlen($error_out) - 1);
							}
							update_last_server_action($db_con, $server_id, 4, 'update');
							break;
					}
					//添加日志
					$str_log = mysqli_real_escape_string($db_con, json_encode($res[$server_id]));
					log_debug($str_log);
					$sql = "INSERT INTO server_operation_log SET server_id={$server_id},operation_type='{$msg_data['oper']}',operation_user='{$msg_data['user']}',operation_date=FROM_UNIXTIME({$now}),extra_data='{$extra_data}', log='{$str_log}';";
					$result = $db_con->query($sql);
					if ($result == false) {
						throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
					}
				}
			}
			echo json_encode($res);
		}
		break;
	case 3:
		{
		// log_debug($msg_data);
			$db_con = get_connect('sg_gm');
			$data = get_ssh_need_info($db_con, $msg_data);
			// log_debug(print_r($data, true));
			$ip = $data['ip'];
			$port = $data['port'];
			$user = $data['username'];
			$passwd = $data['passwd'];
			$location = $data['location'];
		// log_debug(print_r($data, true));
			$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
			$cmd = 'sh ' . $location . '/check.sh';
			// log_debug($cmd);
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
		// {
		// 	server_group : data.field.oper_choose_server_group,
		//  server_group_data : server_group_data,
		// 	server_id : data.field.oper_choose_server,
		// 	query_type : data.field.query_choice,
		// 	start_date : period_select['start'],
		// 	end_date : period_select['end'],
		// }
		// $start_time = mktime($msg_data['start_date']['hours'], $msg_data['start_date']['minutes'], $msg_data['start_date']['seconds'], $msg_data['start_date']['month'], $msg_data['start_date']['date'], $msg_data['start_date']['year']);
		// $end_time = mktime($msg_data['end_date']['hours'], $msg_data['end_date']['minutes'], $msg_data['end_date']['seconds'], $msg_data['end_date']['month'], $msg_data['end_date']['date'], $msg_data['end_date']['year']);

			$pieces = explode(' ~ ', $msg_data['date']);
			if (count($pieces) != 2) {
				throw new Exception('WRONG DATE FORMAT, size:' . count($pieces));
			}
			$start_date = $pieces[0];
			$end_date = $pieces[1];
			$sql = "";
			if ($msg_data['query_type'] == 'single') {
				$sql = "SELECT server_operation_log.server_id,ip,operation_type,operation_user,operation_date,server_name,extra_data FROM server_operation_log LEFT OUTER JOIN gm_server_list ON server_operation_log.server_id=gm_server_list.server_id WHERE server_operation_log.server_id={$msg_data['server_id']} AND UNIX_TIMESTAMP(operation_date)>=UNIX_TIMESTAMP('{$start_date}') AND UNIX_TIMESTAMP(operation_date)<=UNIX_TIMESTAMP('{$end_date}')";
			} elseif ($msg_data['query_type'] == 'group') {
				$group_name = $msg_data['server_group'];
				$group_data = $msg_data['server_group_data'];
				$start_id = $group_data[$group_name]['group_start_id'];
				$end_id = $group_data[$group_name]['group_end_id'];
				$sql = "SELECT GROUP_CONCAT(server_operation_log.server_id) AS server_id,GROUP_CONCAT(ip) AS ip,MAX(operation_type) AS operation_type,MAX(operation_user) AS operation_user,MAX(operation_date) AS operation_date,GROUP_CONCAT(server_name) AS server_name,MAX(extra_data) AS extra_data FROM server_operation_log LEFT OUTER JOIN gm_server_list ON server_operation_log.server_id=gm_server_list.server_id WHERE server_operation_log.server_id>={$start_id} AND server_operation_log.server_id<={$end_id} AND UNIX_TIMESTAMP(operation_date)>=UNIX_TIMESTAMP('{$start_date}') AND UNIX_TIMESTAMP(operation_date)<=UNIX_TIMESTAMP('{$end_date}') GROUP BY operation_date";
			} else {
				return;
			}
			$db_con = get_connect('sg_gm');
			$result = $db_con->query($sql);
			if ($result == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			$result_array = array();
			while ($array_data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				array_push($result_array, $array_data);
			}
			$res['data'] = $result_array;
			$res['group'] = $msg_data['server_group'];
			echo json_encode($res);
		}
		break;
	case 6:
		{
			$db_con = get_connect('sg_gm');
			if ($db_con->connect_errno) {
				throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
			}
			$sql = "SELECT server_id,server_name FROM gm_server_list;";
			$result = $db_con->query($sql);
			if ($result == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			$result_array = array();
			while ($array_data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				array_push($result_array, $array_data);
			}
			echo json_encode($result_array);
		}
		break;
	case 7:
		{
			$date = $msg_data['date'];
			$db_con = get_connect('sg_log', 1);
			if ($db_con->connect_errno) {
				throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
			}
			$sql = "SELECT * FROM daily_statistics_log WHERE date='{$date}'";
			$result = $db_con->query($sql);
			if ($result == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			$result_array = array();
			while ($array_data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				array_push($result_array, $array_data);
			}



			/********************** */
			$date = $msg_data['date'];
			$type = 'task';
			$db_con = get_connect('sg_log', 1);
			if ($db_con->connect_errno) {
				throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
			}
			$sql = "SELECT data FROM daily_statistics_log WHERE date='{$date}' AND type='{$type}'";
			$result = $db_con->query($sql);
			if ($result == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			//$result_array = array();
			$choose = 1;
			$id = 0;
			//$result_array = array();
			$file = fopen('../../tmp/tmp.txt', 'w');
			$array_data = mysqli_fetch_array($result, MYSQLI_ASSOC);
				//array_push($result_array, $array_data);
			$obj = json_decode($array_data['data']);
			foreach ($obj as $row) {
				foreach ($row as $col) {
					$col = mb_convert_encoding($col, 'CP936', 'UTF-8');
					fwrite($file, $col);
					fwrite($file, "\t");
				}
				fwrite($file, "\n");
			}

			/********************** */


			echo json_encode($result_array);
		}
		break;
	case 77:

		break;
	//对指定ssh执行指定命令，并返回结果
	case 8:
		{
			$user = $msg_data['user'];
			$ip = $msg_data['ip'];
			$port = $msg_data['port'];
			$cmd = $msg_data['cmd'];
			$db_con = get_connect('sg_gm');
			if ($db_con->connect_errno) {
				throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
			}
			$sql = "SELECT passwd FROM ssh_user WHERE ip='{$ip}' AND username='{$user}';";
			$result = $db_con->query($sql);
			if ($result == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			$result_array = array();
			$res = mysqli_fetch_array($result, MYSQLI_ASSOC);
			// log_debug(print_r($res, true));
			$passwd = $res['passwd'];
			// log_debug($passwd);
			$ssh2_obj = new SSH2Obj($ip, $port, $user, $passwd);
			// log_debug($cmd);
			echo json_encode($ssh2_obj->sync_operation($cmd));
		}
		break;
	//添加ssh用户
	case 9:
		{
			$db_con = get_connect('sg_gm');
			if ($db_con->connect_errno) {
				throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
			}
			$sql = "INSERT INTO ssh_user VALUES(DEFAULT, '{$msg_data['ip']}', {$msg_data['port']}, '{$msg_data['user']}', '{$msg_data['passwd']}');SELECT LAST_INSERT_ID();";
			//multi_query过程
			$bool_res = $db_con->multi_query($sql);
			if ($bool_res == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			if ($db_con->next_result() === false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			$result = $db_con->store_result();
			if ($result === false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			$res = mysqli_fetch_array($result, MYSQLI_ASSOC);
			response(200, array('res' => $res, 'data' => $msg_data));
		}
		break;
	//test
	case 10:
		{
			$db_con = get_connect('sg_gm');
			if ($db_con->connect_errno) {
				throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
			}
			$sql = "SELECT id,ip,port,username FROM ssh_user;";
			$result = $db_con->query($sql);
			if ($result == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			$result_array = array();
			while ($array_data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				array_push($result_array, $array_data);
			}
			response(200, $result_array);
		}
		break;
	case 11:
		{
			//插入服务器配置
			$db_con = get_connect('sg_gm');
			if ($db_con->connect_errno) {
				throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
			}
			$sql1 = "INSERT INTO server_ssh VALUES({$msg_data['server_id']}, '{$msg_data['server_type']}', {$msg_data['ssh_id']}, '{$msg_data['location']}');";
			$sql2 = "INSERT INTO server_action VALUES({$msg_data['server_id']}, '{$msg_data['server_type']}', 'action', 2);";
			$sql3 = "INSERT INTO server_action VALUES({$msg_data['server_id']}, '{$msg_data['server_type']}', 'update', 4);";
			$result = $db_con->multi_query($sql1 . $sql2 . $sql3);
			if ($result == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}

			response(200);
		}
		break;
	case 12:
		{
			$db_con = get_connect('sg_gm');
			if ($db_con->connect_errno) {
				throw new Exception("NO DB CONNECTION " . $db_con->connect_error);
			}
			$sql = "INSERT INTO server_group VALUES('{$msg_data['name']}', {$msg_data['server_start']}, {$msg_data['server_end']}, {$msg_data['global_start']}, {$msg_data['global_end']});";
			$result = $db_con->query($sql);
			if ($result == false) {
				throw new Exception("QUERY FAILED:" . $sql . $db_con->error);
			}
			response(200);
		}
		break;
	default:
		return;
}

?>