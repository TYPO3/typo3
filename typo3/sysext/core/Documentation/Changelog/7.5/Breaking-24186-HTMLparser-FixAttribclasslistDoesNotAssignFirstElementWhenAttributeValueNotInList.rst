
.. include:: ../../Includes.txt

========================================================================================================================
Breaking: #24186 - HTMLparser - fixAttrib.['class'].list does not assign first element, when attribute value not in list
========================================================================================================================

See :issue:`24186`


Description
===========

The HTMLparser now assigns the first class of `fixAttrib.class.list` when none of the given class name values
are found in the configured list. Until now the class attribute of the rendered HTML tag was just empty in that case.


Impact
======

A HTML element that had no class before could now have been assigned a class.


Migration
=========

Add a class from the configured list to the HTML element or add a class at the first position of `fixAttrib.class.list`.


.. index:: TypoScript, RTE
