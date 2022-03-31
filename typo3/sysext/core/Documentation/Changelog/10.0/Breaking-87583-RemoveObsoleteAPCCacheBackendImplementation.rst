.. include:: /Includes.rst.txt

===================================================================
Breaking: #87583 - Remove obsolete APC Cache Backend implementation
===================================================================

See :issue:`87583`

Description
===========

The Caching framework backend implementation :php:`TYPO3\CMS\Core\Cache\Backend\ApcBackend` has
been removed. The APCu PHP extension has superseded in PHP 7.x.

Impact
======

The PHP APC extension works until PHP 5.x. APCu can be used as "drop-in" replacement since TYPO3 8
LTS which supports PHP 7.0+.

Affected Installations
======================

Any installation which has been updated, and any legacy APC cache backend is configured (see
:file:`LocalConfiguration.php`).

Migration
=========

Use APCu implementation, which is implemented via :php:`TYPO3\CMS\Core\Cache\Backend\ApcuBackend`
instead of :php:`TYPO3\CMS\Core\Cache\Backend\ApcBackend` in your caching framework configuration.

Example before:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['backend'] = \TYPO3\CMS\Core\Cache\Backend\ApcBackend::class;`

Example after:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['backend'] = \TYPO3\CMS\Core\Cache\Backend\ApcuBackend::class;`


.. index:: Backend, PHP-API, ext:core, NotScanned
