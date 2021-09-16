.. include:: ../../Includes.txt

===============================================
Deprecation: #95254 - Two FlexFormTools methods
===============================================

See :issue:`95254`

Description
===========

Two detail methods of class :php:`FlexFormTools` have been marked as deprecated:

* :php:`TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools->getArrayValueByPath()`
* :php:`TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools->setArrayValueByPath()`


Impact
======

Calling the methods will trigger a deprecation level log entry.


Affected Installations
======================

Some instances may contain extensions calling above methods. The extension
scanner will find usages as weak match.


Migration
=========

The methods can be substituted with two counterparts from
:php:`TYPO3\CMS\Core\Utility\ArrayUtility`. They exist since v7 already. Their
signature is slightly different, but usages should be simple to adapt:

.. code-block:: php

    // before
    $value = $flexFormTools->getArrayValueByPath('search/path', $searchArray);
    // after
    $value = ArrayUtility::getValueByPath($searchArray, 'search/path');

    // before
    $flexFormTools->setArrayValueByPath('set/path', $dataArray, $value);
    // after
    $dataArray = ArrayUtility::setValueByPath($dataArray, 'set/path', $value);


.. index:: FlexForm, PHP-API, FullyScanned, ext:core
