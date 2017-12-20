.. include:: ../../Includes.txt

=================================================================
Deprecation: #77732 - Deprecate methods of Extbase's ArrayUtility
=================================================================

See :issue:`77732`

Description
===========

The class :php:`\TYPO3\CMS\Extbase\Utility\ArrayUtility` has been marked as deprecated.


Impact
======

Calling any of the methods within the static class will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation calling the methods of that PHP class.


Migration
=========

A migration is available for the following methods:

- :php:`integerExplode`: Use :php:`GeneralUtility::intExplode`
- :php:`trimExplode`: Use :php:`GeneralUtility::trimExplode`
- :php:`arrayMergeRecursiveOverrule`: Use :php:`\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule` or :php:`array_replace_recursive`
- :php:`getValueByPath`: Use :php:`\TYPO3\CMS\Core\Utility\ArrayUtility::getValueByPath`
- :php:`setValueByPath`: Use :php:`\TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath`
- :php:`unsetValueByPath`: Use :php:`\TYPO3\CMS\Core\Utility\ArrayUtility::removeByPath`
- :php:`sortArrayWithIntegerKeys`: Use :php:`\TYPO3\CMS\Core\Utility\ArrayUtility::sortArrayWithIntegerKeys`

.. index:: Backend, ext:extbase, PHP-API
