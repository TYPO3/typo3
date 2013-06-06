# gelf-php

[![Build Status](https://secure.travis-ci.org/Graylog2/gelf-php.png?branch=master)](http://travis-ci.org/Graylog2/gelf-php)

PHP classes to send [GELF (Graylog extended log format)](http://www.graylog2.org/about/gelf) messages

Issue tracker: [JIRA gelf-php](http://jira.graylog2.org/browse/GELFPHP)

## Author

[Lennart Koopman](https://github.com/lennartkoopmann)

See [the list of contributors](https://github.com/Graylog2/gelf-php/contributors)


## Example

```php
<?php

require('GELFMessage.php');
require('GELFMessagePublisher.php');

$message = new GELFMessage();
$message->setShortMessage('something is broken.');
$message->setFullMessage("lol full message!");
$message->setHost('somehost');
$message->setLevel(GELFMessage::CRITICAL);
$message->setFile('/var/www/example.php');
$message->setLine(1337);
$message->setAdditional("something", "foo");
$message->setAdditional("something_else", "bar");

$publisher = new GELFMessagePublisher('172.16.22.30');
$publisher->publish($message);
```
