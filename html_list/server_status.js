function ServerStatusSuit(dom_id, server_data, group_name, check_interval) {
    this.dom_id = dom_id;
    this.server_data = server_data;
    this.group_name = group_name;
    //最近一次的服务器行为
    this.last_server_action = {};
    //需要检测状态的server_id
    this.effect_server_id = [];
    //检测间隔
    this.check_interval = check_interval;
    //定时器
    this.timer_id = 0;
    //服务器状态
    this.all_server_states = {};
    //选中的服务器
    this.server_checked = {};
}

ServerStatusSuit.prototype.SERVER_STATE = {
    UNKNOWN: '未知',
    RUNNING: '运行中',
    CLOSED: '关闭',
};

ServerStatusSuit.prototype.SHOW_STATE = {
    CLOSED: '关闭',
    SHUTTING: '关闭中',
    RUNNING: '开启',
    STARTING: '开启中',
    RESTART_SHUTTING: '重启:关闭中',
    RESTART_STARTING: '重启:开启中',
    RESTART_CLOSED: '重启:已关闭',
    DOWN: '宕机',
    CANTBECLOSED: '无法关闭',
    ABNORMAL: '异常',
    NO_DATA: '检测中',
};

ServerStatusSuit.prototype.ACTION = {
    SHUT_BEGIN: 1,
    SHUT_END: 2,
    START_BEGIN: 3,
    START_END: 4,
    RESTART_SHUT_BEGIN: 5,
    RESTART_SHUT_END: 6,
    RESTART_START_BEGIN: 7,
    RESTART_START_END: 8,
};

ServerStatusSuit.prototype.LOCAL_SERVER = ['db_server', 'gm_server', 'log_server', 'game_server', 'center_server', 'chat_server', 'gate_server', 'login_server'];
ServerStatusSuit.prototype.GLOBAL_SERVER = ['account_server', 'glog_server', 'area_server', 'transfer_server', 'cross_server'];
ServerStatusSuit.prototype.NOT_WORKING_DESCRIPTION = 'has been stopped';
ServerStatusSuit.prototype.WORKING_DESCRIPTION = 'is running';

ServerStatusSuit.prototype.match_whole_word = function (src_str, search_str, begin_index = 0, ignore_front = false, ignore_back = false) {
    var not_match = true, not_match_front = true, not_match_back = true;
    var now_idx = begin_index, next_search_idx = begin_index;
    do {
        now_idx = src_str.indexOf(search_str, next_search_idx);
        if (now_idx == -1) {
            break;
        }
        next_search_idx = now_idx + 1;
        if (!ignore_front) {
            if (now_idx == 0) {
                not_match_front = false;
            }
            else if (now_idx > 0) {
                var letter = src_str[now_idx - 1];
                if (letter.search(/[a-zA-Z0-9]/) == -1) {
                    not_match_front = false;
                }
            }
        }
        else {
            not_match_front = false;
        }
        if (!ignore_back) {
            var end_idx = now_idx + search_str.length;
            if (end_idx >= src_str.length) {
                not_match_back = false;
            }
            else {
                var letter = src_str[end_idx];
                if (letter.search(/[a-zA-Z0-9]/) == -1) {
                    not_match_back = false;
                }
            }
        }
        else {
            not_match_back = false;
        }
        if (!not_match_back && !not_match_front) {
            not_match = false;
        }
        //console.log(now_idx, next_search_idx);
    } while (not_match)
    return now_idx;
}

ServerStatusSuit.prototype.generate_table = function () {
    var table_label = '#' + this.dom_id;
    var main_id = this.dom_id + '_main';
    var main_label = '#' + main_id;
    $(table_label).empty();
    $(table_label).append('\
            <thead>\
              <tr>\
                <th>\
                  <button class="layui-btn">全选</button>\
                </th>\
                <th>服务器组</th>\
                <th>服务器名称</th>\
                <th>服务器ID</th>\
                <th>服务器IP</th>\
                <th>当前状态</th>\
              </tr>\
            </thead>\
            <tbody id="' + main_id + '">\
            </tbody>');
    var res = this.server_data;
    //console.log(res);
    for (idx in res) {
        var tr_xml = '<tr id=' + res[idx]['server_id'] + '>';
        $(main_label).append(tr_xml);

        var button_xml = '<td><form class="layui-form" action=""><div class="layui-form-item"><input type="checkbox"';
        if (res[idx]['ip'] === null) {
            button_xml += ' disabled';
        }
        else {
            this.effect_server_id.push(res[idx]['server_id']);
            this.last_server_action[res[idx]['server_id']] = res[idx]['last_server_action'];
        }
        button_xml += ' value="' + res[idx]['server_id'] + '"';
        button_xml += '></div></form></td>';
        $(main_label).append(button_xml);
        $(main_label).append('<td>' + this.group_name + '</td>');
        $(main_label).append('<td>' + res[idx]['server_name'] + '</td>');
        $(main_label).append('<td>' + res[idx]['server_id'] + '</td>');
        $(main_label).append('<td>' + res[idx]['ip'] + '</td>');
        $(main_label).append('<td id=' + this.dom_id + '_state_' + res[idx]['server_id'] + '>' + '检测中' + '</td>');
        $(main_label).append('</tr>');
    }
    renderForm();
}


ServerStatusSuit.timer_function = function (self) {
    for (idx in self.effect_server_id) {
        self.get_new_state_and_refresh_display(self.effect_server_id[idx]);
    }
}

ServerStatusSuit.prototype.get_new_state_and_refresh_display = function (server_id) {
    this.gen_server_states(server_id, this.refresh_server_display);
}

ServerStatusSuit.prototype.refresh_server_display = function (server_id) {
    var new_state = this.get_display_state(server_id);
    console.log(this.all_server_states[server_id]);
    // console.log(server_id + ':' + new_state);
    var label = '#' + this.dom_id + '_state_' + server_id;
    console.log(label);
    $(label).text(new_state);
    renderForm();
}

ServerStatusSuit.prototype.gen_server_states = function (server_id, callback) {
    var self = this;
    send_msg_to_server(3, server_id, function (res) {
        // console.log(typeof(res));
        // console.log(res);
        res = filter_color_char(res);
        // console.log(res);
        var server_states = {};
        for (idx in self.LOCAL_SERVER) {
            var server_name = self.LOCAL_SERVER[idx];
            //var offset = res.search(server_name);
            var offset = self.match_whole_word(res, server_name, 0, false, true);
            if (offset == -1) {
                server_states[server_name] = self.SERVER_STATE.UNKNOWN;
                continue;
            }
            var linefeed_offset = res.indexOf('\n', offset);
            var slice_str = res.slice(offset, linefeed_offset);
            if (slice_str.indexOf(self.WORKING_DESCRIPTION) != -1) {
                server_states[server_name] = self.SERVER_STATE.RUNNING;
            }
            else if (slice_str.indexOf(self.NOT_WORKING_DESCRIPTION) != -1) {
                server_states[server_name] = self.SERVER_STATE.CLOSED;
            }
        }
        self.all_server_states[server_id] = server_states;
        if (callback !== undefined)
            callback.call(self, server_id);
    });
}

ServerStatusSuit.prototype.refresh_last_server_action = function (server_id) {

}

/*
 * @return SHOW_STATE
 */
ServerStatusSuit.prototype.get_display_state = function (server_id) {
    var action = Number(this.last_server_action[server_id]);
    switch (action) {
        case this.ACTION.START_BEGIN:
            return this.SHOW_STATE.STARTING;
        case this.ACTION.SHUT_BEGIN:
            return this.SHOW_STATE.SHUTTING;
        case this.ACTION.RESTART_SHUT_BEGIN:
            return this.SHOW_STATE.RESTART_SHUTTING;
        case this.ACTION.RESTART_START_BEGIN:
            return this.SHOW_STATE.RESTART_STARTING;
        case this.ACTION.START_END:
        case this.ACTION.RESTART_START_END:
            {
                var server_states = this.all_server_states[server_id];
                for (server_name in server_states) {
                    if (server_states[server_name] != this.SERVER_STATE.RUNNING) {
                        return this.SHOW_STATE.DOWN;
                    }
                }
                return this.SHOW_STATE.RUNNING;
            }
            break;
        case this.ACTION.SHUT_END:
        case this.ACTION.RESTART_SHUT_END:
            {
                var server_states = this.all_server_states[server_id];
                for (server_name in server_states) {
                    if (server_states[server_name] != this.SERVER_STATE.CLOSED) {
                        return this.SHOW_STATE.CANTBECLOSED;
                    }
                }
                return this.SHOW_STATE.CLOSED;
            }
            break;
        default:
            return this.SHOW_STATE.NO_DATA;
    }
}

ServerStatusSuit.prototype.start_interval_check = function () {
    if (this.timer_id !== 0) {
        return;
    }
    var self = this;
    this.timer_id = setInterval('ServerStatusSuit.timer_function(self)', this.check_interval);
    ServerStatusSuit.timer_function(self);
}

ServerStatusSuit.prototype.stop_interval_check = function (server_id) {
    clearInterval(this.timer_id);
    this.timer_id = 0;
}

/*
* 执行操作：开启、关闭、重启服务器
*
*
*/
ServerStatusSuit.prototype.do_server_operation = function(oper) {
    var send_data = {
        'oper': oper,
        'server': this.server_checked,
        'user': 'admin',
    };
    console.log(send_data);
    var self = this;
    send_msg_to_server(2, send_data, function (resdata) {
        console.log('exec');
        console.log(resdata);
        for (idx in self.server_checked) {
            switch (oper) {
                case '1':
                    self.last_server_action[idx] = self.ACTION.START_END;
                    break;
                case '2':
                    self.last_server_action[idx] = self.ACTION.SHUT_END;
                    break;
                case '3':
                    self.last_server_action[idx] = self.ACTION.RESTART_START_END;
                    break;
            }
        }
    });
    //立即更新状态
    for (idx in this.server_checked) {
        switch (oper) {
            case '1':
                this.last_server_action[idx] = this.ACTION.START_BEGIN;
                break;
            case '2':
                this.last_server_action[idx] = this.ACTION.SHUT_BEGIN;
                break;
            case '3':
                this.last_server_action[idx] = this.ACTION.RESTART_SHUT_BEGIN;
                break;
        }
        this.refresh_server_display(idx);
    }
    renderForm();
}