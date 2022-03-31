.. include:: /Includes.rst.txt

====================================================================================
Deprecation: #95326 - Various "getInstance()" static methods on singleton interfaces
====================================================================================

See :issue:`95326`

Description
===========

A few classes within TYPO3 Core have a static method :php:`getInstance()`
which acts as a wrapper for the constructor which originally was meant as
a performance improvement as pseudo-singleton concept in TYPO3 v6.

With dependency injection, these classes can be injected or instantiated
directly without any performance penalties.

Therefore the following methods have been marked as deprecated:

*   :php:`TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance()`
*   :php:`TYPO3\CMS\Core\Resource\Index\FileIndexRepository::getInstance()`
*   :php:`TYPO3\CMS\Core\Resource\Index\MetaDataRepository::getInstance()`
*   :php:`TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry::getInstance()`
*   :php:`TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance()`
*   :php:`TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance()`
*   :php:`TYPO3\CMS\Form\Service\TranslationService::getInstance()`
*   :php:`TYPO3\CMS\T3editor\Registry\AddonRegistry::getInstance()`
*   :php:`TYPO3\CMS\T3editor\Registry\ModeRegistry::getInstance()`


Impact
======

Calling the methods directly in third-party PHP code will trigger a PHP
:php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with custom PHP code calling the methods are affected.


Migration
=========

Check :guilabel:`Admin Tools > Upgrade > Scan Extension Files` if your
installation is affected and replace calls with constructor injections via
dependency injection if possible, or use
:php:`GeneralUtility::makeInstance()` instead.

.. index:: PHP-API, PartiallyScanned, ext:core
