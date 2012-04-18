# SMTP for LaravelPHP #

This package is a new SMTP class built from scratch.  Many of the existing email libraries are old, bloated, not on GitHub, and worst of all written in camelcase.  I wanted something short and simple.

## Install ##

Copy the config file to ``application/config/smtp.php`` and input the proper information.

## Usage ##

A normal email would go like this:

```php
$mail = new SMTP();
$mail->to('sally@foobar.com', 'Sally Mae');
$mail->from('john@foobar.com', 'John Doe');
$mail->subject('Test Email');
$mail->body('Did <b>this</b> work?');
$result = $mail->send();
```

You can add multiple recipients:

```php
// add to
$mail->to('person1@foobar.com');
$mail->to('person2@foobar.com');

// add cc
$mail->cc('person3@foobar.com');
$mail->cc('person4@foobar.com');

// add bcc
$mail->bcc('person5@foobar.com');
$mail->bcc('person6@foobar.com');
```

You can add attachments:

```php
$mail->attach('/path/to/file.png');
```

You can assign a text version of your email:

```php
$mail->text('Text version of my email, cool.');
```

You can send text-only emails:

```php
$result = $mail->send_text();
```

## Debug Mode ##

In the config you can flag ``'debug_mode' = true;``, which can be helpful in testing your SMTP connections.  It will echo server reponses from each step in the email sending process.

## Limitations ##

Below are some current limitations, which are things I hope to fix w/ time.  Please feel free to contribute to this ongoing project.

* Does not support encryption.
* Does not support priority level.
* Does not keep connection open for spooling email sends.