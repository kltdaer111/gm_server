function ServerStatusSuit(dom_id, server_data, group_name, check_interval, layui) {
    this.dom_id = dom_id;
    this.server_data = server_data;
    this.group_name = group_name;
    //最近一次的服务器行为
    this.last_server_action = {};
    //最近一次的服务器更新操作
    this.last_server_update = {};
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
    this.layui = layui;
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
    UPDATING: '更新中',
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

ServerStatusSuit.prototype.UPDATE = {
    UPDATE_BEGIN: 1,
    UPDATE_END: 2,
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
                <th>操作结果</th>\
              </tr>\
            </thead>\
            <tbody id="' + main_id + '">\
            </tbody>');
    var res = this.server_data;
    //console.log(res);
    for (idx in res) {
        var tr_xml = '<tr id=' + res[idx]['server_id'] + '>';
        $(main_label).append(tr_xml);

        var button_xml = '<td><form class="layui-form" action=""><div class="layui-form-item"><input type="checkbox" lay-filter="checkbox_filter_' + this.dom_id + '"';
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
        $(main_label).append('<td id=' + this.dom_id + '_result_' + res[idx]['server_id'] + '></td>');
        $(main_label).append('</tr>');
    }
    this.reg_checkbox_event();
    renderForm();
}


ServerStatusSuit.prototype.timer_function = function () {
    for (idx in this.effect_server_id) {
        this.get_new_state_and_refresh_display(this.effect_server_id[idx]);
    }
}

ServerStatusSuit.prototype.get_new_state_and_refresh_display = function (server_id) {
    this.gen_server_states(server_id, this.refresh_server_display);
}

ServerStatusSuit.prototype.refresh_server_display = function (server_id) {
    var new_state = this.get_display_state(server_id);
    // console.log(this.all_server_states[server_id]);
    // console.log(server_id + ':' + new_state);
    var label = '#' + this.dom_id + '_state_' + server_id;
    // console.log(label);
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

ServerStatusSuit.prototype.judge_state_by_last_action_and_serverstate = function (action, server_states) {
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

/*
 * @return SHOW_STATE
 */
ServerStatusSuit.prototype.get_display_state = function (server_id) {
    var action = Number(this.last_server_action[server_id]);
    var update = Number(this.last_server_update[server_id]);
    if (update == this.UPDATE.UPDATE_BEGIN) {
        return this.SHOW_STATE.UPDATING;
    }
    var server_states = this.all_server_states[server_id];
    return this.judge_state_by_last_action_and_serverstate(action, server_states);
}

ServerStatusSuit.prototype.start_interval_check = function () {
    if (this.timer_id !== 0) {
        return;
    }
    var self = this;
    this.timer_id = setInterval(function () {
        self.timer_function();
    }, this.check_interval);
    this.timer_function();
}

ServerStatusSuit.prototype.stop_interval_check = function (server_id) {
    clearInterval(this.timer_id);
    this.timer_id = 0;
}

ServerStatusSuit.prototype.reg_checkbox_event = function () {
    //监听checkbox
    var self = this;
    var filter = 'checkbox(checkbox_filter_' + this.dom_id + ')';
    this.layui.use(['form'], function () {
        var form = self.layui.form;
        form.on(filter, function (data) {
            console.log(data);
            if (data.elem.checked) {
                self.server_checked[data.value] = 1;
            }
            else {
                delete self.server_checked[data.value];
            }
        });
    });
}

ServerStatusSuit.prototype.set_operation_result = function (id, res) {
    var label = '#' + this.dom_id + '_result_' + id;
    $(label).text(res);
}

ServerStatusSuit.prototype.check_success_by_output = function (output) {
    if (output.search(/error/i) >= 0) {
        return false;
    }
    if (output.indexOf('错误') >= 0) {
        return false;
    }
    if (output.indexOf('失败') >= 0) {
        return false;
    }
    return true;
}

/*
* 执行操作：开启、关闭、重启服务器
*
*
*/
ServerStatusSuit.prototype.do_server_operation = function (oper, data) {
    var send_data = {
        oper: oper,
        server: this.server_checked,
        user: 'admin',
        para1: '',
        para2: '',
    };
    console.log(send_data);
    //立即更新状态
    var alert_time = 0;
    for (id in this.server_checked) {
        switch (oper) {
            case '开启服务器':
                this.last_server_action[id] = this.ACTION.START_BEGIN;
                break;
            case '关闭服务器':
                this.last_server_action[id] = this.ACTION.SHUT_BEGIN;
                break;
            case '重启服务器':
                this.last_server_action[id] = this.ACTION.RESTART_SHUT_BEGIN;
                break;
            case '关服更新':
                if (this.get_display_state(id) !== this.SHOW_STATE.CLOSED) {
                    if (alert_time < 1) {
                        alert_time += 1;
                        var r = confirm("你确定要关闭服务器并更新吗?");
                        if (r != true) {
                            return;
                        }
                    }
                }
                if (data.field.tab_copy_choose_source == "") {
                    alert('请选择拷贝源!');
                    return;
                }
                send_data.para1 = data.field.tab_copy_choose_source;
                if (data.field.tab_copy_choose_version == "") {
                    alert('请选择版本!');
                    return;
                }
                console.log('goon');
                send_data.para2 = data.field.tab_copy_choose_version;
                this.last_server_update[id] = this.UPDATE.UPDATE_BEGIN;
                break;
            case '不关服更新':

                if (data.field.tab_copy_choose_source == "") {
                    alert('请选择拷贝源!');
                    return;
                }
                send_data.para1 = data.field.tab_copy_choose_source;
                if (data.field.tab_copy_choose_version == "") {
                    alert('请选择版本!');
                    return;
                }
                send_data.para2 = data.field.tab_copy_choose_version;
                this.last_server_update[id] = this.UPDATE.UPDATE_BEGIN;
                break;
        }
        this.refresh_server_display(id);
        this.set_operation_result(id, '等待中');
    }
    renderForm();
    var self = this;
    send_msg_to_server(2, send_data, function (resdata) {
        console.log('exec');
        console.log(resdata);
        //更新服务器状态、判断操作是否成功
        //TODO 把结果判断放到php里
        for (id in self.server_checked) {
            switch (oper) {
                case '开启服务器':
                    self.last_server_action[id] = self.ACTION.START_END;
                    self.gen_server_states(id, function (server_id) {
                        var server_state = self.all_server_states[server_id];
                        if (self.SHOW_STATE.RUNNING == self.judge_state_by_last_action_and_serverstate(self.ACTION.START_END, server_state)) {
                            self.set_operation_result(server_id, '成功');
                        }
                        else {
                            self.set_operation_result(server_id, '失败');
                        }
                        renderForm();
                    });
                    break;
                case '关闭服务器':
                    self.last_server_action[id] = self.ACTION.SHUT_END;
                    self.gen_server_states(id, function (server_id) {
                        var server_state = self.all_server_states[server_id];
                        if (self.SHOW_STATE.CLOSED == self.judge_state_by_last_action_and_serverstate(self.ACTION.SHUT_END, server_state)) {
                            self.set_operation_result(server_id, '成功');
                        }
                        else {
                            self.set_operation_result(server_id, '失败');
                        }
                        renderForm();
                    });
                    break;
                case '重启服务器':
                    self.last_server_action[id] = self.ACTION.RESTART_START_END;
                    self.gen_server_states(id, function (server_id) {
                        var server_state = self.all_server_states[server_id];
                        if (self.SHOW_STATE.RUNNING == self.judge_state_by_last_action_and_serverstate(self.ACTION.RESTART_START_END, server_state)) {
                            self.set_operation_result(server_id, '成功');
                        }
                        else {
                            self.set_operation_result(server_id, '失败');
                        }
                        renderForm();
                    });
                    break;
                case '关服更新':
                case '不关服更新':
                    self.last_server_update[id] = self.UPDATE.UPDATE_END;
                    if (resdata[id]['error_out'] != '') {
                        self.set_operation_result(id, '失败');
                    }
                    else if (self.check_success_by_output(resdata[id]['standard_out']) === false) {
                        self.set_operation_result(id, '失败');
                    }
                    else {
                        self.set_operation_result(id, '成功');
                    }
                    renderForm();
                    break;
            }
        }
    });
}