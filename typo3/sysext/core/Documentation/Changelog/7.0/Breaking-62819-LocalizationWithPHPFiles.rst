
.. include:: ../../Includes.txt

==================================================
Breaking: #62819 - Remove php Localization Support
==================================================

See :issue:`62819`

Description
===========

The support for php files in localization, e.g. locallang.php files was deprecated with TYPO3 CMS 4.6. All translations
are done with XLF in the core, XML files are still supported.

The parsing of PHP localization files is now disabled by default, the parsing class is now deprecated.


Impact
======

Extensions using locallang.php files for translation will not show labels anymore.


Affected installations
======================

All installations with third-party extensions using locallang.php translation files.


Migration
=========

Third-party extensions should migrate their translation files to the XLIFF format (XLF file extension). The extension
development extension (Extension Key "extdeveval") can be used to transform locallang.php files to according XLF files.

Until this is done, it is possible to enable this option again by adding the following lines to
typo3conf/AdditionalConfiguration.php:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'] = 'xlf,xml,php';
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['parser']['php'] = 'TYPO3\\CMS\\Core\\Localization\\Parser\\LocallangArrayParser';


.. index:: PHP-API, Backend, Frontend, LocalConfiguration
