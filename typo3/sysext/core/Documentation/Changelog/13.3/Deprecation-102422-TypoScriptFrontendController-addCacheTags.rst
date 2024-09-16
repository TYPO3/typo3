.. include:: /Includes.rst.txt

.. _deprecation-102422-1700563266:

============================================================================================
Deprecation: #102422 - TypoScriptFrontendController->addCacheTags() and ->getPageCacheTags()
============================================================================================

See :issue:`102422`

Description
===========

The methods :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->addCacheTags()` and
:php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPageCacheTags()`
have been marked as deprecated.


Impact
======

Calling the methods
:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->addCacheTags()`
and
:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPageCacheTags()`
will trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations calling
:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->addCacheTags()`
or
:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPageCacheTags()`.


Migration
=========

.. code-block:: php

    // Before
    $GLOBALS['TSFE']->addCacheTags([
        'tx_myextension_mytable_123',
        'tx_myextension_mytable_456'
    ]);

    // After
    use TYPO3\CMS\Core\Cache\CacheTag;

    $request->getAttribute('frontend.cache.collector')->addCacheTags(
        new CacheTag('tx_myextension_mytable_123', 3600),
        new CacheTag('tx_myextension_mytable_456', 3600)
    );

.. code-block:: php

    // Before
    $GLOBALS['TSFE']->getPageCacheTags();

    // After
    $request->getAttribute('frontend.cache.collector')->getCacheTags();

.. index:: PHP-API, FullyScanned, ext:core
