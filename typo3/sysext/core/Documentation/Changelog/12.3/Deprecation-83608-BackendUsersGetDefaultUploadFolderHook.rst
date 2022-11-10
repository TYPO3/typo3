.. include:: /Includes.rst.txt

.. _deprecation-83608-1679521195:

================================================================
Deprecation: #83608 - Backend Users' getDefaultUploadFolder Hook
================================================================

See :issue:`83608`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getDefaultUploadFolder']` has been marked
as deprecated in favor of a new PSR-14 event :php:`AfterDefaultUploadFolderWasResolvedEvent`.

Along with the hook, the two methods:
* :php:`BackendUserAuthentication->getDefaultUploadFolder()`
* :php:`BackendUserAuthentication->getDefaultUploadTemporaryFolder()`
have been marked as internal, as they are not considered part of the public TYPO3 API anymore.


Impact
======

Using this hook will trigger a PHP deprecation notice every time the method 
:php:`BackendUserAuthentication->getDefaultUploadFolder()` is called,


Affected installations
======================

TYPO3 installations with special functionality in extensions using these methods or the hook.


Migration
=========

Migrate to the PSR-14 event :php:`AfterDefaultUploadFolderWasResolvedEvent` in your
custom extensions.

It is fired after various Page TsConfig settings have been applied and allows for more
fine-grained control.

.. index:: Backend, PHP-API, FullyScanned, ext:backend