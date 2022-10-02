.. include:: /Includes.rst.txt

.. _breaking-97201:

==============================================================
Breaking: #97201 - Removed hook for new content element wizard
==============================================================

See :issue:`97201`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook']`
has been removed in favor of a new PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent`.

Additionally, the :php:`params` property of a wizard item has been
removed, since it just duplicated the default values, configured with
:php:`tt_content_defValues` and therefore previously required extension
authors to provide the same information twice in two different formats.

.. note::

    The public methods :php:`getPageInfo()`, :php:`getColPos()`,
    :php:`getSysLanguage()` and :php:`getUidPid()` have been removed
    from the internal :php:`NewContentElementController` class, since
    they were only added for the use in the now removed hook. This
    information is now directly available in the new PSR-14 event.

Impact
======

Any hook implementation registered is not executed anymore
in TYPO3 v12.0+.

The :php:`params` property on a wizard item is no longer evaluated.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook.

TYPO3 installations setting the :php:`params` property on a wizard item.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 event <../12.0/Feature-97201-PSR-14EventForModifyingNewContentElementWizardItems>`
to allow greater influence in the functionality.

Migrate the :php:`params` property to :php:`tt_content_defValues` or just
remove :php:`params` in case the information had already been configured
for both properties.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
