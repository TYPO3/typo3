.. include:: /Includes.rst.txt

.. _breaking-102745-1705054472:

======================================================
Breaking: #102745 - Removed ContentObject stdWrap hook
======================================================

See :issue:`102745`

Description
===========

The ContentObject stdWrap hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']`
has been removed in favor of the more powerful PSR-14 events:

* :php:`\TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapFunctionsInitializedEvent`
* :php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsInitializedEvent`
* :php:`\TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapFunctionsExecutedEvent`
* :php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsExecutedEvent`

Impact
======

Any hook implementation registered is not executed anymore
in TYPO3 v13.0+. The extension scanner will report usages.


Affected installations
======================

TYPO3 installations with custom extensions using the hook.


Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v12 (using the hook) and v13+ (using the new events)
when implementing the events as well without any further deprecations.
Use the :doc:`PSR-14 events <../13.0/Feature-102745-PSR-14EventsForModifyingContentObjectStdWrapFunctionality>`
to allow greater influence in the functionality.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
