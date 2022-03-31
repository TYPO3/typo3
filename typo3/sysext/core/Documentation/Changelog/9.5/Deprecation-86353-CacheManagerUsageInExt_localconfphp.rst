.. include:: /Includes.rst.txt

=============================================================
Deprecation: #86353 - CacheManager usage in ext_localconf.php
=============================================================

See :issue:`86353`

Description
===========

Usage of :php:`\TYPO3\CMS\Core\Cache\CacheManager->getCache()` during
:file:`ext_localconf.php` loading phase has been marked as deprecated.


Impact
======

Using :php:`\TYPO3\CMS\Core\Cache\CacheManager->getCache()` in
:file:`ext_localconf.php` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations with third party extensions that use
:php:`\TYPO3\CMS\Core\Cache\CacheManager->getCache()` in
:file:`ext_localconf.php`.


Migration
=========

Load caches on demand, when actually needed.

.. index:: PHP-API, NotScanned
