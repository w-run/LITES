<?php

namespace core\lib;


class IMAP
{

    private $server = '';
    private $username = '';
    private $password = '';
    private $conn = null;
    private $email = '';


    public function __construct($username, $password, $email_address, $mail_server, $server_type, $port, $ssl = false)
    {
        if ($server_type == 'imap') {
            if ($port == '') $port = '143';
            $str_connect = '{' . $mail_server . '/imap:' . $port . '}INBOX';
        } else {
            if ($port == '') $port = '110';
            $str_connect = '{' . $mail_server . ':' . $port . '/pop3' . ($ssl ? "/ssl" : "") . '}INBOX';
        }
        $this->server = $str_connect;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email_address;
    }


    public function connect()
    {
        $this->conn = @imap_open($this->server, $this->username, $this->password, 0);
        if (!$this->conn) {
            echo "Error: Connecting to mail server. ";
            echo $this->server;
            exit;
        }
    }


    public function get_mail_total()
    {
        if (!$this->conn) return false;
        $tmp = imap_num_msg($this->conn);
        return is_numeric($tmp) ? $tmp : false;
    }


    public function get_imap_header($mid)
    {
        return imap_headerinfo($this->conn, $mid);
    }


    public function get_header_info($mail_header)
    {
        $sender = $mail_header->from[0];
        $sender_replyto = $mail_header->reply_to[0];
        if (strtolower($sender->mailbox) != 'mailer-daemon' && strtolower($sender->mailbox) != 'postmaster') {
            $mail_details = array(
                'from' => strtolower($sender->mailbox) . '@' . $sender->host,
                'fromName' => $this->_decode_GBK($sender->personal),
                'toOth' => strtolower($sender_replyto->mailbox) . '@' . $sender_replyto->host,
                'toNameOth' => $this->_decode_GBK($sender_replyto->personal),
                'subject' => $this->_decode_GBK($mail_header->subject),
                'to' => strtolower($this->_decode_mime_str($mail_header->toaddress))
            );
        }
        return $mail_details;
    }


    public function get_body($mid)
    {
        $body = imap_fetchbody($this->conn, $mid, 1);
        $encoding = imap_fetchstructure($this->conn, $mid);


        if (!isset($encoding->parts)) {
            if ($encoding->encoding == 3) {
                return base64_decode($body);
            }
        } else {
            $code = 3;
            $param = strtolower($encoding->parameters[0]->value);
            $type = 0;

            foreach ($encoding->parts as $part) {
                if ($part->encoding == 0) {
                    foreach ($part->parts as $pa) {
                        if ($pa->encoding == 4) {
                            $code = 4;
                        }
                    }
                }
                if ($part->encoding == 4) {
                    $code = 4;
                }
                if ($part->type == 5) {
                    $type = 5;
                }
                if ($part->type == 3) {
                    $type = 3;
                }
            }

            if ($type == 5) {
                $start = strripos($body, 'base64');
                $end = strripos($body, '------');
                $body = substr($body, $start, $end);
                $end = strpos($body, '------');
                $body = substr($body, 6, $end - 6);
                $body = base64_decode($body);
                if (mb_detect_encoding($body, 'GBK')) {
                    $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                }
                return $body;
            }

            if ($type == 3) {
                $start = strpos($body, 'text/html');
                $body = substr($body, $start);
                $start = strpos($body, 'base64');
                $body = substr($body, $start + 6);
                $end = strpos($body, '------');
                $body = substr($body, 0, $end);
                $body = base64_decode($body);
                if (mb_detect_encoding($body, 'GBK')) {
                    $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                }
                return $body;
            }
            if ($code == 3) {

                if (!strpos($param, 'part') && !strpos($param, 'nextpart')) {
                    $body = imap_fetchbody($this->conn, $mid, 2);
                    return base64_decode($body);
                }
                if (strpos($param, 'nextpart') || strpos($param, 'part')) {
                    $body = imap_fetchbody($this->conn, $mid, 2);
                    $body = base64_decode($body);
                    if (mb_detect_encoding($body, 'GBK')) {
                        $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                    }
                    return $body;
                }
                $body = base64_decode($body);
                if (mb_detect_encoding($body, 'GBK')) {
                    $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
                }
                return $body;
            }
            if ($code == 4) {
                if (!strpos($param, 'part') && !strpos($param, 'nextpart')) {
                    $body = imap_fetchbody($this->conn, $mid, 2);
                    return imap_qprint($body);
                }
                return imap_qprint($body);
            }

        }


        return $body;

    }


    public function mark_mail_read($mid)
    {
        return imap_setflag_full($this->conn, $mid, '\\Seen');
    }


    public function mark_mail_un_read($mid)
    {
        return imap_clearflag_full($this->conn, $mid, '\\Seen');
    }


    public function is_unread($headerinfo)
    {
        if (($headerinfo->Unseen == 'U') || ($headerinfo->Recent == 'N')) return true;
        return false;
    }


    public function delete_mail($mid)
    {
        if (!$this->conn) return false;
        return imap_delete($this->conn, $mid, 0);
    }


    public function get_date($mid)
    {
        return strtotime($this->get_imap_header($mid)->MailDate);
    }


    public function close_mailbox()
    {
        if (!$this->conn) return false;
        imap_close($this->conn, CL_EXPUNGE);
    }


    public function __destruct()
    {
        $this->close_mailbox();
    }


    private function _decode_GBK($string)
    {
        $newString = '';
        $string = str_replace('=?GBK?B?', '', $string);
        $newString = base64_decode($string);
        return $newString;

    }

    private function _decode_mime_str($string, $charset = "UTF-8")
    {
        $newString = '';
        $elements = imap_mime_header_decode($string);
        for ($i = 0; $i < count($elements); $i++) {
            if ($elements[$i]->charset == 'default') $elements[$i]->charset = 'iso-8859-1';
            $newString .= iconv($elements[$i]->charset, $charset, $elements[$i]->text);
        }
        return $newString;
    }

}