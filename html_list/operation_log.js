function OperationLogTab(id, layui) {
    this.id = id;
    this.layui = layui;
    this.server_group_data = {};
    this.period_select = {};
    this.col = [];
    this.type = new Set();
    this.period_select = {};
}

OperationLogTab.prototype.addCol = function (col, name, func) {
    this.col.push([col, name, func]);
}

OperationLogTab.prototype.RegType = function (type) {
    this.type.add(type);
}

OperationLogTab.prototype.genID = function (id) {
    return this.id + '_' + id;
}

OperationLogTab.prototype.genLabel = function (id) {
    return '#' + this.genID(id);
}

OperationLogTab.prototype.resetSelectFormOfOperationServer = function () {
    $(this.genLabel('operation_server')).empty();
    $(this.genLabel('operation_server')).append('<option value="">请选择服务器</option>');
}

OperationLogTab.prototype.initTab = function () {
    var label = '#' + this.id;
    $(label).append('\
    <div class="layui-fluid">\
    <form class="layui-form" action="">\
      <div class="layui-row">\
        <div class="layui-col-lg1">\
          <input type="radio" name="' + this.genID('query_choice') + '" value="group" title="服务器组" checked>\
        </div>\
        <div class="layui-col-lg3">\
          <select name="' + this.genID('operation_group') + '" id="' + this.genID('operation_group') + '" lay-filter="' + this.genID('operation_group') + '">\
            <option value="">请选择服务器组</option>\
          </select>\
        </div>\
        <div class="layui-col-lg1  layui-col-md-offset1">\
          <input type="radio" name="' + this.genID('query_choice') + '" value="single" title="服务器名称">\
        </div>\
        <div class="layui-col-lg3">\
          <select name="' + this.genID('operation_server') + '" id="' + this.genID('operation_server') + '">\
            <option value="">请选择服务器</option>\
          </select>\
        </div>\
      </div>\
      <div class="layui-row">\
      </div>\
      <div class="layui-row">\
        <div class="layui-col-lg1 layui-col-md-offset2">\
          <label class="layui-form-label">日期范围</label>\
        </div>\
        <div class="layui-col-lg3">\
          <input type="text" class="layui-input" id="' + this.genID('date') + '">\
        </div>\
        <div class="layui-col-lg1 layui-col-md-offset2">\
          <div class="layui-form-item">\
            <button class="layui-btn" lay-submit id="' + this.genID('query') + '" lay-filter="' + this.genID('query') + '">查询</button>\
          </div>\
        </div>\
      </div>\
    </form>\
    <table class="layui-table", page=true, lay-filter="' + this.genID('layuitable') + '">\
    <thead id=' + this.genID('log-col') + '>\
    </thead>\
    <tbody id=' + this.genID('log-table') + '>\
    </tbody>\
    </table>\
    </div>');
    var self = this;
    this.layui.use(['form', 'laydate', 'table'], function () {
        var laydate = self.layui.laydate;
        var form = self.layui.form;
        var table = self.layui.table;
        //
        // table.render({
        //     elem: self.genLabel('layuitable'),
        //     page: true,
        // });

        //执行一个laydate实例
        laydate.render({
            elem: self.genLabel('date'),
            type: 'datetime',
            range: '~',
            done: function (value, date, endDate) {
                self.period_select['start'] = date;
                self.period_select['end'] = endDate;
                console.log(value); //得到日期生成的值，如：2017-08-18
                // console.log(date); //得到日期时间对象：{year: 2017, month: 8, date: 18, hours: 0, minutes: 0, seconds: 0}
                // console.log(endDate); //得结束的日期时间对象，开启范围选择（range: true）才会返回。对象成员同上。
            },
        });

        //监听服务器组的select事件
        form.on('select(' + self.genID('operation_group') + ')', function (data) {
            console.log(data);
            var start_id = self.server_group_data[data.value]['group_start_id'];
            var end_id = self.server_group_data[data.value]['group_end_id'];
            var send_data = {
                'start_id': start_id,
                'end_id': end_id,
                server_type : 'server'
            };
            send_msg_to_server(1, send_data, function (res) {
                console.log(res);
                console.log(send_data);
                self.resetSelectFormOfOperationServer();
                for (idx in res) {
                    if (res[idx]['ip'] !== null) {
                        $(self.genLabel('operation_server')).append("<option value='" + res[idx]['server_id'] + "'>" + res[idx]['server_name'] + "</option>");
                    }
                }
                renderForm();
            });
        });

        //监听【查询】按钮
        form.on('submit(' + self.genID('query') + ')', function (data) {
            var date = $(self.genLabel('date')).val();
            console.log(data);
            var send_data = {
                server_group: data.field[self.genID('operation_group')],
                server_group_data: self.server_group_data,
                server_id: data.field[self.genID('operation_server')],
                query_type: data.field[self.genID('query_choice')],
                date: date,
            }
            if (send_data.query_type == 'group') {
                if (send_data.server_group == '') {
                    alert('请选择服务器组!');
                    return false;
                }
            }
            else {
                if (send_data.server_id == '') {
                    alert('请选择服务器名称!');
                    return false;
                }
            }
            if (send_data.date == '') {
                alert('请输入时间范围!');
                return false;
            }
            console.log(send_data);
            send_msg_to_server(5, send_data, function (result) {
                console.log(result);
                $(self.genLabel('log-col')).empty();
                $(self.genLabel('log-col')).append('<tr id=' + self.genID('thead') + '></tr>');
                var head_label = self.genLabel('thead');
                for (idx in self.col) {
                    $(head_label).append('<th lay-data="{sort:true, field:\'' + self.col[idx][1]  + '\'}">' + self.col[idx][1] + '</th>');
                }

                var res = result['data'];
                var group_name = result['group'];
                $(self.genLabel('log-table')).empty();
                for (idx in res) {
                    res[idx]['group_name'] = group_name;
                    if (!self.type.has(res[idx]['operation_type'])) {
                        continue;
                    }
                    var str = 'data' + idx;
                    var tr_xml = '<tr id=' + self.genID(str) + '></tr>';
                    $(self.genLabel('log-table')).append(tr_xml);
                    var label = self.genLabel(str);
                    for (num in self.col) {
                        var data = res[idx][self.col[num][0]];
                        if (self.col[num][2] !== undefined) {
                            data = self.col[num][2](data);
                        }
                        $(label).append('<td>' + data + '</td>');
                    }
                }
                //转换静态表格
                table.init(self.genID('layuitable'), {
                    limit: 10,
                    page: true
                });
                renderForm();
            });
            return false;
        });
    });

    //填充服务器组select栏数据
    var data = [{
        'table_name': 'server_group',
    }];

    get_need_info_and_callback_it('sg_gm', data, function (res) {
        for (idx in res) {
            $(self.genLabel('operation_group')).append("<option value='" + res[idx]['group_name'] + "'>" + res[idx]['group_name'] + "</option>");
            self.server_group_data[res[idx]['group_name']] = res[idx];
        }
        renderForm();
    });


}

