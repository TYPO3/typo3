.. include:: /Includes.rst.txt

.. _breaking-99898-1705657466:

========================================================================
Breaking: #99898 - Continuous array keys from GeneralUtility::intExplode
========================================================================

See :issue:`99898`


Description
===========

When the method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode()`
is called with the parameter :php:`$removeEmptyEntries` set to :php:`true`, the
array keys are now continuous.

Previously, the array had gaps in the keys in the places where empty values were
removed. This behavior had been an undocumented side-effect of the
implementation. It is now changed to always return an array with continuous
integer array keys (i.e., a list) to reduce surprising behavior.

Before this change (TYPO3 v12):

.. code-block:: php

    GeneralUtility::intExplode(',', '1,,3', true);
    // Result: [0 => 1, 2 => 3]

After this change (TYPO3 v13):

.. code-block:: php

    GeneralUtility::intExplode(',', '1,,3', true);
    // Result: [0 => 1, 1 => 3]


Impact
======

Calling :php:`GeneralUtility::intExplode()` with the parameter
:php:`$removeEmptyEntries` set to :php:`true` and relying on gaps in the keys of
the resulting array keys may lead to unexpected results.


Affected installations
======================

Custom extensions that rely on the array keys of the result of
:php:`GeneralUtility::intExplode()` to have gaps in the keys.


Migration
=========

Adapt your code to not rely on gaps in the keys anymore.


.. index:: PHP-API, NotScanned, ext:core
