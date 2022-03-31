.. include:: /Includes.rst.txt

=======================================
Deprecation: #94664 - Pdo cache backend
=======================================

See :issue:`94664`

Description
===========

The Caching Framework backend implementation :php:`TYPO3\CMS\Core\Cache\Backend\PdoBackend`
is superseded by the :php:`TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend` since
introduction of Doctrine DBAL. There is little reason to use :php:`PdoBackend` instead
of the :php:`Typo3DatabaseBackend` and the latter is optimized much better.

:php:`PdoBackend` has thus been marked as deprecated and should not be used anymore.


Impact
======

The implementation has been marked as deprecated, usages trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Some instances *may* use this cache backend, but chances are low. This can
be verified in the backend "Configuration" module, section "TYPO3_CONF_VARS",
searching for string "PdoBackend".


Migration
=========

TYPO3 cache backend configuration is usually done in :file:`LocalConfiguration.php`.
Affected instances should switch to :php:`Typo3DatabaseBackend` and eventually update
database schema.

:file:`LocalConfiguration.php` example before:

.. code-block:: php

    'SYS' => [
        'caching' => [
            'cacheConfigurations' => [
                'aCache' => [
                    'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\PdoBackend',
    ...

:file:`LocalConfiguration.php` example after:

.. code-block:: php

    'SYS' => [
        'caching' => [
            'cacheConfigurations' => [
                'aCache' => [
                    'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
    ...


In case this cache backend is still used for whatever reason and can't be dropped
easily, the class should be copied to an own extension having an own namespace. The
instance configuration needs to be adapted accordingly. Note there is an additional
schema definition file in :file:`EXT:core/Resources/Private/Sql/Cache/Backend/PdoBackendCacheAndTags.sql`,
that should be copied along the way with it's location being updated in the cache class.

.. index:: LocalConfiguration, PHP-API, NotScanned, ext:core
