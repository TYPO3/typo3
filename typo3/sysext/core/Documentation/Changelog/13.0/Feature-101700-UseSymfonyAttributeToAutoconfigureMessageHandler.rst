.. include:: /Includes.rst.txt

.. _important-101700-1703832449:

==========================================================================
Feature: #101700 - Use Symfony attribute to autoconfigure message handlers
==========================================================================

See :issue:`101700`

Description
===========

The symfony PHP attribute :php:`\Symfony\Component\Messenger\Attribute\AsMessageHandler`
is now respected and allows to register services as message handlers by setting
the attribute on the class or the method.

Before:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Queue\Handler\DemoHandler:
      tags:
        - name: 'messenger.message_handler'

After:

The registration can be removed from the :file:`Configuration/Services.yaml`
file and the attribute is assigned to the handler class instead:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Queue/Handler.php

    <?php

    namespace MyVendor\MyExtension\Queue\Handler;

    use MyVendor\MyExtension\Queue\Message\DemoMessage;
    use Symfony\Component\Messenger\Attribute\AsMessageHandler;

    #[AsMessageHandler]
    final class DemoHandler
    {
        public function __invoke(DemoMessage $message): void
        {
            // do something with $message
        }
    }

It's also possible to set the attribute on the method:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Queue/Handler.php

    <?php

    namespace MyVendor\MyExtension\Queue\Handler;

    use MyVendor\MyExtension\Queue\Message\DemoMessage;
    use Symfony\Component\Messenger\Attribute\AsMessageHandler;

    final class DemoHandler
    {
        #[AsMessageHandler]
        public function __invoke(DemoMessage $message): void
        {
            // do something with $message
        }
    }


Impact
======

The registration of services as message handlers has been simplified by
respecting the :php:`\Symfony\Component\Messenger\Attribute\AsMessageHandler`
attribute. When using this attribute, there is no need to register such
service in the :file:`Configuration/Services.yaml` file anymore. Existing
configuration will work as before.

.. index:: Backend, CLI, PHP-API, ext:core
