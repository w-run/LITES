<?php
$errMsg = "Unknow Error";
$errCode = "-1";
if (array_key_exists("state", $_GET)) {
    $errMsg = ["1" => "Request Error", "3" => "App Error", "4" => "Not found", "5" => "Server Error"][$_GET['state']];
    $errCode = ["1" => 101, "3" => 306, "4" => 104, "5" => 300][$_GET['state']];
}
header("ErrCode: " . $errCode);
header("ErrMsg: " . strtolower($errMsg));
?>
<html>
<head>
    <title>ERROR | LITES</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta name="renderer" content="webkit"/>
    <meta name="force-rendering" content="webkit"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        * {
            user-select: none;
        }

        body {
            background: #f6f6f6
        }

        h1 {
            text-align: center;
            padding: 30vh 0 20px;
            color: #555;
        }

        p {
            text-align: center;
            color: #ccc;
            font-weight: 300;
        }
    </style>
</head>
<body style="">
<h1>ERROR: <?php echo $errMsg ?></h1>
<p>LITES Framework</p>
</body>
</html>