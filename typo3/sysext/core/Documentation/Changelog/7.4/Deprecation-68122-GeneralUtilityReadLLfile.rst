
.. include:: ../../Includes.txt

==========================================================
Deprecation: #68122 - Deprecate GeneralUtility::readLLfile
==========================================================

See :issue:`68122`

Description
===========

Method `GeneralUtility::realLLfile()` was just a wrapper around LocalizationFactory
and has been marked as deprecated.


Impact
======

Extensions using `realLLfile()` to parse localization files should switch to
an instance of `LocalizationFactory`.


Affected Installations
======================

Extensions using `GeneralUtility::readLLfile()`


Migration
=========

A typical call now should look like:

.. code-block:: php

		/** @var $languageFactory \TYPO3\CMS\Core\Localization\LocalizationFactory */
		$languageFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LocalizationFactory::class);
		$languageFactory->getParsedData($fileToParse, $language, $renderCharset, $errorMode);


.. index:: PHP-API
