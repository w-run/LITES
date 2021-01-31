<?php

namespace core\lib;


class SMTP
{
    public $charset = 'UTF-8';
    public $delimiter = PHP_EOL;
    public $name;
    public $error = array();
    public $log = array();

    protected $debug = FALSE;
    protected $authed = FALSE;
    protected $server = '';
    protected $port = 25;
    protected $account = '';
    protected $password = '';
    protected $from;
    protected $fp;
    protected $backup = FALSE;
    protected $to = array();
    protected $cc = array();
    protected $bc = array();
    protected $attachments = array();
    protected $ct = 'text/html';
    protected $boundary = '_LITES_5be64bf46hd540654fbac1d_';


    public function __construct($smtp_server, $from, $password = '', $debug = FALSE)
    {
        $this->debug = $debug;

        if (!strstr($from, '@')) {
            $this->error("{$from} is not a valid email address!");
        }

        $this->from = $from;
        $this->password = $password;

        if (strstr($from, ':')) {
            list($this->name, $this->from) = explode(':', $from);
        }

        if (preg_match('/:([0-9]{1,4}$)/', $smtp_server, $port)) {
            $port = $port[1];
            $smtp_server = str_replace(':' . $port, '', $smtp_server);
        } else {
            $port = 25;
        }
        $this->smtp_server = $smtp_server;
        $this->port = $port;

        $this->fp = @fsockopen($this->smtp_server, $this->port);

        if ($this->debug) {
            $this->log("telnet {$this->smtp_server} {$this->port}");
        }

        if (!$this->fp) {
            $this->error("Can't connect to {$this->smtp_server} with port:{$this->port}");
        }

    }


    public function auth($account = '', $password = '')
    {
        if ($this->error) {
            return $this->error;
        }

        stream_set_blocking($this->fp, TRUE);
        $msg = fgets($this->fp);
        $this->sendCommand("HELO LITES", '220,250');

        if ($password) {
            $this->sendCommand('AUTH LOGIN', '334');
            $this->sendCommand(base64_encode($account), '334');
            $this->sendCommand(base64_encode($password), '235');
        }

        $this->authed = TRUE;
    }


    public function sendCommand($command, $code = '')
    {
        fputs($this->fp, $command . $this->delimiter);
        $msg = fgets($this->fp, 512);

        if ($this->debug) {
            $this->log('SEND: ' . htmlspecialchars($command) . " ({$code})" . $this->delimiter . '<br>RESP: ' . htmlspecialchars($msg));
        }

        if ($code != '') {
            if ($msg) {
                $msg = substr($msg, 0, 3);
                if (!preg_match("/{$msg}/", $code)) {
                    $this->error("Error: {$command} (Return: {$msg})");
                }
            }
        }
    }


    public function changeContentType($type = 'text')
    {
        if ($type == 'text') {
            $this->ct = 'text/plain';
        } else {
            $this->ct = 'text/html';
        }
    }


    public function addRecipients($recipients, $method = 'to')
    {
        if (!in_array($method, array('to', 'cc', 'bc'))) {
            $method = 'to';
        }

        foreach ((array)$recipients as $recipient) {
            array_push($this->$method, $recipient);
        }
    }


    public function removeRecipients($recipients, $method = '')
    {
        if (!is_array($recipients)) {
            $recipients = (array)$recipients;
        }

        if (in_array($method, array('to', 'cc', 'bc'))) {
            $this->$method = array_diff($this->$method, $recipients);
        } else {
            $this->to = array_diff($this->to, $recipients);
            $this->cc = array_diff($this->cc, $recipients);
            $this->bc = array_diff($this->bc, $recipients);
        }
    }


    public function clearRecipients($method = '')
    {
        if (!$method && !in_array($method, array('to, bc, cc'))) {
            $this->to = array();
            $this->cc = array();
            $this->bc = array();
        } else {
            $this->$method = array();
        }
    }


    public function sendRecipients()
    {
        foreach (array('to', 'bc', 'cc') as $method) {
            if ($this->$method) {
                foreach ($this->$method as $recipient) {
                    $this->sendCommand("RCPT TO:<$recipient>");
                }
            }
        }
    }


    public function addAttachments($files, $stop = FALSE)
    {
        foreach ((array)$files as $file) {
            $name = '';
            if (strstr($file, ':')) {
                list($name, $file) = explode(':', $file);
            }
            if (file_exists($file)) {
                if (!$name) {
                    $name = basename($file);
                }
                $this->attachments[$name] = $file;
            } else {
                $error = "Error: attachment '$file' doesn't exists!";
                $this->log($error);
                if ($stop) {
                    throw new exception($error);
                }
            }
        }
    }


    public function removeAttachments($attachments)
    {
        foreach ((array)$attachments as $attachment) {
            if ($this->attachments[$attachment]) {
                unset($this->attachments[$attachment]);
            }
        }
    }


    public function clearAttachments()
    {
        $this->attachments = array();
    }


    public function getFileContents($file)
    {
        return file_get_contents($file);
    }


    public function changeBackup($state = '')
    {
        if ($state) {
            $this->backup = (bool)$state;
        } else {
            $this->backup = !$this->backup;
        }
        return $this->backup;
    }


    public function resetMail()
    {
        $this->ct = 'text/html';
        $this->backup = FALSE;
        $this->clearRecipients();
        $this->clearAttachments();
    }


    public function send($subject, $message, $to = '')
    {
        if (!$this->authed) {
            $this->auth($this->from, $this->password);
        }

        if ($to && !in_array($to, $this->to)) {
            array_unshift($this->to, $to);
        }

        if ($this->backup) {
            $this->addRecipients($this->from, 'bc');
        }

        if (!$this->to) {
            $this->error('No Recipient!');
        }

        if ($this->error) {
            return FALSE;
        }

        $delimiter =& $this->delimiter;

        $this->sendCommand("MAIL FROM:<{$this->from}>", '250');
        $this->sendRecipients();
        $this->sendCommand('DATA', '354');
        $this->sendCommand($this->getMailHeader($subject) . $this->getMailBody($message), '250');
        return empty($this->error);
    }


    public function quit()
    {
        if ($this->fp) {
            $this->sendCommand('QUIT');
            fclose($this->fp);
        }
    }


    public function __destruct()
    {
        $this->quit();
    }


    protected function formatStr($str)
    {
        return '=?' . $this->charset . '?B?' . base64_encode($str) . '?=';
    }


    protected function getMailHeader($subject)
    {
        $delimiter =& $this->delimiter;
        $subject = $this->formatStr($subject);
        $mailheader = "MIME-Version: 1.0{$delimiter}Subject: {$subject}{$delimiter}";
        if ($this->name) {
            $mailheader .= "From: " . $this->formatStr($this->name) . " <{$this->from}>{$delimiter}";
        } else {
            $mailheader .= "From: {$this->from}{$delimiter}";
        }

        $mailheader .= 'To: ' . implode(',', $this->to) . $delimiter;
        if ($this->cc) {
            $mailheader .= 'Cc: ' . implode(',', $this->cc) . $delimiter;
        }
        if ($this->bc) {
            $mailheader .= 'Bcc: ' . implode(',', $this->bc) . $delimiter;
        }
        if (!$this->attachments) {
            $mailheader .= "Content-Type: {$this->ct}; charset=\"{$this->charset}\"{$delimiter}";
            $mailheader .= "Content-Transfer-Encoding: base64{$delimiter}";
        } else {
            $mailheader .= "Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"{$delimiter}";
        }
        $mailheader .= $delimiter;
        return $mailheader;
    }


    protected function getMailBody($message)
    {
        $delimiter =& $this->delimiter;
        if ($this->attachments) {
            $mailbody = "This is a multi-part message in MIME format.{$delimiter}{$delimiter}";
            $mailbody .= "--{$this->boundary}{$delimiter}";
            $mailbody .= "Content-Type: {$this->ct}; charset=\"{$this->charset}\"{$delimiter}";
            $mailbody .= "Content-Transfer-Encoding: base64{$delimiter}{$delimiter}";
            $mailbody .= chunk_split(base64_encode($message)) . $delimiter;
            foreach ($this->attachments as $name => $file) {
                $mailbody .= "--{$this->boundary}$delimiter";
                $mailbody .= "Content-Type: application/octet-stream; name=\"" . $this->formatStr($name) . "\"{$delimiter}";
                $mailbody .= "Content-Transfer-Encoding: base64{$delimiter}";
                $mailbody .= "Content-Disposition: attachment; filename=\"" . $this->formatStr($name) . "\"{$delimiter}";
                $mailbody .= $delimiter . chunk_split(base64_encode($this->getFileContents($file))) . $delimiter;
            }
            $mailbody .= "--{$this->boundary}--";
        } else {
            $mailbody = chunk_split(base64_encode($message));
        }
        $mailbody .= $delimiter . '.';
        return $mailbody;
    }


    protected function error($msg = '')
    {
        if ($msg != '') {
            $this->error[] = $msg;
        }
        return $this->error;
    }


    protected function log($msg = '')
    {
        if ($msg != '') {
            $this->log[] = $msg;
        }
        return $this->log;
    }
}