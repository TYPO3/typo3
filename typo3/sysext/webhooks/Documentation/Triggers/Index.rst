..  include:: /Includes.rst.txt

..  _triggers:

========
Triggers
========

The TYPO3 Core currently provides the following triggers for webhooks:

Page Modification
    Triggers when a page is created, updated or deleted
File Added
    Triggers when a file is added
File Updated
    Triggers when a file is updated
File Removed
    Triggers when a file is removed
Login Error Occurred
    Triggers when a login error occurred

These triggers are meant as a first set of triggers that can be used to send
webhooks. In most projects however, it is likely
that custom triggers are required.

..  _custom-triggers:

Custom triggers
===============

..  _custom-triggers-events:

Trigger by PSR-14 events
------------------------

Custom triggers can be added by creating a `Message` for a specific PSR-14
event and by tagging that message as a webhook message.

The following example shows how to create a simple webhook message for the
:php:`\TYPO3\CMS\Core\Resource\Event\AfterFolderAddedEvent`:

..  literalinclude:: _snippets/_FolderAddedMessage.php
    :caption: EXT:my_extension/Webhooks/Message/FolderAddedMessage.php

#.  Create a final class implementing the :php-short:`\TYPO3\CMS\Core\Messaging\WebhookMessageInterface`.
#.  Add the :php-short:`\TYPO3\CMS\Core\Attribute\WebhookMessage` attribute to
    the class. The attribute requires the
    following information:

    *   `identifier`: The identifier of the webhook message.
    *   `description`: The description of the webhook message. This description
        is used to describe the trigger in the TYPO3 backend.
#.  Add a static method `createFromEvent()` that creates a new instance of the message from the event you want to use as a trigger.
#.  Add a method `jsonSerialize()` that returns an array with the data that should be send with the webhook.

..  _custom-triggers-events-services.yaml:

Use :file:`Services.yaml` instead of the PHP attribute
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Instead of the PHP attribute the :file:`Services.yaml` can be used to define the
webhook message. The following example shows how to define the webhook message
from the example above in the :file:`Services.yaml`:

..  literalinclude:: _snippets/_Services.yaml
    :caption: EXT: my_extension/Configuration/Services.yaml

..  _custom-triggers-hooks:

Trigger by hooks or custom code
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case a trigger is not provided by the TYPO3 Core or a PSR-14 event is not available,
it is possible to create a custom trigger - for example by using a TYPO3 hook.

The message itself should look similar to the example above, but does not need the
:php:`createFromEvent()` method.

Instead, the custom code (hook implementation) will create the message
and dispatch it.

Example hook implementation for a datahandler hook
(see :php:`\TYPO3\CMS\Webhooks\Listener\PageModificationListener`):

..  literalinclude:: _snippets/_PageModificationListener.php
    :caption: EXT:my_extension/Webhooks/Message/PageModificationListener.php
