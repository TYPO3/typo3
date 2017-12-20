.. include:: ../../Includes.txt

=======================================================================================================
Breaking: #78383 - pages, tt_content, sys_file_metadata have been removed from defaultCategorizedTables
=======================================================================================================

See :issue:`78383`

Description
===========

The tables `pages`, `tt_content` and `sys_file_metadata` have been removed from `defaultCategorizedTables`. 
For these tables the core API `\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable` would be
executed to define a common position of the categories field.


Impact
======

It is no longer possible to remove the category field for these tables by reset the configuration.


Affected Installations
======================

Any TYPO3 instance that reset the configuration value.


Migration
=========

None.

Use PageTSConfig  to disable the field:

.. code-block:: typoscript

    TCEFORM.pages.categories.disabled = 1
    TCEFORM.tt_content.categories.disabled = 1
    TCEFORM.sys_file_metadata.categories.disabled = 1

.. index:: LocalConfiguration, TSConfig, PHP-API
