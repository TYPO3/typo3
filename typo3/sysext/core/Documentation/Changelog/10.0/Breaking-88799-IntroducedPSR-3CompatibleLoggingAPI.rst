.. include:: /Includes.rst.txt

==========================================================
Breaking: #88799 - Introduced PSR-3 compatible Logging API
==========================================================

See :issue:`88799`

Description
===========

With the adaption of the PSR-3 standard some PHP code had to be changed in order to reach compliance.
The key difference is that log levels are now represented by strings rather than numbers. Note that the order
of log levels is not affected and stays the same.

The breaking changes mostly affect internal functionality and should not apply to third-party extensions.


Impact
======

The class :php:`\TYPO3\CMS\Core\Log\LogLevel` now extends from the PSR-3 base class and therefore inherits the new definition
of the log levels (`EMERGENCY` to `DEBUG`) based on string values.

The signatures of following methods have been adjusted to accept the new :php:`LogLevel::*` constants:

* :php:`\TYPO3\CMS\Core\Log\Logger::addWriter`
* :php:`\TYPO3\CMS\Core\Log\Logger::addProcessor`

The internal storage of the log level inside :php:`\TYPO3\CMS\Core\Log\LogRecord` has been adjusted, consequently the methods

* :php:`setLevel()` and
* :php:`getLevel()`

respectively accept and return :php:`string` values now.

In case you have configured own logger or log targets, you have to adjust the integer level and use strings.

Example:

.. code-block:: php

   # old configuration
   $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['Core']['writerConfiguration'] = [
      7 => [
         \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => 'typo3temp/var/log/core.log'
         ]
      ],
   ];

   # new configuration
   $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['Core']['writerConfiguration'] = [
      'debug' => [
         \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => 'typo3temp/var/log/core.log'
         ]
      ],
   ];

In case you have used the constants like :php:`LogLevel::DEBUG` you are fine and your config will work like before.


Affected Installations
======================

Any installation using third-party extensions interacting with the internals of the Logging API.


Migration
=========

There are two easy ways to convert the integer to the string representation and vice versa:

- Convert from integer to string: :php:`$logLevel = LogLevel::getInternalName($logLevelAsNumber)`
- Convert from string to integer: :php:`$logLevelAsNumber = LogLevel::normalizeLevel($logLevel)`

.. index:: PHP-API, NotScanned, ext:core
