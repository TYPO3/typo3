.. include:: /Includes.rst.txt

.. _breaking-110221-1784306185:

===============================================
Breaking: #110221 - Class Logger declared final
===============================================

See :issue:`110221`

Description
===========

The PHP class :php:`\TYPO3\CMS\Core\Log\Logger` has been declared
:php:`final` and is now instantiated directly via :php:`new` within
:php:`\TYPO3\CMS\Core\Log\LogManager`, instead of using
:php:`GeneralUtility::makeInstance()`.

The logger is a plain, per-channel object created and configured by the
:php:`LogManager`. Routing its creation through :php:`makeInstance()` only
served to allow overriding the class via XCLASS, which is not a supported
extension point for the logging API.

Impact
======

Extending or XCLASSing :php:`Logger` is not possible anymore. Extension
classes will raise a fatal PHP error, XCLASS configurations for this class
are silently ignored.

Obtaining and using loggers via dependency injection, the :php:`#[Channel]`
attribute, :php:`LoggerAwareInterface` or
:php:`LogManager->getLogger()` continues to work as before.

Affected installations
======================

TYPO3 installations with third-party extensions that extend or XCLASS the
class :php:`Logger`, which is very unlikely.

Migration
=========

To customize logging behavior, implement the PSR-3
:php:`\Psr\Log\LoggerInterface` and provide the instances through a custom
:php:`\TYPO3\CMS\Core\Log\LogManagerInterface` implementation, or attach
custom log writers (:php:`\TYPO3\CMS\Core\Log\Writer\WriterInterface`) and
processors (:php:`\TYPO3\CMS\Core\Log\Processor\ProcessorInterface`) to the
existing logger, which is the designated way to influence how log records
are handled.

.. index:: PHP-API, NotScanned, ext:core
