.. include:: /Includes.rst.txt

.. _deprecation-102821-1709843835:

================================================================
Deprecation: #102821 - ExtensionManagementUtility::addPItoST43()
================================================================

See :issue:`102821`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43()`
has been marked as deprecated in TYPO3 v13 and will be removed with TYPO3 v14.


Impact
======

Using the :php:`ExtensionManagementUtility::addPItoST43()` will raise a deprecation
level log entry and a fatal error in TYPO3 v14.


Affected installations
======================

Extensions using :php:`ExtensionManagementUtility::addPItoST43()` are affected:
Using :php:`ExtensionManagementUtility::addPItoST43()` triggers a deprecation level log message.
The extension scanner will find usages of :php:`ExtensionManagementUtility::addPItoST43()` as strong match.


Migration
=========

..  code-block:: php

    // Before:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('my_extkey', '', '_pi1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'tx_myextkey',
        'setup',
        'plugin.tx_myextkey_pi1.userFunc = MY\MyExtkey\Plugins\Plugin->main'
    );

    // After:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'my_extkey',
        'setup',
        'plugin.tx_myextkey_pi1 = USER_INT
         plugin.tx_myextkey_pi1.userFunc = MY\MyExtkey\Plugins\Plugin->main'
    );

.. index:: LocalConfiguration, PHP-API, FullyScanned, ext:core
