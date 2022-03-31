.. include:: /Includes.rst.txt

=============================================
Breaking: #87558 - Consolidate extbase caches
=============================================

See :issue:`87558`

Description
===========

The caches of extbase have been consolidated as both of them shared the same caching frontend.
Cache identifiers `extbase_reflection` and `extbase_datamapfactory_datamap` do no longer exist.

A single cache `extbase` is pre-configured and used for class schemata and data maps instead.


Impact
======

Adjusting the cache configuration of either `extbase_reflection`
or `extbase_datamapfactory_datamap` will no longer have any effect.

The installation may throw an error depending on the php error level configuration, if the no longer existing
cache keys are written to without initializing them first.

The following global settings do no longer exist:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_reflection']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_datamapfactory_datamap']`

The following code code might throw an error depending on the php error level configuration:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['SYS']['cacheConfigurations']['extbase_reflection']['backend'] = \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;


Affected Installations
======================

All installations that override the configuration of the caches `extbase_reflection` and `extbase_datamapfactory_datamap`.


Migration
=========

Override new cache `extbase` in the same manner the former caches were overridden.

.. index:: PHP-API, FullyScanned, ext:extbase
