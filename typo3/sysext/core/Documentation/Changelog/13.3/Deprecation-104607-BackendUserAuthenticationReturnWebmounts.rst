.. include:: /Includes.rst.txt

.. _deprecation-104607-1723556132:

==================================================================
Deprecation: #104607 - BackendUserAuthentication:returnWebmounts()
==================================================================

See :issue:`104607`

Description
===========

Method :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::returnWebmounts()` has
been marked as deprecated and will be removed with TYPO3 v14.

Method :php:`BackendUserAuthentication::getWebmounts()` was
introduced as substitution. It returns a unique list of integer uids
instead of a list of strings, which is more type safe.
Superfluous calls to array_unique() can be removed since the uniqueness
is now guaranteed by BackendUserAuthentication::getWebmounts().

Impact
======

Calling :php:`BackendUserAuthentication::returnWebmounts()` will trigger a PHP
deprecation warning.


Affected installations
======================

All installations using :php:`BackendUserAuthentication::returnWebmounts()`
are affected.


Migration
=========

Existing calls to  :php:`BackendUserAuthentication::returnWebmounts()` should
be replaced by :php:`BackendUserAuthentication::getWebmounts()`.

If third party extensions convert the previous result array from an array of
strings to an array of integers, this can be skipped. In addition
superfluous calls to array_unique() can be removed since the uniqueness
is now guaranteed by BackendUserAuthentication::getWebmounts().

.. index:: PHP-API, TCA, FullyScanned, ext:core
