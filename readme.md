# SMTP for LaravelPHP #

This package is a new SMTP class built from scratch.  Many of the existing email libraries are old, bloated, not on GitHub, and worst of all written in camelcase.  I wanted something short and simple.

## Install ##

In ``application/bundles.php`` add:

```php
'smtp' => array('auto' => true),
```

Copy the sample config file to ``application/config/smtp.php`` and input the proper information.

## Usage ##

A normal email would go like this:

```php
$mail = new SMTP();
$mail->to('tim@gmail.com');
$mail->from('paul@gmail.com', 'Paul T.');
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

## Debug Mode ##

In the config you can flag ``'debug_mode' = true;``, which can be helpful in testing your SMTP connections.  It will echo server responses from each step in the email sending process.

## Limitations ##

Below are some current limitations.  Please feel free to contribute to this ongoing project.

* Does not support encryption.
* Does not support priority level.
* Does not keep connection open for spooling email sends.