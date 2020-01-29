.. include:: ../../Includes.txt

===============================================
Deprecation: #90258 - Simplified RTE Parser API
===============================================

See :issue:`90258`

Description
===========

The PHP class `RteHtmlParser` which is used to transform RTE-based
textarea fields from the database to the configured Rich Text Editor, and back, has a new simplified API. For this reason,
the two methods :php:`TYPO3\CMS\Core\Html\RteHtmlParser->init()` and 
:php:`TYPO3\CMS\Core\Html\RteHtmlParser->RTE_transform()` have been
deprecated.


Impact
======

Calling any of the methods will trigger a PHP deprecation warning.


Affected Installations
======================

TYPO3 installations with extensions dealing with extracting or adding content such as "l10nmgr", or any custom extension using
the methods.


Migration
=========

The PHP method `init()` can be removed without substitution, as it
serves no purpose anymore.

The PHP method `RTE_transform()` now has two methods as substitute,
depending on the direction which is necessary. This was previously
done in the third method argument ("rte" and "db"):

- :php:`transformTextForRichTextEditor($content, $configuration)`
- :php:`transformTextForPersistence($content, $configuration)`

The second argument `$configuration` is now the `processing` configuration (`proc`) of the RTE configuration.

.. index:: RTE, FullyScanned, ext:core