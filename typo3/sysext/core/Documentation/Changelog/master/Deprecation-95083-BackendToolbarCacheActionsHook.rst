.. include:: ../../Includes.txt

=======================================================
Deprecation: #95083 - Backend toolbar CacheActions hook
=======================================================

See :issue:`95083`

Description
===========

The TYPO3 Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']`
which can be used to modify the cache actions, shown in the TYPO3 Backend
top toolbar, has been deprecated.

The accompanied PHP Interface for the hook
:php:`TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface` has been
marked as deprecated as well.

Impact
======

If the hook is registered in a TYPO3 installation, a PHP deprecation
message is triggered. The extension scanner also detects any usage
of the deprecated interface as strong, and the definition of the
hook as weak match.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook.

Migration
=========

Migrate to the newly introduced `ModifyClearCacheActionsEvent` PSR-14 event.

.. index:: PHP-API, FullyScanned, ext:backend
