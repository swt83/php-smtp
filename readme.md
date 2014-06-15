# SMTP

This package is a new SMTP class built from scratch.  Many of the existing email libraries are old, bloated, not on GitHub, and worst of all written in camelcase.  I wanted something short and simple.

2013-12-20 -- I know that Laravel 4 contains its own mailing class based on SwiftMailer, but I still have problems with that software.  I've decided to continue development of this package for the time being.

2014-06-15 -- I removed the ability to set recipients en masse with arrays.  It was a little confusing and I think caused more problems than it solved.  You just need to loop an array and assign individually.

## Install

Normal install via Composer.

### Provider

Register the service provider in your ``app/config/app.php`` file:

```php
'Travis\SMTP\Provider',
```

### Config

Copy the config file to ``app/config/packages/travis/smtp/config.php`` and input the necessary information.

## Usage

A normal email would go like this:

```php
$mail = new Travis\SMTP();
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
