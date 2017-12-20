
.. include:: ../../Includes.txt

========================================
Deprecation: #67471 - Deprecate init.php
========================================

See :issue:`67471`

Description
===========

In order to move all unneeded files from typo3/, the often used init.php has been deprecated in favor of using the
bootstrap initialization code directly in the TYPO3 Backend.


Impact
======

All entry points from third-party extensions using init.php will now throw a deprecation warning.


Affected Installations
======================

All instances having extensions that include init.php when not using the mod.php for modules or ajax calls.


Migration
=========

Use the following code instead of the init.php inclusion if you still need custom entry points:

.. code-block:: php

	define('TYPO3_MODE', 'BE');

	require __DIR__ . '/sysext/core/Classes/Core/Bootstrap.php';
	\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->run('typo3/');


If using a module, use the mod.php to register your own module.


.. index:: PHP-API, Backend
