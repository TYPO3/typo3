.. include:: /Includes.rst.txt

.. _breaking-110219-1784306184:

============================================================
Breaking: #110219 - Log request ID provided by log processor
============================================================

See :issue:`110219`

Description
===========

The unique ID of the current request, used to correlate all log entries
written during a single request, was previously passed as constructor
argument from :php:`\TYPO3\CMS\Core\Log\LogManager` to every
:php:`\TYPO3\CMS\Core\Log\Logger` instance, which in turn copied it into
each created :php:`\TYPO3\CMS\Core\Log\LogRecord`.

The request ID is now added to log records at logging time by the new log
processor :php:`\TYPO3\CMS\Core\Log\Processor\RequestIdProcessor`, which
:php-short:`\TYPO3\CMS\Core\Log\LogManager` attaches automatically to every logger it creates, for
all severity levels covered by the configured log writers. The processor
is registered internally on purpose — not via
:php:`$GLOBALS['TYPO3_CONF_VARS']['LOG']` — so it cannot be removed
accidentally by overriding the global processor configuration.

The following method signatures have changed:

- :php-short:`\TYPO3\CMS\Core\Log\Logger::__construct()` no longer accepts a second
  :php:`$requestId` argument, the protected property
  :php-short:`\TYPO3\CMS\Core\Log\Logger::$requestId` has been removed
- :php-short:`\TYPO3\CMS\Core\Log\LogManager::__construct()` now expects a
  :php:`\TYPO3\CMS\Core\Core\RequestId` object instead of a string, and
  creates one itself if omitted
- The protected method :php-short:`\TYPO3\CMS\Core\Log\LogManager::makeLogger()` no longer receives
  a :php:`$requestId` argument

The generated log output is unchanged: log records written by configured
writers carry the same request ID as before, available via
:php-short:`\TYPO3\CMS\Core\Log\LogRecord::getRequestId()`.

Impact
======

Instantiating :php-short:`\TYPO3\CMS\Core\Log\LogManager` with a string request ID will raise a PHP
:php:`\TypeError`.

Passing a second argument to the :php-short:`\TYPO3\CMS\Core\Log\Logger` constructor is ignored.
Log records created by a manually instantiated :php-short:`\TYPO3\CMS\Core\Log\Logger` — bypassing
:php-short:`\TYPO3\CMS\Core\Log\LogManager` — no longer contain a request ID, unless the
:php-short:`\TYPO3\CMS\Core\Log\Processor\RequestIdProcessor` is attached manually.

Affected installations
======================

TYPO3 installations with third-party extensions instantiating
:php-short:`\TYPO3\CMS\Core\Log\Logger` or :php-short:`\TYPO3\CMS\Core\Log\LogManager` directly with a custom request ID,
which is very unlikely. Extensions obtaining loggers via dependency
injection, the :php:`#[Channel]` attribute, :php-short:`\Psr\Log\LoggerAwareInterface`
or :php-short:`\TYPO3\CMS\Core\Log\LogManager->getLogger()` are not affected.

Migration
=========

Obtain loggers through dependency injection or
:php-short:`\TYPO3\CMS\Core\Log\LogManager->getLogger()`, which attach the
:php-short:`\TYPO3\CMS\Core\Log\Processor\RequestIdProcessor` automatically.

For manually created loggers that should add the request ID to their
log records, attach the processor explicitly:

.. code-block:: php

    use TYPO3\CMS\Core\Core\RequestId;
    use TYPO3\CMS\Core\Log\Logger;
    use TYPO3\CMS\Core\Log\LogLevel;
    use TYPO3\CMS\Core\Log\Processor\RequestIdProcessor;

    $logger = new Logger('my.channel');
    $logger->addWriter(LogLevel::WARNING, $myWriter);
    $logger->addProcessor(LogLevel::WARNING, new RequestIdProcessor(new RequestId()));

.. index:: PHP-API, NotScanned, ext:core
