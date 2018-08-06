<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class ServerGroupModel extends DB_Model
{
    // function __construct()
    // {
    //     parent::__construct();
    //     $this->account_server_name = 'account_server_name';
    //     $this->login_server_name = 'login_server_name';
    // }

    function get_group_info()
    {
        $query = $this->db->get('server_group');
        return $query->result();
    }
}