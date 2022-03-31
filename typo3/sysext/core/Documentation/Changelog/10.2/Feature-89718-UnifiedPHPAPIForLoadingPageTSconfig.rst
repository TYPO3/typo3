.. include:: /Includes.rst.txt

==========================================================
Feature: #89718 - Unified PHP API for loading PageTSconfig
==========================================================

See :issue:`89718`

Description
===========

Most parts of TYPO3 Core share duplicate or similar functionality in
Frontend or Backend context. One of that is the loading and parsing
of PageTSconfig, the configuration syntax for various places in
TYPO3 Backend, which can also be used to define Backend Layouts.

In order to streamline this functionality, the loading process of
gathering all data from a rootline of a page is now simplified in
a new :php:`PageTsLoader` PHP class.

Additionally, parsing, and additional matching against conditions,
which was added later-on in 2009 and put on top, is now separated
properly, building a truly separation of concerns for compiling
and parsing TSconfig. This is put in the :php:`PageTsConfigParser` PHP class.


Impact
======

When there is the necessity for fetching and loading PageTSconfig,
it is recommended for extension developers to make use of both new
PHP classes:

- :php:`TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader`
- :php:`TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser`

Usages for fetching all available PageTS in one large string (not parsed yet)::

    $loader = GeneralUtility::makeInstance(PageTsConfigLoader::class);
    $tsConfigString = $loader->load($rootLine);


The string can then be put in proper TSconfig array syntax::

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
on :php:`$GLOBALS['TSFE']->getPagesTSconfig()` or :php:`BackendUtility::getPagesTsConfig()`.

.. index:: PHP-API, TSConfig, ext:core
