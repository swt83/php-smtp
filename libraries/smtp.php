<?php

/**
 * A new SMTP class built from scatch.
 *
 * @package    SMTP
 * @author     Scott Travis <scott.w.travis@gmail.com>
 * @link       http://github.com/swt83/laravel-smtp
 * @license    MIT License
 */

class SMTP
{
	// headers
	private $newline = "\r\n";
	private $charset = 'UTF-8';
	private $encoding = '7bit';
	private $wordwrap = 70;
	private $text_mode = false;

	// connection
	private $connection;
	private $localhost = 'localhost';
	private $timeout = 30;
	private $debug_mode = false;

	// authentication
	private $host;
	private $port;
	private $secure; // null, ssl, or tls
	private $user;
	private $pass;
	
	// email
	private $to = array();
	private $cc = array();
	private $bcc = array();
	private $from;
	private $body;
	private $text;
	private $subject;
	private $attachments = array();

	public function __construct()
	{
		// load config
		$config = Config::get('smtp');
		
		// set server vars
		$this->host = $config['host'];
		$this->port = $config['port'];
		$this->secure = $config['secure'];
		$this->user = $config['user'];
		$this->pass = $config['pass'];
		
		// set debug mode
		$this->debug_mode = $config['debug_mode'];
	}
	
	public function from($email, $name = null)
	{
		$this->from = array(
			'email' => $email,
			'name' => $name ? $name : '',
		);
	}
	
	public function to($email, $name = null)
	{
		$this->to[] = array(
			'email' => $email,
			'name' => $name ? $name : '',
		);
	}
	
	public function cc($email, $name = null)
	{
		$this->cc = array(
			'email' => $email,
			'name' => $name ? $name : '',
		);
	}
	
	public function bcc($email, $name = null)
	{
		$this->bcc = array(
			'email' => $email,
			'name' => $name ? $name : '',
		);
	}
	
	public function body($html)
	{
		$this->body = $html;
	}
	
	public function text($text)
	{
		$this->text = wordwrap($text, $this->wordwrap);
	}
	
	public function subject($subject)
	{
		$this->subject = $subject;
	}
	
	public function attach($path)
	{
		// not built yet
	}
	
	public function send_text()
	{
		$this->text_mode = true;
		$this->send();
	}
	
	public function send()
	{
		// connect to server
		if ($this->smtp_connect())
		{
			// deliver the email
			if ($this->smtp_deliver())
			{
				$result = true;
			}
			else
			{
				$result = false;
			}
		}
		else
		{
			$result = false;
		}
		
		// disconnect
		$this->smtp_disconnect();
		
		// return
		return $result;
	}
	
	private function smtp_connect()
	{
		// modify url, if needed
		if ($this->secure === 'ssl') $this->host = 'ssl://'.$this->host;
	
		// connect to server
		$this->connection = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
		if ($this->code() !== 220) return false;
		
		// send helo
		fputs($this->connection, 'HELO '.$this->localhost.$this->newline);
		$this->status();
		
		// if tls required...
		if($this->secure === 'tls')
		{
			// send starttls
			fputs($this->connection, 'STARTTLS'.$this->newline);
			if ($this->code() !== 220) return false;
			
			// enable crypto
			stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
			
			// send helo
			fputs($this->connection, 'HELO '.$this->localhost.$this->newline);
			if ($this->code() !== 250) return false;
		}
		
		// if not localhost...
		if($this->host !== 'localhost')
		{
			// send auth login
			fputs($this->connection, 'AUTH LOGIN'.$this->newline);
			if ($this->code() !== 334) return false;
			
			// send username
			fputs($this->connection, base64_encode($this->user).$this->newline);
			if ($this->code() !== 334) return false;
			
			// send password
			fputs($this->connection, base64_encode($this->pass).$this->newline);
			if ($this->code() !== 235) return false;
		}
		
		// return
		return true;
	}
	
	private function smtp_construct()
	{
		// set unique boundary
		$boundary = md5(uniqid(time()));
		
		// add from info
		$headers[] = 'From: '.$this->format($this->from);
		$headers[] = 'Reply-To: '.$this->format($this->from);
		$headers[] = 'Subject: '.$this->subject;
		
		// add to receipients
		if (!empty($this->to))
		{
			$string = '';
			foreach ($this->to as $r) $string .= $this->format($r).', ';
			$string = substr($string, 0, -2);
			$headers[] = 'To: '.$string;
		}
		
		// add cc recipients
		if (!empty($this->cc))
		{
			$string = '';
			foreach ($this->cc as $r) $string .= $this->format($r).', ';
			$string = substr($string, 0, -2);
			$headers[] = 'CC: '.$string;
		}
		
		// add email content
		if (empty($this->attachments))
		{
			if ($this->text_mode === true)
			{
				// add text
				$headers[] = 'Content-Type: text/plain; charset="'.$this->charset.'"';
				$headers[] = 'Content-Transfer-Encoding: '.$this->encoding;
				$headers[] = '';
				$headers[] = $this->text;
			}
			else
			{
				// add multipart
				$headers[] = 'MIME-Version: 1.0';
				$headers[] = 'Content-Type: multipart/alternative; boundary="'.$boundary.'"';
				$headers[] = '';
				$headers[] = 'This is a multi-part message in MIME format.';
				$headers[] = '--'.$boundary;
				
				// add text
				$headers[] = 'Content-Type: text/plain; charset="'.$this->charset.'"';
				$headers[] = 'Content-Transfer-Encoding: '.$this->encoding;
				$headers[] = '';
				$headers[] = $this->text;
				$headers[] = '--'.$boundary;
				
				// add html
				$headers[] = 'Content-Type: text/html; charset="'.$this->charset.'"';
				$headers[] = 'Content-Transfer-Encoding: '.$this->encoding;
				$headers[] = '';
				$headers[] = $this->body;
				$headers[] = '--'.$boundary.'--';
			}
		}
		else
		{
			/*
			
			// my notes for doing attachments
			
			$file = $path.$filename;
			$file_size = filesize($file);
			$handle = fopen($file, "r");
			$content = fread($handle, $file_size);
			fclose($handle);
			$content = chunk_split(base64_encode($content));
			$uid = md5(uniqid(time()));
			$name = basename($file);
			$headers[] = '--'.$boundary;
			$headers[] = "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; // use different content types here
			$headers[]= "Content-Transfer-Encoding: base64\r\n";
			$headers[]= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
			$headers[]= $content."\r\n\r\n";
			*/	
		}
		
		// final period
		$headers[] = '.';
		
		// build headers string
		$email = '';
		foreach ($headers as $header)
		{
			$email .= $header.$this->newline;
		}
		
		// return
		return $email;
	}
	
	private function smtp_deliver()
	{
		// send mailfrom
		fputs($this->connection, 'MAIL FROM: <'. $this->from['email'] .'>'.$this->newline);
		$this->status();
		
		// send rcptto
		$recipients = $this->to + $this->cc + $this->bcc;
		foreach ($recipients as $r)
		{
			fputs($this->connection, 'RCPT TO: <'.$r['email'].'>'.$this->newline);
			$this->status();
		}
		
		// send data
		fputs($this->connection, 'DATA'.$this->newline);
		$this->status();
		
		// send headers
		fputs($this->connection, $this->smtp_construct());
		if ($this->code() === 250)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function smtp_disconnect()
	{
		// send quit
		fputs($this->connection, 'QUIT'.$this->newline);
		$this->status();
		
		// close connection
		fclose($this->connection);
	}
	
	private function status()
	{
		// get response
		$response = '';
		while ($str = fgets($this->connection, 4096))
		{
			$response .= $str;
			if (substr($str, 3, 1) === ' ') break;
		}
		
		// report
		if ($this->debug_mode) echo '<code>'.$response.'</code><br/>';
		
		// return
		return $response;
	}
	
	private function code()
	{
		// filter code from response
		return (int) substr($this->status(), 0, 3);
	}
	
	private function format($recipient)
	{
		// format name <email>
		if ($recipient['name'])
		{
			return $recipient['name'].' <'.$recipient['email'].'>';
		}
		else
		{
			return $recipient['email'];
		}
	}
}