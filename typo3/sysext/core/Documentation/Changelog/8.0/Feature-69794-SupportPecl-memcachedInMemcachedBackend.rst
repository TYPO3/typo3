
.. include:: /Includes.rst.txt

============================================================
Feature: #69794 - Support pecl-memcached in MemcachedBackend
============================================================

See :issue:`69794`

Description
===========

Support for the PECL module "memcached" has been added to the MemcachedBackend of the Caching Framework.


Impact
======

The MemcachedBackend checks if either "memcache" or "memcached" is installed. If both plugins are installed, the
MemcachedBackend uses "memcache" over "memcached" to avoid being a breaking change. An integrator may set the option
`peclModule` to use the preferred PECL module.

Example code:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['my_memcached'] = [
		'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class
		'backend' => \TYPO3\CMS\Core\Cache\Backend\MemcachedBackend::class,
		'options' => [
			'peclModule' => 'memcached',
			'servers' => [
			   'localhost',
			   'server2:port'
			]
		]
	];

.. index:: PHP-API, LocalConfiguration
