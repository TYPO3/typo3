
.. include:: ../../Includes.txt

======================================
Feature: #67880 - Added count to split
======================================

See :issue:`67880`

Description
===========

A new property `returnCount` is added to the stdWrap property `split`.

When dealing with comma separated values like the content of `field:records` or similar,
we might need to know how many items are present inside the csv.

Example:

.. code-block:: typoscript

	# should return 9
	1 = TEXT
	1 {
		value = x,y,z,1,2,3,a,b,c
		split.token = ,
		split.returnCount = 1
	}


.. index:: TypoScript, Frontend
