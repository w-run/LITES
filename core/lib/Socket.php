<?php

namespace core\lib;


class Socket
{
    private $log;
    private $port;
    private $address;
    private $master;
    private $sockets = array();
    private $client = array();

    public function __construct($address, $port)
    {
        $this->log = false;
        $this->port = $port;
        $this->address = $address;
        $this->master = $this->WebSocket();
        $this->sockets[] = $this->master;
        $this->run();
    }


    private function WebSocket()
    {
        $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() failed");
        socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1) or die("socket_option() failed");
        socket_bind($server, $this->address, $this->port) or die("socket_bind() failed");
        socket_listen($server, 100) or die("socket_listen() failed");
        return $server;
    }


    private function run()
    {
        while (true) {
            $changes = $this->sockets;

            //@socket_select($changes, $write = NULL, $except = NULL, NUll );
            if (@socket_select($changes, $write = NULL, $except = NULL, 0) < 1) {
                continue;
            }
            foreach ($changes as $socket) {
                //连接主机的 client
                if ($socket == $this->master) {
                    $client = socket_accept($this->master);
                    if ($client < 0) {
                        $this->log("socket_accept() failed");
                        continue;
                    } else {
                        $this->sockets[] = $client;
                        socket_getpeername($client, $addr, $por);
                        $this->client[] = array('socket' => $client, 'ip' => $addr, 'hand' => false, 'type' => false, 'status' => false);
                        $this->log("连接到客户端： " . $client);
                    }
                } else {
                    $bytes = @socket_recv($socket, $buffer, 4096, 0);
                    $opcode = ord($buffer[0]) & 15;
                    if ($opcode == 8) {
                        $this->close($socket);
                        $this->log('close :' . $socket);
                        continue;
                    }
                    $key = $this->getClientKey($socket);
                    if ($this->client[$key]['hand'] === false) {
                        $this->doHandShake($socket, $buffer);
                        $this->client[$key]['hand'] = true;
                        $this->log("握手成功！");
                    } else if ($this->client[$key]['type'] === false) {
                        $this->setSendType($socket, $key, $buffer);
                    } else if ($this->client[$key]['type'] == 1) {
                        $this->systemSend($socket, $buffer);
                    } else if ($this->client[$key]['type'] == 2) {
                        $this->massSend($socket, $key, $buffer);
                    }
                    unset($buffer);
                }
            }
        }
    }


    private function getClientKey($socket)
    {
        foreach ($this->client as $k => $v) {
            if ($this->client[$k]['socket'] == $socket) {
                return $k;
            }
        }
        return false;
    }

    private function getSocketKey($socket)
    {
        foreach ($this->sockets as $k => $v) {
            if ($this->sockets[$k] == $socket) {
                return $k;
            }
        }
        return false;
    }


    private function getKey($req)
    {
        $key = null;
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $req, $match)) {
            $key = $match[1];
        }
        return $key;
    }


    private function encry($req)
    {
        $key = $this->getKey($req);
        $mask = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        return base64_encode(sha1($key . $mask, true));
    }


    private function doHandShake($socket, $req)
    {

        $acceptKey = $this->encry($req);
        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: " . $acceptKey . "\r\n" .
            "\r\n";
        $this->socketWrite($socket, $upgrade);
    }


    private function decode($buffer)
    {
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }


    private function frame($msg, $type = 'utf8')
    {
        if ($msg === false) {
            return '';
        }
        $length = strlen($msg);
        if ($length <= 125) {
            return "\x81" . chr($length) . $msg;
        } else if ($length <= 65535) {

            return "\x81" . chr(126) . pack("n", $length) . $msg;
        } else if ($length <= 4294967295) {

            return "\x81" . chr(127) . pack("xxxxN", $length) . $msg;
        } else {

            $result = $this->splitZh($msg, 4294967295, $type);
            return "\x81" . chr(127) . pack("xxxxN", 4294967295) . $result[0] . $this->frame($result[1], $type);
        }
    }


    private function splitZh($str, $byte = 125, $type = 'utf8')
    {
        $result = array();
        if ($type == 'utf8') {
            $len_byte = 3;
            $len_zh = mb_strlen($str, 'utf8');
        } else if ($type == 'gbk') {
            $len_byte = 2;
            $len_zh = mb_strlen($str, 'gbk');
        } else if ($type == 'gb2312') {
            $len_byte = 2;
            $len_zh = mb_strlen($str, 'gb2312');
        }
        for ($i = 0, $start = 0, $end = 0; $i < $len_zh; $i++) {
            $len = $end - $start;
            if (!preg_match("/^[\x7f-\xff]+$/", $str[$end])) { //兼容gb2312,utf-8

                if ($len + 1 > $byte) {
                    $result[] = substr($str, $start, $len);
                    $result[] = substr($str, $end);
                    return $result;
                }
                $end++;
            } else {

                if ($len + $len_byte > $byte) {
                    $result[] = substr($str, $start, $len);
                    $result[] = substr($str, $end);
                    return $result;
                }
                $end += $len_byte;
            }
            if ($i == $len_zh - 1 && $start < strlen($str)) {
                $result[] = substr($str, $start);
                $result[] = false;
            }
        }
        return $result;
    }


    private function sliceFrame($msg, $byte = 125, $type = 'utf8')
    {
        $a = $this->arr_split_zh($msg, $byte, $type);
        if (count($a) == 1) {
            return "\x81" . chr(strlen($a[0])) . $a[0];
        }
        $ns = "";
        foreach ($a as $o) {
            $ns .= "\x81" . chr(strlen($o)) . $o;
        }
        return $ns;
    }


    private function arr_split_zh($str, $byte = 125, $type = 'utf8')
    {
        $result = array();
        if ($type == 'utf8') {
            $len_byte = 3;
            $len_zh = mb_strlen($str, 'utf8');
        } else if ($type == 'gbk') {
            $len_byte = 2;
            $len_zh = mb_strlen($str, 'gbk');
        } else if ($type == 'gb2312') {
            $len_byte = 2;
            $len_zh = mb_strlen($str, 'gb2312');
        }
        for ($i = 0, $start = 0, $end = 0; $i < $len_zh; $i++) {
            $len = $end - $start;
            if (!preg_match("/^[\x7f-\xff]+$/", $str[$end])) { //兼容gb2312,utf-8

                if ($len + 1 > $byte) {
                    $result[] = substr($str, $start, $len);
                    $start = $end;
                }
                $end++;
            } else {

                if ($len + $len_byte > $byte) {
                    $result[] = substr($str, $start, $len);
                    $start = $end;
                }
                $end += $len_byte;
            }
            if ($i == $len_zh - 1 && $start < strlen($str)) {
                $result[] = substr($str, $start);
            }
        }
        return $result;
    }


    private function setSendType($Sender, $key, $buffer)
    {
        $ip = $this->client[$key]['ip'];
        if ($this->client[$key]['status'] === false) {
            $msg = '请先设置聊天模式  [1]系统机器人 [2]和其他人群聊';
            $this->client[$key]['status'] = true;
            $this->Send($Sender, $msg);
            $this->log(++$key . "号用户进入IP:" . $ip);
        } else {
            $type = $this->decode($buffer);
            $type = trim($type);
            if ($type == 1) {
                $msg = '系统机器人回复开启';
                $this->client[$key]['type'] = 1;
                $this->Send($Sender, $msg);
            } else if ($type == 2) {
                $msg = '群发给其他用户开启';
                $this->client[$key]['type'] = 2;
                $this->Send($Sender, $msg);
                $msg = ++$key . "号用户进入IP:" . $ip;
                $data['msg'] = $msg;
                $data['key'] = $key;

                $this->systemPushMsg($data, 1, 2, $Sender);

                $this->systemPushList($Sender, 2);
            } else {
                $msg = '聊天模式设置错误请重新设置 [1]系统机器人 [2]和其他人群聊';
                $this->Send($Sender, $msg);
            }
        }
    }


    private function Send($client, $msg)
    {
        $msg = $this->frame("系统消息: " . $msg);
        $this->socketWrite($client, $msg);
    }


    private function systemSend($client, $msg)
    {
        $msg = $this->frame("系统消息: " . $msg);
        $this->socketWrite($client, $msg);
    }


    private function massSend($Sender, $id, $msg)
    {
        $msg = $this->decode($msg);
        $msg = $this->frame(++$id . '号用户：' . $msg);
        foreach ($this->sockets as $k => $v) {
            $key = $this->getClientKey($v);
            if ($v != $this->master && $v != $Sender && $this->client[$key]['hand'] !== false && $this->client[$key]['type'] == 2) {
                $this->socketWrite($v, $msg);
            }
        }
    }

    private function systemPushMsg($data, $msgType, $clientType, $socket = false)
    {
        $msg['data'] = $data;
        $msg['type'] = $msgType;
        $msg = json_encode($msg);
        $msg = $this->frame($msg);
        foreach ($this->sockets as $k => $v) {
            $key = $this->getClientKey($v);
            if ($v != $this->master && $v != $socket && $this->client[$key]['hand'] !== false && $this->client[$key]['type'] == $clientType) {
                $this->socketWrite($v, $msg);
            }
        }
    }


    private function systemPushList($Sender, $clientType)
    {
        foreach ($this->sockets as $k => $v) {
            $key = $this->getClientKey($v);
            if ($v != $this->master && $this->client[$key]['hand'] !== false && $this->client[$key]['type'] == $clientType) {
                $data['key'] = $key + 1;
                $data['msg'] = $data['key'] . '号用户进入IP:' . $this->client[$key]['ip'];
                $msg['data'][] = $data;
            }
        }
        $msg['type'] = 3;
        $msg = json_encode($msg);
        $msg = $this->frame($msg);
        $this->socketWrite($Sender, $msg);
    }


    private function close($socket)
    {
        $cKey = $this->getClientKey($socket);
        $sKey = $this->getSocketKey($socket);
        $key = $cKey + 1;
        $ip = $this->client[$cKey]['ip'];
        $type = $this->client[$cKey]['type'];
        socket_close($socket);
        unset($this->client[$cKey]);
        unset($this->sockets[$sKey]);
        $msg = $key . "号用户离开IP:" . $ip;
        $this->log($msg);

        if ($type == 2) {
            $data['msg'] = $msg;
            $data['key'] = $key;

            $this->systemPushMsg($data, 2, 2, $socket);
        }
    }

    function socketWrite($socket, $msg)
    {
        try {
            socket_write($socket, $msg, strlen($msg));
        } catch (Exception $e) {
            $this->close($socket);
        }
    }

    private function log($msg)
    {
        if ($this->log) {
            echo iconv('UTF-8', 'gbk', $msg);
            echo "\n";
        }
    }
}