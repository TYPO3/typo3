.. include:: /Includes.rst.txt

.. _deprecation-103752-1714304437:

========================================================================================
Deprecation: #103752 - Obsolete `$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']`
========================================================================================

See :issue:`103752`

Description
===========

Configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']`
has been obsoleted, its handling has been removed with TYPO3 core v13.2.

This option is well-known to integrators who add relations to the TCA :sql:`pages`
table: It triggers relation resolving of page relations for additional fields when
rendering a frontend request in default language. The most common usage is
TypoScript "slide".

Impact
======

Integrators can simply forget about this option: Relations of table :sql:`pages`
are now always resolved with nearly no performance penalty in comparison to not
having them resolved.

Affected installations
======================

Many instances add additional relations to the :sql:`pages` table to then add
this field in :php:`addRootLineFields`. This option is no longer evaluated,
relation fields attached to :sql:`pages` are always resolved in frontend.

There should be little to no extensions using this option directly, since it was
an internal option of class :php:`RootlineUtility`. Extensions using
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']` *may* trigger a
PHP warning level error since that array key has been removed. The extension scanner
is configured to locate such usages.

Migration
=========

The option is no longer evaluated in TYPO3 core. It is removed from
:php:`settings.php` during upgrade, if given.


.. index:: Frontend, LocalConfiguration, PHP-API, FullyScanned, ext:core
