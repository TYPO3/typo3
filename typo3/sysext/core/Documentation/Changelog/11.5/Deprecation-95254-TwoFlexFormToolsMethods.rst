.. include:: /Includes.rst.txt

===============================================
Deprecation: #95254 - Two FlexFormTools methods
===============================================

See :issue:`95254`

Description
===========

Two detail methods of class :php:`TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools`
have been marked as deprecated:

*   :php:`FlexFormTools->getArrayValueByPath()`
*   :php:`FlexFormTools->setArrayValueByPath()`


Impact
======

Calling the methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Some instances may contain extensions calling above methods. The extension
scanner will find usages as weak match.


Migration
=========

The methods can be substituted with two counterparts from
:php:`TYPO3\CMS\Core\Utility\ArrayUtility`. They exist since TYPO3 v7 already. Their
signature is slightly different, but usages should be simple to adapt:

.. code-block:: php

    // use TYPO3\CMS\Core\Utility\ArrayUtility;
    // before
    $value = $flexFormTools->getArrayValueByPath('search/path', $searchArray);
    // after
    $value = ArrayUtility::getValueByPath($searchArray, 'search/path');

    // before
    $flexFormTools->setArrayValueByPath('set/path', $dataArray, $value);
    // after
    $dataArray = ArrayUtility::setValueByPath($dataArray, 'set/path', $value);


.. index:: FlexForm, PHP-API, FullyScanned, ext:core
