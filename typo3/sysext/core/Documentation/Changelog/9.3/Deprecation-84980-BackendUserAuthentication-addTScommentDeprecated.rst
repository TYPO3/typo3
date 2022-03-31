.. include:: /Includes.rst.txt

===============================================================
Deprecation: #84980 - BackendUserAuthentication->addTScomment()
===============================================================

See :issue:`84980`

Description
===========

Method :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->addTScomment()` has been marked as deprecated.


Impact
======

The method has been used to add comments to :typoscript:`TSconfig` at runtime, those
comments however are never shown in the TYPO3 backend.
Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances with extensions calling :php:`BackendUserAuthentication->addTScomment()`
will trigger a deprecation error.
It is however rather unlikely that extensions
rely on this widely unknown API method and method calls were mostly core internal.
The extension scanner should find possible usages within extensions.


Migration
=========

Drop the method call.

.. index:: Backend, PHP-API, TSConfig, FullyScanned
