.. include:: /Includes.rst.txt

.. _breaking-96899:

========================================================
Breaking: #96899 - "displayWarningMessages" hook removed
========================================================

See :issue:`96899`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages']`
has been removed in favor of a new PSR-14 event
:php:`\TYPO3\CMS\Backend\Controller\Event\ModifyGenericBackendMessagesEvent`.

The hook was used to display messages in the About module.

Impact
======

Registered hooks are not executed anymore.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook, which is very
unlikely. The extension scanner will report possible usages.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 event <Feature-96899-NewPSR-14EventModifyGenericBackendMessagesEvent>`
as a direct replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
