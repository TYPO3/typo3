
.. include:: /Includes.rst.txt

======================================================================
Feature: #70078 - Extensions can provide a class map for class loading
======================================================================

See :issue:`70078`

Description
===========

With the old class loader it was possible for extension authors
to register several classes in an ext_autoload.php file.

This possibility was completely removed with introduction of composer class loading.
In composer mode, one can fully benefit from composer and its class loading options.
However TYPO3 installations in non composer mode (extracted and symlinked
archive of sources), lack this functionality completely.

Now it is possible to provide a class map section in either the composer.json file
or the ext_emconf.php file. This section will be evaluated and used also in non composer mode.

Example ext_emconf.php file:

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
		),
		'classmap' =>
		array(
		  'Resources/PHP/Libs'
		)
	  )
	);

In the example configuration the path `Resources/PHP/Libs` is parsed for PHP files which are automatically added
to the class loader.

Impact
======

Extensions that target TYPO3 6.2 LTS and 7 LTS can now provide a class map in ext_emconf.php which is only evaluated in
TYPO3 7 LTS and an ext_autoload.php which is only evaluated in 6.2 LTS for maximum flexibility and compatibility.


.. index:: PHP-API
