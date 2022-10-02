.. include:: /Includes.rst.txt

.. _breaking-98304:

===================================================================
Breaking: #98304 - Removed hook for modifying edit form user access
===================================================================

See :issue:`98304`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck']`
has been removed in favor of a new PSR-14 event
:php:`\TYPO3\CMS\Backend\Form\Event\ModifyEditFormUserAccessEvent`.

Additionally, the corresponding :php:`TYPO3\CMS\Backend\Form\Exception\AccessDeniedHookException`,
which had been thrown in case a hook denied the user access has been replaced
by the :php:`TYPO3\CMS\Backend\Form\Exception\AccessDeniedListenerException`.

Impact
======

Any hook implementation registered is not executed anymore in
TYPO3 v12.0+. The :php:`AccessDeniedHookException` is not thrown
anymore. The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using this hook or the exception in custom
extension code.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 event <../12.0/Feature-98304-PSR-14EventForModifyingEditFormUserAccess>`
as an improved replacement, providing an object-oriented approach
as well as built-in convenience features and an increased amount
of context information.

Any usage of the :php:`AccessDeniedHookException` should be replaced by the
:php:`AccessDeniedListenerException`.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
