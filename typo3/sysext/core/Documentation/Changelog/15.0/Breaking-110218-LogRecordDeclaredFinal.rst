.. include:: /Includes.rst.txt

.. _breaking-110218-1784306183:

==================================================
Breaking: #110218 - Class LogRecord declared final
==================================================

See :issue:`110218`

Description
===========

The PHP class :php:`\TYPO3\CMS\Core\Log\LogRecord` — the value object passed
to log writers and processors — has been declared :php:`final` and is now
instantiated directly via :php:`new` within
:php:`\TYPO3\CMS\Core\Log\Logger`, instead of using
:php:`GeneralUtility::makeInstance()`.

A log record is a plain data transfer object created for every single log
entry. Routing its creation through :php:`makeInstance()` allowed the class
to be overridden via XCLASS, but caused an unnecessary container lookup in
one of the most frequently executed code paths of the logging API.

In addition, the class now declares :php:`strict_types` and uses native
type declarations for all method signatures instead of loose PHPDoc
annotations.

Impact
======

Extending or XCLASSing :php:`LogRecord` is not possible anymore. Extension
classes will raise a fatal PHP error, XCLASS configurations for this class
are silently ignored.

Calling a method of :php:`LogRecord` with a wrong argument type — for
example a non-float value for :php:`setCreated()` — will raise a PHP
:php:`\TypeError`, depending on the :php:`strict_types` mode of the
calling code.

Creating, reading and modifying log records — for example in custom
implementations of
:php:`\TYPO3\CMS\Core\Log\Processor\ProcessorInterface` or
:php:`\TYPO3\CMS\Core\Log\Writer\WriterInterface` — continues to work as
before.

Affected installations
======================

TYPO3 installations with third-party extensions that extend or XCLASS the
class :php:`LogRecord`, which is very unlikely.

Migration
=========

To enrich or modify log records, implement a custom log processor
(:php:`ProcessorInterface`), which may return a modified or newly created
:php:`LogRecord` instance. Custom output handling belongs into a log writer
(:php:`WriterInterface`).

.. index:: PHP-API, NotScanned, ext:core
