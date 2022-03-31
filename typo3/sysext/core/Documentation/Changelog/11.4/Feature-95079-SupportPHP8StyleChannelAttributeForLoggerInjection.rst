.. include:: /Includes.rst.txt

============================================================================
Feature: #95079 - Support PHP 8 style Channel attribute for logger injection
============================================================================

See :issue:`95079`

Description
===========

Services are now able to control the component name that an
injected logger is created with.
This allows to group logs of related classes and is basically
a channel system as often used in monolog.

The :php:`\TYPO3\CMS\Core\Log\Channel` attribute is supported for constructor
argument injection as a class and parameter specific attribute and for
:php:`\Psr\Log\LoggerAwareInterface` dependency injection services as a class attribute.

This feature is only available with PHP 8.
The channel attribute will be gracefully ignored in PHP 7,
and the classic component name will be used instead.


Registration via class attribute for :php:`\Psr\Log\LoggerInterface` injection:

.. code-block:: php

    use Psr\Log\LoggerInterface;
    use TYPO3\CMS\Core\Log\Channel;
    #[Channel('security')]
    class MyClass
    {
        private LoggerInterface $logger;
        public function __construct(LoggerInterface $logger)
        {
            $this->logger = $logger;
            // do your magic
        }
    }

Registration via parameter attribute for :php:`LoggerInterface` injection,
overwrites possible class attributes:

.. code-block:: php

    use Psr\Log\LoggerInterface;
    use TYPO3\CMS\Core\Log\Channel;
    class MyClass
    {
        private LoggerInterface $logger;
        public function __construct(
            #[Channel('security')]
            LoggerInterface $logger
        ) {
            $this->logger = $logger;
            // do your magic
        }
    }


Registration via class attribute for :php:`LoggerAwareInterface` services.

.. code-block:: php

    use Psr\Log\LoggerAwareInterface;
    use Psr\Log\LoggerAwareTrait;
    use TYPO3\CMS\Core\Log\Channel;
    #[Channel('security')]
    class MyClass implements LoggerAwareInterface
    {
        use LoggerAwareTrait;
    }


Impact
======

It is now possible to group several classes into channels, regardless of the
PHP namespace.

.. index:: PHP-API, ext:core
