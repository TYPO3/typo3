.. include:: /Includes.rst.txt

.. _breaking-97451:

=======================================================
Breaking: #97451 - Removed BackendController page hooks
=======================================================

See :issue:`97451`

Description
===========

The hooks :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess']`,
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['renderPreProcess']`, and
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['renderPostProcess']` have
been removed in favor of a new PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent`.

Additionally, the :php:`BackendController->addCss()` method has been removed without replacement,
as it is no longer used.

Impact
======

Any hook implementation registered is not executed anymore in
TYPO3 v12.0+. The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using this hook in custom extension code.

Migration
=========

The hooks are removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 event <../12.0/Feature-97451-PSR-14EventsForBackendPageController>`
as an improved replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
