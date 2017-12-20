
.. include:: ../../Includes.txt

============================================
Breaking: #76802 - Drop xcache cache backend
============================================

See :issue:`76802`

Description
===========

The `xcache` core cache backend has been dropped because PHP7 does no longer support xcache.


Impact
======

Instances will throw an exception or a fatal error using a cache with this backend configured.


Affected Installations
======================

Instances that still use a configuration in `LocalConfiguration.php` or `AdditionalConfiguration.php` like:

.. code-block:: php

    'SYS' => [
        'caching' => [
            'cacheConfigurations' => [
                'aCache' => [
                    'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\XcacheBackend',


Migration
=========

Affected instances must switch to a different cache backend, `APCU` `PHP` module with `ApcuBackend`
provides the same local server in memory characteristics like `xcache` and should be considered as alternative.

.. index:: LocalConfiguration
