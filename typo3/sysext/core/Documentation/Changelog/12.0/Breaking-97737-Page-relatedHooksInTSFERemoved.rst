.. include:: /Includes.rst.txt

.. _breaking-97737-1654595331:

=====================================================
Breaking: #97737 - Page-related hooks in TSFE removed
=====================================================

See :issue:`97737`

Description
===========

The following hooks, which were executed during the process of resolving page
details of a frontend request have been removed:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PreProcessing']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['fetchPageId-PostProcessing']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess']`

They have been replaced by improved PSR-14 events.

Impact
======

Extensions that hook into these places are not executing the PHP-code anymore.

Affected installations
======================

TYPO3 installations with extensions using one of the hooks.

Check the "Configuration" module to see if your TYPO3 installation is using
one of the hooks by browsing :php:`$TYPO3_CONF_VARS[SC_OPTIONS]` or using the
Extension Scanner.

Migration
=========

The hooks are removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 events <../12.0/Feature-97737-PSR-14EventsWhenPageRootlineInFrontendIsResolved>`

* :php:`BeforePageIsResolvedEvent`
* :php:`AfterPageWithRootLineIsResolvedEvent`
* :php:`AfterPageAndLanguageIsResolvedEvent`

as an improved replacement.

.. index:: Frontend, FullyScanned, ext:frontend
