.. include:: ../../Includes.txt

===================================================
Feature: #83350 - Add recursive filtering of arrays
===================================================

See :issue:`83350`


Description
===========

The new method :php:`\TYPO3\CMS\Core\Utility\ArrayUtility::filterRecursive()` has been added as an enhancement to the
`PHP function`_ :php:`array_filter()` to filter multidimensional arrays.

The method :php:`ArrayUtility::filterRecursive()` behaves just like :php:`array_filter()` and if no callback is defined,
values are removed if they equal to boolean :php:`false`. See `converting to boolean`_.

.. _`PHP function`: https://secure.php.net/manual/en/function.array-filter.php
.. _`converting to boolean`: https://secure.php.net/manual/en/language.types.boolean.php#language.types.boolean.casting


Impact
======

Arrays can be filtered recursive using the new method.

.. index:: PHP-API
