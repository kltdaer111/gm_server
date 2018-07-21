<?php

require_once("../sg_gm/lib/log_debug.php");

class SSH2Obj
{
    private $m_ip;

    private $m_port;

    private $m_user;

    private $m_passwd;

    private $m_conn;

    public function __construct($ip, $port, $user, $passwd)
    {
        $this->m_ip = $ip;
        $this->m_port = $port;
        $this->m_user = $user;
        $this->m_passwd = $passwd;
        $conn = ssh2_connect($ip, $port);
        if ($conn == false) {
            throw new Exception("Can't connect to {$ip}:{$port}");
        }
        if (ssh2_auth_password($conn, $user, $passwd) == false) {
            throw new Exception("Wrong user:{$user} or password");
        }
        $this->m_conn = $conn;
    }

    public function sync_operation($cmd)
    {
        $stream = ssh2_exec($this->m_conn, $cmd);
        //log_debug($cmd);
        stream_set_blocking($stream, true);
        return stream_get_contents($stream);
    }

    public function sync_oper($cmd)
    {
        $stream = ssh2_exec($this->m_conn, $cmd);
        $dio_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);  //获得标准输入输出留
        $err_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);  //获得错误输出留
        stream_set_blocking($err_stream, true);
        stream_set_blocking($dio_stream, true);
        $result_err = stream_get_contents($err_stream);
        if($result_err === false){
            $result_err = '';
        }
        $result_dio = stream_get_contents($dio_stream); //获取流的内容，即命令的返回内容
        if($result_dio === false){
            $result_dio = '';
        }
        fclose($stream);
        return array($result_dio, $result_err);
    }

    public function async_operation($cmd)
    {
        $stream = ssh2_exec($this->m_conn, $cmd);
    }
}

?>