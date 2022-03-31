.. include:: /Includes.rst.txt

============================================
Deprecation: #94665 - Wincache cache backend
============================================

See :issue:`94665`

Description
===========

The Caching Framework backend implementation :php:`TYPO3\CMS\Core\Cache\Backend\WincacheBackend`
is not maintained since Microsoft dropped its support: A PHP 7.4 compatible version
came long after PHP 7.4 release and there are no PHP 8.0 works in sight. This backend
in general found relatively little use and can be substituted with the well
maintained ApcuBackend key/value store on Windows platforms.

:php:`WincacheBackend` has been marked as deprecated and should not be used anymore.


Impact
======

The implementation has been marked as deprecated, usages trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Some instances hosted on Windows platform *may* use this cache backend. This can be
verified in the backend "Configuration" module, section "TYPO3_CONF_VARS", searching
for string "WincacheBackend".


Migration
=========

TYPO3 cache backend configuration is usually done in :file:`LocalConfiguration.php`.
Affected instances could switch to :php:`ApcuBackend` if the :php:`apcu` PHP module
is loaded, or alternatively to some other backend like :php:`RedisBackend`,
:php:`MemcachedBackend` or :php:`Typo3DatabaseBackend`, depending on the specific
cache size and usage characteristics.


:file:`LocalConfiguration.php` example before:

.. code-block:: php

    'SYS' => [
        'caching' => [
            'cacheConfigurations' => [
                'aCache' => [
                    'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\WincacheBackend',
    ...

:file:`LocalConfiguration.php` example after:

.. code-block:: php

    'SYS' => [
        'caching' => [
            'cacheConfigurations' => [
                'aCache' => [
                    'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\ApcuBackend',
    ...


In case this cache backend is still used for whatever reason and can't be dropped
easily, the class should be copied to an own extension having an own namespace. The
instance configuration needs to be adapted accordingly.

.. index:: LocalConfiguration, PHP-API, NotScanned, ext:core
