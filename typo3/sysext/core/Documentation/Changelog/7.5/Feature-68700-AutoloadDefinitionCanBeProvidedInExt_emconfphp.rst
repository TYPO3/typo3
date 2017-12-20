
.. include:: ../../Includes.txt

=======================================================================
Feature: #68700 - Autoload definition can be provided in ext_emconf.php
=======================================================================

See :issue:`68700`

Description
===========

It is now possible for extensions to provide one or more PSR-4 definitions,
in the ext_emconf.php file.

While it was possible to define a psr-4 section in a composer.json before already, now it is also
possible to define an autoload/psr-4 section in the ext_emconf.php file as well, so that extension authors
do not need to provide a composer.json just for that any more.

This is the new recommended way to register classes for TYPO3.

Example ext_emconf.php:

.. code-block:: php

	<?php
	$EM_CONF[$_EXTKEY] = array (
	  'title' => 'Extension skeleton for TYPO3 7',
	  'description' => 'Description for ext',
	  'category' => 'Example Extensions',
	  'author' => 'Helmut Hummel',
	  'author_email' => 'info@helhum.io',
	  'author_company' => 'helhum.io',
	  'shy' => '',
	  'priority' => '',
	  'module' => '',
	  'state' => 'stable',
	  'internal' => '',
	  'uploadfolder' => '0',
	  'createDirs' => '',
	  'modify_tables' => '',
	  'clearCacheOnLoad' => 0,
	  'lockType' => '',
	  'version' => '0.0.1',
	  'constraints' =>
	  array (
		'depends' =>
		array (
		  'typo3' => '7.5.0-7.99.99',
		),
		'conflicts' =>
		array (
		),
		'suggests' =>
		array (
		),
	  ),
	  'autoload' =>
	  array(
		'psr-4' =>
		array(
		  'Helhum\\ExtScaffold\\' => 'Classes'
		)
	  )
	);


Impact
======

Without providing an autoload section, TYPO3 scans the complete extension directory for PHP class files and registers them all.
This includes test classes or classes of third party libraries, which might lead to unexpected results.

Therefore it is recommended to provide such an autoload section in an extension. It will be ignored in older TYPO3 versions, so
there will be no issue with backwards compatibility.


.. index:: PHP-API
