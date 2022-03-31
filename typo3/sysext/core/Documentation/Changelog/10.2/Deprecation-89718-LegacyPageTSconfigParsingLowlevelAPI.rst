.. include:: /Includes.rst.txt

==============================================================
Deprecation: #89718 - Legacy PageTSconfig parsing lowlevel API
==============================================================

See :issue:`89718`

Description
===========

Two new PHP API classes for retrieving and parsing TsConfig are
introduced:

- :php:`TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader`
- :php:`TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser`

As this API is more consistent, and flexible, as well as agnostic
of the current Context of backend or frontend, the following
functionality has been marked as deprecated:

- :php:`TYPO3\CMS\Core\Configuration\TsConfigParser`
- :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getRawPagesTSconfig()`


Impact
======

Instantiating the PHP class or the mentioned PHP method will trigger
a deprecation message.


Affected Installations
======================

TYPO3 Installations with extensions using the lowlevel API for handling PageTSconfig.


Migration
=========

Loading and parsing PageTSconfig on a low-level should be done via the new PHP classes:

- :php:`TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader`
- :php:`TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser`

Usages for fetching all available PageTS of a page/rootline in one large string:

.. code-block:: php

   $loader = GeneralUtility::makeInstance(PageTsConfigLoader::class);
   $tsConfigString = $loader->load($rootLine);


The string is parsed (and conditions are applied) with the Parser:

.. code-block:: php

      $parser = GeneralUtility::makeInstance(
       PageTsConfigParser::class,
       $typoScriptParser,
       $hashCache
   );
   $pagesTSconfig = $parser->parse(
       $tsConfigString,
       $conditionMatcher
   );

Extension developers should rely on this syntax rather than
on :php:`$GLOBALS['TSFE']->getPagesTSconfig()` or :php:`BackendUtility::getPagesTsConfig()`, or the deprecated method / class.

.. index:: PHP-API, TSConfig, FullyScanned, ext:core
