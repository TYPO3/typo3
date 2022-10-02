.. include:: /Includes.rst.txt

.. _deprecation-96996:

===============================================================================
Deprecation: #96996 - Deprecate TypoScriptFrontendController->checkEnableFields
===============================================================================

See :issue:`96996`

Description
===========

The :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkEnableFields()`
method has been deprecated in favour of the new :php:`TYPO3\CMS\Core\Domain\Access\RecordAccessVoter`
component.

Impact
======

:php:`TypoScriptFrontendController->checkEnableFields()` will trigger a PHP :php:`E_USER_DEPRECATED` error
when called. The extension scanner will report usages as weak match.

Affected Installations
======================

All installations calling :php:`TypoScriptFrontendController->checkEnableFields()`
in custom extension code.

Migration
=========

Replace all usages of the deprecated method. Use the :php:`RecordAccessVoter`
component instead, e.g. :php:`RecordAccessVoter->accessGranted()`.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
