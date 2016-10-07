
.. include:: ../../Includes.txt

==========================================================
Deprecation: #75327 - $TSFE->csConvObj and $TSFE->csConv()
==========================================================

See :issue:`75327`

Description
===========

The public property `csConvObj` and the public method `csConv()` inside the TypoScriptFrontendController PHP
class have been marked as deprecated.


Impact
======

Calling `$TSFE->csConv()` will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation which uses the property or the method directly.


Migration
=========

If a charset conversion is necessary, the conversion can be done directly by instantiating the charset converter class.

.. code-block:: php

	$from = 'iso-8859-15';
	/** @var \TYPO3\CMS\Core\Charset\CharsetConverter $charsetConverter */
	$charsetConverter = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
	$output = $charsetConverter->conv($str, $charsetConverter->parse_charset($from), 'utf-8');

.. index:: PHP-API, Frontend
