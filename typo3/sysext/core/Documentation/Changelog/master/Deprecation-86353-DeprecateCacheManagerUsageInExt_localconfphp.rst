.. include:: ../../Includes.txt

=======================================================================
Deprecation: #86353 - Deprecate CacheManager usage in ext_localconf.php
=======================================================================

See :issue:`86353`

Description
===========

:php:`\TYPO3\CMS\Core\Cache\CacheManager->getCache()` usage during
:file:`ext_localconf.php` loading phase has been deprecated.


Impact
======

Using :php:`\TYPO3\CMS\Core\Cache\CacheManager->getCache()` in
:file:`ext_localconf.php` will log a deprecation warning.


Affected Installations
======================

All installations with third party extensions that use
:php:`\TYPO3\CMS\Core\Cache\CacheManager->getCache()` in
:file:`ext_localconf.php`.


Migration
=========

Load caches on demand, when actually needed.

.. index:: PHP-API, NotScanned
