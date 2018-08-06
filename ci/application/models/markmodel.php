<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MarkModel extends DB_Model
{
    function __construct()
    {
        parent::__construct();
        $this->account_server_name = 'account_server_name';
        $this->login_server_name = 'login_server_name';
    }

    public function get_mark_info($filter)
    {
        //$query = $this->db->get('platform_info');
        //echo $this->db->_error_message();
        $sql = "SELECT * FROM platform_info WHERE BINARY mark_type LIKE '%{$filter}%'";
        $query = $this->db->query($sql);
        return $query->result();
    }

    public function get_account_info()
    {
        //$query = $this->db->get('account_server_table');
        //echo $this->db->_error_message();
        $sql = "SELECT account_server_table.server_name, ip, port, mark_type FROM account_server_table LEFT JOIN (SELECT server_name, GROUP_CONCAT(mark_type SEPARATOR ';') AS mark_type FROM account_server_list GROUP BY server_name) AS t1 ON account_server_table.server_name=t1.server_name";
        $query = $this->db->query($sql);
        return $query->result();
    }

    public function get_account_table_by_name($server_name)
    {
        $this->db->where('server_name', $server_name);
        $query = $this->db->get('account_server_table');
        return $query->result();
    }

    public function get_login_info()
    {
        $query = $this->db->get('login_server_table');
        //echo $this->db->_error_message();
        return $query->result();
    }

    public function get_login_table_by_name($server_name)
    {
        $this->db->where('server_name', $server_name);
        $query = $this->db->get('login_server_table');
        return $query->result();
    }

    public function get_all_info($mark)
    {
        $sql = "SELECT platform_info.mark_type, platform_name, channel_name, version, compatible_version, t3.bbs_url, t4.plist_url, t5.notice_url, t6.res_url, t7.combat_url, t1.{$this->login_server_name}, t2.{$this->account_server_name} FROM platform_info LEFT JOIN (SELECT GROUP_CONCAT(bbs_url SEPARATOR ';') AS bbs_url, mark_type FROM bbs_info GROUP BY mark_type) AS t3 ON t3.mark_type=platform_info.mark_type LEFT JOIN (SELECT GROUP_CONCAT(plist_url SEPARATOR ';') AS plist_url, mark_type FROM plist_info GROUP BY mark_type) AS t4 ON t4.mark_type=platform_info.mark_type LEFT JOIN (SELECT GROUP_CONCAT(notice_url SEPARATOR ';') AS notice_url, mark_type FROM notice_info GROUP BY mark_type) AS t5 ON t5.mark_type=platform_info.mark_type LEFT JOIN (SELECT GROUP_CONCAT(res_url SEPARATOR ';') AS res_url, mark_type FROM res_info GROUP BY mark_type) AS t6 ON t6.mark_type=platform_info.mark_type LEFT JOIN (SELECT GROUP_CONCAT(combat_url SEPARATOR ';') AS combat_url, mark_type FROM combat_view GROUP BY mark_type) AS t7 ON t7.mark_type=platform_info.mark_type LEFT JOIN (SELECT GROUP_CONCAT(server_name SEPARATOR ';') AS {$this->login_server_name}, mark_type FROM login_server_list GROUP BY mark_type) AS t1 ON t1.mark_type=platform_info.mark_type LEFT JOIN (SELECT GROUP_CONCAT(server_name SEPARATOR ';') AS {$this->account_server_name}, mark_type FROM account_server_list GROUP BY mark_type) AS t2 ON t2.mark_type=platform_info.mark_type WHERE platform_info.mark_type='{$mark}';";
        $query = $this->db->query($sql);
        return $query->result();
    }

    public function judge_col_belong_table($col_name)
    {
        if ($col_name == $this->account_server_name) {
            return 'accout_server_list';
        }
        if ($col_name == $this->login_server_name) {
            return 'login_server_list';
        }
        switch ($col_name) {
            case 'platform_name':
            case 'channel_name':
            case 'version':
            case 'compatible_version':
                return 'platform_info';
            case 'bbs_url':
                return 'bbs_info';
            case 'combat_url':
                return 'combat_view';
            case 'notice_url':
                return 'notice_info';
            case 'plist_url':
                return 'plist_info';
            case 'res_url':
                return 'res_info';
        }
    }

    // public function update($table, $col_val_array, $mark_type)
    // {
    //     $this->db->where('mark_type', $mark_type);
    //     $this->db->update($table, $col_val_array);
    // }

    // public function insert($table, $col_val_array, $mark_type)
    // {
    //     $col_val_array['mark_type'] =  $mark_type;
    //     $this->db->insert($table, $col_val_array);
    // }

    public function insertIntoPlatform($mark_type, $platform_name, $channel_name, $version, $compatible_version)
    {
        $col_val_array = array('mark_type' => $mark_type, 'platform_name' => $platform_name, 'channel_name' => $channel_name, 'version' => $version, 'compatible_version' => $compatible_version);
        return $this->db->insert('platform_info', $col_val_array);   
    }

    public function insertIntoBBS($mark_type, $array_bbs_url)
    {
        $col_val_array = array();
        foreach($array_bbs_url as $bbs_url){
            array_push($col_val_array, array('mark_type' => $mark_type, 'bbs_url' => $bbs_url));
        }
        return $this->db->insert_batch('bbs_info', $col_val_array);   
    }

    public function insertIntoPList($mark_type, $array_plist_url)
    {
        $col_val_array = array();
        foreach($array_plist_url as $plist_url){
            array_push($col_val_array, array('mark_type' => $mark_type, 'plist_url' => $plist_url));
        }
        return $this->db->insert_batch('plist_info', $col_val_array);   
    }

    public function insertIntoNotice($mark_type, $array_notice_url)
    {
        $col_val_array = array();
        foreach($array_notice_url as $notice_url){
            array_push($col_val_array, array('mark_type' => $mark_type, 'notice_url' => $notice_url));
        }
        return $this->db->insert_batch('notice_info', $col_val_array);   
    }

    public function insertIntoRes($mark_type, $array_res_url)
    {
        $col_val_array = array();
        foreach($array_res_url as $res_url){
            array_push($col_val_array, array('mark_type' => $mark_type, 'res_url' => $res_url));
        }
        return $this->db->insert_batch('res_info', $col_val_array);   
    }

    public function insertIntoCombat($mark_type, $array_combat_url)
    {
        $col_val_array = array();
        foreach($array_combat_url as $combat_url){
            array_push($col_val_array, array('mark_type' => $mark_type, 'combat_url' => $combat_url));
        }
        return $this->db->insert_batch('combat_info', $col_val_array);   
    }

    public function insertIntoAccount($mark_type, $server_name, $ip, $port)
    {
        $col_val_array = array('mark_type' => $mark_type, 'server_name' => $server_name, 'ip' => $ip, 'version' => $version, 'port' => $port);
        return $this->db->insert('account_server_list', $col_val_array);  
    }

    public function insertIntoLogin($mark_type, $server_name, $ip, $port)
    {
        $col_val_array = array('mark_type' => $mark_type, 'server_name' => $server_name, 'ip' => $ip, 'version' => $version, 'port' => $port);
        return $this->db->insert('account_server_list', $col_val_array);  
    }
}