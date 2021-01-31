var install = {
    get_mysql() {
        var callback = {
            host: install.$("#mysql_host").value,
            port: install.$("#mysql_port").value,
            usr: install.$("#mysql_usr").value,
            pwd: install.$("#mysql_pwd").value,
            lib: install.$("#mysql_lib").value
        }
        for (let i in callback)
            if (callback[i] == '') {
                install.msg('请仔细填写数据库配置')
                return null;
            }
        return callback;
    },
    get_system() {
        var callback = {
            name: install.$("#system_name").value,
            domain: install.$("#system_domain").value,
            allow_host: install.$("#system_client").value
        }
        for (let i in callback)
            if (callback[i] == '') {
                install.msg('请仔细填写站点配置')
                return null;
            }
        return callback;
    },
    test() {
        var mysql = install.get_mysql();
        if (mysql != null) {
            install.request({
                data: {
                    type: 'test',
                    mysql: JSON.stringify(mysql)
                },
                success(res) {
                    install.msg(res)
                }
            })
        }
    },
    install() {
        var mysql = install.get_mysql();
        var system = install.get_system();
        if (mysql != null && system != null) {
            install.request({
                data: {
                    type: 'install',
                    mysql: JSON.stringify(mysql),
                    system: JSON.stringify(system)
                },
                success(res) {
                    install.msg(res)
                    if (res == '安装成功') setTimeout(function () {
                        location.href = system.allow_host;
                    }, 2000)
                }
            })
        }
    },
    $(param) {
        return document.querySelector(param);
    },
    request(param) {
        url = location.href;
        var type = (typeof param.type == 'string') ? param.type.toUpperCase() : 'POST';
        param.data = param.data || [];
        var data = (function (value) {
            var dataStr = '';
            for (var key in value) {
                dataStr += encodeURIComponent(key) + "=" + encodeURIComponent(value[key]) + "&";
            }
            ;
            return dataStr.substring(0, dataStr.length - 1);
        }(param.data));
        var success = (typeof param.success == 'function') ? param.success : function () {
        };
        var error = (typeof param.error == 'function') ? param.error : function (res) {
            install.msg("request error");
        };
        var ajax = new XMLHttpRequest();
        ajax.withCredentials = true;
        ajax.open(type, url, true);
        if (type.toUpperCase() == 'POST')
            ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        for (let k in param.header)
            ajax.setRequestHeader(k, param.header[k]);
        ajax.onreadystatechange = function () {
            if (ajax.readyState == 4) {
                if (ajax.status == 200 || ajax.status == 304) {
                    var result = ajax.responseText;
                    success.call(this, result);
                } else {
                    error.call(this, result);
                }
                ;
            }
            ;
        };

        ajax.send(data);
    },

    msg(str, is_alert = false) {
        if (is_alert)
            alert(str, "提示");
        else
            toast(str)
    }
}
document.addEventListener('DOMContentLoaded', function () {

    window.toast = function (inner, timeout = 2000) {
        if (document.getElementById("toast") == null) {
            var oDiv = document.createElement('div');
            oDiv.innerHTML = inner;
            oDiv.setAttribute("id", "toast");
            document.body.appendChild(oDiv);
            var vp = document.getElementById("toast");
            var i = -60;
            vttimer = setInterval(function () {
                if (i <= -10) {
                    vp.style.top = i + 'px';
                    i += 3;
                } else {
                    window.clearInterval(vttimer)
                }
            }, 8);
            if (timeout > 0)
                setTimeout(window.toast_hide, timeout);
        }
    }
    window.toast_hide = function () {
        if (document.getElementById("toast") != null) {
            var vp = document.getElementById("toast");
            if (vp != null) {
                var i = -10;
                vdtimer = setInterval(function () {
                    if (document.getElementById("toast") == null) {
                        window.clearInterval(vdtimer);
                    }
                    if (i >= -60) {
                        vp.style.top = i + 'px';
                        i -= 3;
                    } else {
                        document.body.removeChild(document.getElementById("toast"));
                        window.clearInterval(vdtimer);
                    }
                }, 10);
            }
        }
    }

    window.alert = function (msg, callback = function () {
    }, showCanel = false) {
        var div = document.createElement("div");
        div.innerHTML = "<div id=\"dialogs2\" style=\"display: none\">" +
            "<div class=\"alertMask\"></div>" +
            "<div class=\"alert\">" +
            "	<div class=\"alertHd\">" +
            "		<strong class=\"alertTitle\"></strong>" +
            "	</div>" +
            "	<div class=\"alertBd\" id=\"dialog_msg2\">弹窗内容</div>" +
            "	<div class=\"alertHd\">" +
            "		<strong class=\"alertTitle\"></strong>" +
            "	</div>" +
            "	<div class=\"alertFt\">" +
            (showCanel ? "		<a href=\"javascript:;\" class=\"alertBtn alertBtnCancel\" id=\"dialog_cancel2\">取消</a>" : "") +
            "		<a href=\"javascript:;\" class=\"alertBtn alertBtnPrimary\" id=\"dialog_ok2\">确定</a>" +
            "	</div></div></div>";
        document.body.appendChild(div);
        var dialogs2 = document.getElementById("dialogs2");
        dialogs2.style.display = 'block';
        var dialog_msg2 = document.getElementById("dialog_msg2");
        dialog_msg2.innerHTML = msg;
        if (showCanel)
            var dialog_cancel2 = document.getElementById("dialog_cancel2");
        var dialog_ok2 = document.getElementById("dialog_ok2");
        dialog_ok2.onclick = function () {
            dialogs2.style.display = 'none';
            callback('ok');
            document.body.removeChild(div);
        };
        if (showCanel)
            dialog_cancel2.onclick = function () {
                dialogs2.style.display = 'none';
                callback('cancel');
                document.body.removeChild(div);
            };
    };
});
