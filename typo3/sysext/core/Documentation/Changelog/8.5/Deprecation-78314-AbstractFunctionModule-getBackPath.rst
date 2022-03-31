.. include:: /Includes.rst.txt

=========================================================
Deprecation: #78314 - AbstractFunctionModule->getBackPath
=========================================================

See :issue:`78314`

Description
===========

The protected method :php:`AbstractFunctionModule->getBackPath()` has been marked as deprecated, as it is not needed anymore.


Impact
======

Calling the PHP method will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 extension with a PHP class extending the `AbstractFunctionModule` and calling the method above.


Migration
=========

As the method always returns an empty string (since the `backPath` functionality is not needed anymore) the PHP call can be removed.

.. index:: Backend, PHP-API
