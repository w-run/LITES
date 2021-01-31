<?php
error_reporting(0);
function install($system_config, $mysql_config)
{
    if (file_get_contents('../core/conf/install.lock') !== false)
        return "已执行过安装程序，重新安装请删除install.lock";
    $con = new mysqli(
        $mysql_config['host'],
        $mysql_config['usr'],
        $mysql_config['pwd'],
        null,
        $mysql_config['port']
    );
    $con->set_charset("utf8mb4");
    //检测是否成功
    if ($con->connect_error) {
        return $con->connect_error;
    }

    $db_exist = $con->select_db($mysql_config['lib']);
    if (!$db_exist) {
        $sql = "create database " . $mysql_config['lib'] . " default character set = 'utf8mb4' ";
        if ($con->query($sql) === TRUE) {
            $con->select_db($mysql_config['lib']);
        } else {
            return $con->error;
        }
    }

    $_sql = file_get_contents('lites.sql');
    $_arr = explode(';', $_sql);
    foreach ($_arr as $_value) {
        if ($_value != "") {
            if ($con->query($_value . ';') === FALSE) {
                return $con->error;
            }
        }
    }
    $con->close();
    $con = null;

    $system_config['version'] = 'V1.0.0';

    $content = json_encode([
        'system' => $system_config,
        'mysql' => $mysql_config
    ]);

    $file = fopen('../core/conf/config.json', "w");
    fwrite($file, $content);
    fclose($file);
    $lock = fopen('../core/conf/install.lock', "w");
    fwrite($lock, 'installed');
    fclose($lock);

    return '安装成功';
}

function test($mysql_config)
{
    if (file_get_contents('../core/conf/install.lock') !== false)
        return "已执行过安装程序，重新安装请删除install.lock";
    $con = new mysqli(
        $mysql_config['host'],
        $mysql_config['usr'],
        $mysql_config['pwd'],
        null,
        $mysql_config['port']
    );
    $con->set_charset("utf8mb4");
    //检测是否成功
    if ($con->connect_error) {
        return "连接失败（" . $con->connect_error . "）";
    }
    if ($con->select_db($mysql_config['lib'])) {
        return "连接成功（数据库名已存在，继续安装可能会覆盖表数据）";
    } else {
        return "连接成功（将创建新数据库）";
    }
}

if (count($_POST) != 0) {
    $callback = "未知状态";
    if ($_POST['type'] == 'test') {
        $callback = test(json_decode($_POST['mysql'], true));
    } else if ($_POST['type'] == 'install') {
        $callback = install(json_decode($_POST['system'], true), json_decode($_POST['mysql'], true));
    }
    die($callback);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit"/>
    <meta name="force-rendering" content="webkit"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <link rel="shortcut icon" href="../res/favicon.ico" type="image/x-icon"/>
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>安装 | LITES </title>
    <link rel="stylesheet" href="css/style.css"/>
    <script language="JavaScript" src="js/install.js"></script>
    <script>
    </script>
</head>
<body class="home">
<h1 class="logo icon-L"></h1>
<section class="install">
    <h2>站点配置</h2>
    <div class="input-box">
        <label for="system_name">系统名称</label>
        <input type="text" id="system_name" value="LITES" placeholder="系统名称"/>
    </div>
    <div class="input-box">
        <label for="system_domain">域名</label>
        <input type="text" id="system_domain" value="lite.cn" placeholder="域名"/>
    </div>
    <div class="input-box">
        <label for="system_domain">Client</label>
        <input type="text" id="system_client" value="http://127.0.0.1/" placeholder="默认客户端地址，防跨域阻止"/>
    </div>
    <h2>数据库配置</h2>
    <div class="input-box">
        <label for="mysql_host">主机</label>
        <input type="text" id="mysql_host" value="localhost" placeholder="MySQL数据库主机地址"/>
        <input type="text" id="mysql_port" value="3306" placeholder="MySQL数据库端口号"/>
    </div>
    <div class="input-box">
        <label for="mysql_pwd">名称</label>
        <input type="text" id="mysql_lib" value="lites" placeholder="MySQL数据库连接的库名"/>
    </div>
    <div class="input-box">
        <label for="mysql_usr">用户名</label>
        <input type="text" id="mysql_usr" value="" placeholder="MySQL数据库用户名"/>
    </div>
    <div class="input-box">
        <label for="mysql_pwd">密码</label>
        <input type="password" id="mysql_pwd" value="" placeholder="MySQL数据库密码"/>
    </div>
    <div class="install-btns">
        <button type="button" onclick="install.test()">数据库测试</button>
        <button type="button" onclick="install.install()">安装</button>
    </div>
</section>
<footer></footer>
</body>
</html>

