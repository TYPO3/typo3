
.. include:: /Includes.rst.txt

=====================================================================================
Deprecation: #71917 - Deprecate the argument 'hsc' for getLL, getLLL, sL and pi_getLL
=====================================================================================

See :issue:`71917`

Description
===========

The parameter :php:`$hsc` within the following methods of :php:`TYPO3\CMS\Lang\LanguageService` has been marked as deprecated:

* :php:`getLL()`
* :php:`getLLL()`
* :php:`sL()`

The parameter :php:`$hsc` within the following method of :php:`TYPO3\CMS\Frontend\Plugin\AbstractPlugin` has been marked as deprecated:

* :php:`pi_getLL()`


Impact
======

Directly or indirectly using any of the methods :php:`getLL()`, :php:`getLLL()`, :php:`sL()` or :php:`pi_getLL()` with the parameter :php:`$hsc` will trigger a deprecation log entry.


Affected Installations
======================

Any installation with a third-party extension calling one of the methods in its PHP code.


Migration
=========

If the return value of these methods is output in HTML context use :php:`htmlspecialchars` directly to properly escape the content.

.. index:: Frontend, Backend, PHP-API
