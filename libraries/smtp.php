<?php

/**
 * A simple SMTP class with a fresh start.
 *
 * @package    SMTP
 * @author     Scott Travis <scott.w.travis@gmail.com>
 * @link       http://github.com/swt83/laravel-smtp
 * @license    MIT License
 */

class SMTP
{
	// connection
	private $connection;
	private $localhost = 'localhost';
	private $timeout = 30;
	private $debug_mode = false;

	// auth
	private $host;
	private $port;
	private $secure; // null, 'ssl', or 'tls'
	private $auth; // true if authorization required
	private $user;
	private $pass;
	
	// email
	private $to = array();
	private $cc = array();
	private $bcc = array();
	private $from;
	private $reply;
	private $body;
	private $text;
	private $subject;
	private $attachments = array();
	private $text_mode = false;
	
	// misc
	private $charset = 'UTF-8';
	private $newline = "\r\n";
	private $encoding = '7bit';
	private $wordwrap = 70;

	public function __construct($connection = null)
	{
		// load config
		$config = Config::get('smtp');
	
		// if no connection...
		if (!$connection)
		{
			// load connection
			$connection = $config['connections'][$config['default']];
		}
		else
		{
			// load connection
			$connection = $config['connections'][$connection];
		}
		
		// set connection vars
		$this->host = $connection['host'];
		$this->port = $connection['port'];
		$this->secure = $connection['secure'];
		$this->auth = $connection['auth'];
		$this->user = $connection['user'];
		$this->pass = $connection['pass'];
		
		// set debug mode
		$this->debug_mode = $config['debug_mode'];
	}
	
	public function from($email, $name = null)
	{
		// if not array...
		if (!is_array($email))
		{
			// set normal
			$this->from = array(
				'email' => $email,
				'name' => $name,
			);
		}
		else
		{
			// set convention
			$this->from = array(
				'email' => isset($email[0]) ? $email[0] : null,
				'name' => isset($email[1]) ? $email[1] : null,
			);			
		}
	}
	
	public function reply($email, $name = null)
	{
		// if not array...
		if (!is_array($email))
		{
			// set normal
			$this->reply = array(
				'email' => $email,
				'name' => $name,
			);
		}
		else
		{
			// set convention
			$this->reply = array(
				'email' => isset($email[0]) ? $email[0] : null,
				'name' => isset($email[1]) ? $email[1] : null,
			);			
		}
	}
	
	public function to($email, $name = null)
	{
		// if not array...
		if (!is_array($email))
		{
			// set normal
			$this->to[] = array(
				'email' => $email,
				'name' => $name,
			);
		}
		else
		{
			// spin array...
			foreach ($email as $e)
			{
				// fix array
				if (!is_array($e)) $e = array($e);
			
				// set convention
				$this->to[] = array(
					'email' => isset($e[0]) ? $e[0] : null,
					'name' => isset($e[1]) ? $e[1] : null,
				);
			}
		}
	}
	
	public function cc($email, $name = null)
	{
		// if not array...
		if (!is_array($email))
		{
			// set normal
			$this->cc[] = array(
				'email' => $email,
				'name' => $name,
			);
		}
		else
		{
			// spin array...
			foreach ($email as $e)
			{
				// fix array
				if (!is_array($e)) $e = array($e);
			
				// set convention
				$this->cc[] = array(
					'email' => isset($e[0]) ? $e[0] : null,
					'name' => isset($e[1]) ? $e[1] : null,
				);
			}
		}
	}
	
	public function bcc($email, $name = null)
	{
		// if not array...
		if (!is_array($email))
		{
			// set normal
			$this->bcc[] = array(
				'email' => $email,
				'name' => $name,
			);
		}
		else
		{
			// spin array...
			foreach ($email as $e)
			{
				// fix array
				if (!is_array($e)) $e = array($e);
			
				// set convention
				$this->bcc[] = array(
					'email' => isset($e[0]) ? $e[0] : null,
					'name' => isset($e[1]) ? $e[1] : null,
				);
			}
		}
	}
	
	public function body($html)
	{
		$this->body = $html;
	}
	
	public function text($text)
	{
		$this->text = wordwrap(strip_tags($text), $this->wordwrap);
	}
	
	public function subject($subject)
	{
		$this->subject = $subject;
	}
	
	public function attach($path)
	{
		// if not array...
		if (!is_array($path))
		{
			// add
			$this->attachments[] = $path;
		}
		else
		{
			// spin array...
			foreach ($path as $p)
			{
				// add
				$this->attachments[] = $p;
			}
		}
	}
	
	public function send_text()
	{
		// text mode
		$this->text_mode = true;
		
		// return
		return $this->send();
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
		
		// After each request we send to the SMTP server, we'll call the
		// response() method to see what the server had to say.  If debug mode
		// is activated, then these responses will be printed to the screen.
		// Note that the code() method automatically calls response().
		
		// open connection
		$this->connection = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
		
		// response
		if ($this->code() !== 220) return false;
		
		// request
		$this->request('HELO '.$this->localhost.$this->newline);
		
		// response
		$this->response();
		
		// if tls required...
		if ($this->secure === 'tls')
		{
			// request
			$this->request('STARTTLS'.$this->newline);
			
			// response
			if ($this->code() !== 220) return false;
			
			// enable crypto
			stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
			
			// request
			$this->request('HELO '.$this->localhost.$this->newline);
			
			// response
			if ($this->code() !== 250) return false;
		}
		
		// if auth required...
		if ($this->auth)
		{
			// request
			$this->request('AUTH LOGIN'.$this->newline);
			
			// response
			if ($this->code() !== 334) return false;
			
			// request
			$this->request(base64_encode($this->user).$this->newline);
			
			// response
			if ($this->code() !== 334) return false;
			
			// request
			$this->request(base64_encode($this->pass).$this->newline);
			
			// response
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
		$headers[] = 'Reply-To: '.$this->format($this->reply ? $this->reply : $this->from);
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
		
		// build email contents
		if (empty($this->attachments))
		{
			if ($this->text_mode)
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
			// add multipart
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'Content-Type: multipart/mixed; boundary="'.$boundary.'"';
			$headers[] = '';
			$headers[] = 'This is a multi-part message in MIME format.';
			$headers[] = '--'.$boundary;
			
			// add text
			$headers[] = 'Content-Type: text/plain; charset="'.$this->charset.'"';
			$headers[] = 'Content-Transfer-Encoding: '.$this->encoding;
			$headers[] = '';
			$headers[] = $this->text;
			$headers[] = '--'.$boundary;
			
			if (!$this->text_mode)
			{
				// add html
				$headers[] = 'Content-Type: text/html; charset="'.$this->charset.'"';
				$headers[] = 'Content-Transfer-Encoding: '.$this->encoding;
				$headers[] = '';
				$headers[] = $this->body;
				$headers[] = '--'.$boundary;
			}
			
			// spin thru attachments...		
			foreach ($this->attachments as $path)
			{
				// if file exists...
				if (file_exists($path))
				{
					// open file
					$contents = @file_get_contents($path);
					
					// if accessible...
					if ($contents)
					{
						// encode file contents
						$contents = chunk_split(base64_encode($contents));

						// add attachment
						$headers[] = 'Content-Type: application/octet-stream; name="'.basename($path).'"'; // use different content types here
						$headers[] = 'Content-Transfer-Encoding: base64';
						$headers[] = 'Content-Disposition: attachment';
						$headers[] = '';
						$headers[] = $contents;
						$headers[] = '--'.$boundary;
					}
				}
			}
				
			// add last "--"
			$headers[sizeof($headers) - 1] .= '--';
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
		// request
		$this->request('MAIL FROM: <'. $this->from['email'] .'>'.$this->newline);
		
		// response
		$this->response();
		
		// spin recipients...
		$recipients = $this->to + $this->cc + $this->bcc;
		foreach ($recipients as $r)
		{
			// request
			$this->request('RCPT TO: <'.$r['email'].'>'.$this->newline);
			
			// response
			$this->response();
		}
		
		// request
		$this->request('DATA'.$this->newline);
		
		// response
		$this->response();
		
		// request
		$this->request($this->smtp_construct());
		
		// response
		if ($this->code() === 250)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	private function smtp_disconnect()
	{
		// request
		$this->request('QUIT'.$this->newline);
		
		// response
		$this->response();
		
		// close connection
		fclose($this->connection);
	}
	
	private function code()
	{
		// filter code from response
		return (int) substr($this->response(), 0, 3);
	}
	
	private function request($string)
	{
		// report
		if ($this->debug_mode) echo '<code><strong>'.$string.'</strong></code><br/>';
		
		// send
		fputs($this->connection, $string);
	}
	
	private function response()
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
	
	private function format($recipient)
	{
		// format "name <email>"
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