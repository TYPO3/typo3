.. include:: /Includes.rst.txt

===========================================================
Deprecation: #83121 - Logging method DataHandler->newlog2()
===========================================================

See :issue:`83121`

Description
===========

The PHP method :php:`DataHandler->newlog2()` within DataHandler, TYPO3's core persistence API,
has been marked as deprecated.


Impact
======

Calling this method in PHP will trigger a deprecation warning.


Affected Installations
======================

Custom extensions calling DataHandler and using the method above directly in PHP.


Migration
=========

Use DataHandlers' :php:`log()` functionality or the TYPO3 Logging API for logging.

.. index:: PHP-API, Backend, FullyScanned
