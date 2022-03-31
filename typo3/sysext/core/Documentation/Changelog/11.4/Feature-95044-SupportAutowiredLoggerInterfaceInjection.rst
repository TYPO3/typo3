.. include:: /Includes.rst.txt

=============================================================
Feature: #95044 - Support autowired LoggerInterface injection
=============================================================

See :issue:`95044`

Description
===========

Logger instances may be required to be available during object
construction, LoggerAwareInterface isn't an option in that case.
Therefore :php:`\Psr\Log\LoggerInterface` as constructor argument
is now autowired (if the service is configured to use autowiring)
and instantiated with an object-specific logger.


Impact
======

Services are no longer required to use
:php:`\Psr\Log\LoggerAwareInterface` and :php:`\Psr\Log\LoggerAwareTrait`,
but can add a constructor argument :php:`\Psr\Log\LoggerInterface` instead.

Example:

.. code-block:: php

    use Psr\Log\LoggerInterface;

    class MyClass {
        private LoggerInterface $logger;

        public function __construct(LoggerInterface $logger) {
            $this->logger = $logger;
        }
    }

.. index:: PHP-API, ext:core
