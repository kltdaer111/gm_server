function page(json, type) {
    layui.use(['table', 'layer'], function () {
        var table = layui.table;
        var $ = layui.jquery;
        //console.log(json);
        if ('mark_table_edit' == type) {
            table.render({
                elem: "#mark_table",
                height: 500,
                width: '1200',
                cols: [
                    [ //标题栏
                        {
                            field: 'mark_type',
                            title: '<font size="3" face="楷体">渠道类型标记</font>',
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'platform_name',
                            title: '<font size="3" face="楷体">平台名称</font>',
                            width: 200,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'channel_name',
                            title: '<font size="3" face="楷体">渠道名称</font>',
                            width: 200,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'version',
                            title: '<font size="3" face="楷体">客户端最新版本号</font>',
                            width: 200,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'compatible_version',
                            title: '<font size="3" face="楷体">客户端兼容版本号</font>',
                            width: 200,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'right',
                            title: '操作',
                            align: 'center',
                            width: 100,
                            toolbar: "#mark_table_edit"
                        }
                    ]
                ],
                id: 'mark_list',
                data: json,
                page: true, //是否显示分页
                limit: 10 //每页默认显示的数量
            });
        } else if ('mark_account_table_edit' == type) {
            table.render({
                elem: "#mark_account_table",
                height: 500,
                width: '1000',
                cols: [
                    [ //标题栏
                        {
                            field: 'mark_type',
                            title: '<font size="3" face="楷体">渠道类型标记</font>',
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'server_name',
                            title: '<font size="3" face="楷体">服务器名称</font>',
                            width: 200,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'ip',
                            title: '<font size="3" face="楷体">服务器ip</font>',
                            width: 200,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'port',
                            title: '<font size="3" face="楷体">服务器port</font>',
                            width: 100,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'right',
                            title: '操作',
                            align: 'center',
                            width: 100,
                            toolbar: "#mark_account_table_edit"
                        }
                    ]
                ],
                id: 'mark_account_list',
                data: json,
                page: true, //是否显示分页
                limit: 10 //每页默认显示的数量
            });
        } else if ('mark_login_server_table_edit' == type) {
            table.render({
                elem: "#mark_login_server_table",
                height: 500,
                width: '1000',
                cols: [
                    [ //标题栏
                        {
                            field: 'mark_type',
                            title: '<font size="3" face="楷体">渠道类型标记</font>',
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'server_id',
                            title: '<font size="3" face="楷体">服务器id</font>',
                            width: 100,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'server_name',
                            title: '<font size="3" face="楷体">服务器名称</font>',
                            width: 250,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'status',
                            title: '<font size="3" face="楷体">服务器状态</font>',
                            width: 100,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'label',
                            title: '<font size="3" face="楷体">标签(新服/推荐)</font>',
                            width: 150,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'right',
                            title: '操作',
                            align: 'center',
                            width: 100,
                            toolbar: "#mark_login_server_table_edit"
                        }
                    ]
                ],
                id: 'mark_login_server_list',
                data: json,
                page: true, //是否显示分页
                limit: 10 //每页默认显示的数量
            });
        } else if ('mark_url_table_edit' == type) {
            table.render({
                elem: "#mark_bbs_url_table",
                height: 355,
                width: '1000',
                cols: [
                    [ //标题栏
                        {
                            field: 'mark_type',
                            title: '<font size="3" face="楷体">渠道类型标记</font>',
                            align: 'left',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'bbs_url',
                            title: '<font size="3" face="楷体">bbs_url</font>',
                            width: 500,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'right',
                            title: '操作',
                            align: 'center',
                            width: 100,
                            toolbar: "#mark_bbs_url_table_edit"
                        }
                    ]
                ],
                id: 'mark_bbs_url_list',
                data: json[0],
                page: true, //是否显示分页
                limit: 7 //每页默认显示的数量
            });

            table.render({
                elem: "#mark_plist_url_table",
                height: 355,
                width: '1000',
                cols: [
                    [ //标题栏
                        {
                            field: 'mark_type',
                            title: '<font size="3" face="楷体">渠道类型标记</font>',
                            align: 'left',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'plist_url',
                            title: '<font size="3" face="楷体">plist_url</font>',
                            width: 600,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'right',
                            title: '操作',
                            align: 'center',
                            width: 100,
                            toolbar: "#mark_plist_url_table_edit"
                        }
                    ]
                ],
                id: 'mark_plist_url_list',
                data: json[1],
                page: true, //是否显示分页
                limit: 7 //每页默认显示的数量
            });

            table.render({
                elem: "#mark_notice_url_table",
                height: 355,
                width: '1000',
                cols: [
                    [ //标题栏
                        {
                            field: 'mark_type',
                            title: '<font size="3" face="楷体">渠道类型标记</font>',
                            align: 'left',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'notice_url',
                            title: '<font size="3" face="楷体">公告url</font>',
                            width: 600,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'right',
                            title: '操作',
                            align: 'center',
                            width: 100,
                            toolbar: "#mark_notice_url_table_edit"
                        }
                    ]
                ],
                id: 'mark_notice_url_list',
                data: json[2],
                page: true, //是否显示分页
                limit: 7 //每页默认显示的数量
            });

            table.render({
                elem: "#mark_res_url_table",
                height: 355,
                width: '1000',
                cols: [
                    [ //标题栏
                        {
                            field: 'mark_type',
                            title: '<font size="3" face="楷体">渠道类型标记</font>',
                            align: 'left',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'res_url',
                            title: '<font size="3" face="楷体">资源url</font>',
                            width: 600,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'right',
                            title: '操作',
                            align: 'center',
                            width: 100,
                            toolbar: "#mark_res_url_table_edit"
                        }
                    ]
                ],
                id: 'mark_res_url_list',
                data: json[3],
                page: true, //是否显示分页
                limit: 7 //每页默认显示的数量
            });

            table.render({
                elem: "#mark_combat_url_table",
                height: 355,
                width: '1000',
                cols: [
                    [ //标题栏 
                        {
                            field: 'mark_type',
                            title: ' <font size = "3" face = "楷体" > 渠道类型标记 </font>',
                            align: 'left',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'combat_url',
                            title: '<font size="3" face="楷体"> combat_url </font>',
                            width: 600,
                            align: 'center',
                            style: 'background-color: #f2f2f2;'
                        }, {
                            field: 'right',
                            title: '操作',
                            align: 'center',
                            width: 100,
                            toolbar: "#mark_combat_url_table_edit"
                        }
                    ]
                ],
                id: 'mark_combat_url_list',
                data: json[4],
                page: true,  //是否显示分页 
                limit: 7 //每页默认显示的数量 
            });
        }

    });
}