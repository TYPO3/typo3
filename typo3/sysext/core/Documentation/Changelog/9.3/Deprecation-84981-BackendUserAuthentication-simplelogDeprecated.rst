.. include:: ../../Includes.txt

============================================================
Deprecation: #84981 - BackendUserAuthentication->simplelog()
============================================================

See :issue:`84981`

Description
===========

Method :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->simplelog()` has been marked as deprecated.


Impact
======

The method has been a shortcut to :php:`writelog()` which can be used instead.


Affected Installations
======================

Instances with extensions that call this method. Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.
The extension scanner should find possible usages.


Migration
=========

Use :php:`writelog()` instead or - even better - use the logging framework to log messages.

.. index:: Backend, PHP-API, FullyScanned