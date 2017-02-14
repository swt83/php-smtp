# SMTP

This package is a new SMTP class built from scratch.  Many of the existing email libraries are old, bloated, not on GitHub, and worst of all written in camelcase.  I wanted something short and simple.

## Install

Normal install via Composer.

## Config

Everytime you make a new SMTP object, you have to pass a config array.  This is how we make the package framework agnostic.  The included config file is your reference for the required fields and values.

One method is to pass the array directly from the file:

```php
$mail = new Travis\SMTP(require __DIR__ . '/path/to/config.php');
```

Another method is to use Laravel to pass a config after manually copying the file to ``app/config/smtp.php``:

```php
$mail = new Travis\SMTP(Config::get('smtp'));
```

Note that your config includes multiple connections, and you can choose which one to use when you forge the object:

```php
$mail = new Travis\SMTP($config, 'amazon');
```

You can also set a default connection in the config array.

## Usage

A normal email would go like this:

```php
use Travis\SMTP;

$mail = new SMTP($config);
$mail->to('tim@gmail.com');
$mail->from('paul@gmail.com', 'Paul T.'); // email is required, name is optional
$mail->subject('Hello World');
$mail->body('This is a <b>HTML</b> email.');
$result = $mail->send();
```

You can add multiple recipients, name is optional:

```php
// add to
$mail->to('matthew@gmail.com');
$mail->to('mark@gmail.com');

// add cc
$mail->cc('luke@gmail.com');
$mail->cc('john@gmail.com');

// add bcc
$mail->bcc('james@gmail.com');
$mail->bcc('peter@gmail.com');
```

You can set a custom reply-to address:

```php
$mail->reply('paul@gmail.com', 'Paul T.');
```

You can add attachments:

```php
$mail->attach('/path/to/file1.png');
$mail->attach('/path/to/file2.png');
```

You can assign a text version of your email:

```php
$mail->text('This is a text email.');
```

You can send text-only emails:

```php
$result = $mail->send_text();
```

### Debug Mode

In the config you can flag ``'debug_mode' = true;``, which can be helpful in testing your SMTP connections.  It will echo server responses from each step in the email sending process.

## Limitations

Below are some current limitations.  Please feel free to contribute to this ongoing project.

* Does not support encryption.
* Does not support priority level.
* Does not keep connection open for spooling email sends.
