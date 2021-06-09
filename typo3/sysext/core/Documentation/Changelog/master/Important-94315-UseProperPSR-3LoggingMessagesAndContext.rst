.. include:: ../../Includes.txt

=================================================================
Important: #94315 - Use proper PSR-3 logging messages and context
=================================================================

See :issue:`94315`

Description
===========

The v11 core is looking into proper PSR-3 Logging implementation again. When
analyzing the current situation, we realized many core logging calls were
using messages that violated the PSR-3
`placeholder specification <https://www.php-fig.org/psr/psr-3/>`__.

The v11 core fixed all places, but it's likely extensions have this issue,
too. Extension developers should have a look at their logger calls and adapt
them if necessary.

Typical call before:

.. code-block:: php

   $this->logger->alert('Password reset requested for email "' . $emailAddress . '" . but was requested too many times.');

Correct call:

.. code-block:: php

   $this->logger->alert('Password reset requested for email {email} but was requested too many times.', ['email' => $emailAddress]);

First argument is 'message', second (optional) argument is 'context'. A message can
use :php:`{placehlders}`. All core provided log writers will substitute placeholders
in the message with data from the context array, if a context array key with same name exists.

.. index:: PHP-API, ext:core
