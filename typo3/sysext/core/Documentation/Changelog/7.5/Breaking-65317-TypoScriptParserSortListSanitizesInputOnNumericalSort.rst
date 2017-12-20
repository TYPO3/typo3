
.. include:: ../../Includes.txt

==============================================================================
Breaking: #65317 - TypoScriptParser sortList sanitizes input on numerical sort
==============================================================================

See :issue:`65317`

Description
===========

When calling the `:= sortList()` with a "numeric" modifier of the TypoScript parser with a string, the `sort()` method
differs between PHP versions. In order to make this behavior more strict, a check is done before the elements are
sorted to only have numeric values in the list, otherwise an Exception is thrown.


Impact
======

An exception is thrown if non-numerical values are given for a numeric sort in TypoScripts `sortList`.


Affected Installations
======================

All installations using `sortList` numeric with non-numerical values.


Migration
=========

Either remove the non-numerical values from the list or change the sort order to be non-numerical (ascending / descending).


.. index:: TypoScript, Frontend
