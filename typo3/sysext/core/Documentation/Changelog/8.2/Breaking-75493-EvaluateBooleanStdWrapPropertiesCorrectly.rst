
.. include:: ../../Includes.txt

===================================================================
Breaking: #75493 - Evaluate "boolean /stdWrap" properties correctly
===================================================================

See :issue:`75493`

Description
===========

stdWrap sub-properties on boolean properties were not evaluated correctly unless the
property itself was explicitly set.

Example: :typoscript:`page.10.value.prioriCalc.wrap =` without :typoscript:`page.10.value.prioriCalc =`


Impact
======

It is now possible to reliably use stdWrap sub-properties on boolean properties.


Affected Installations
======================

Earlier installations can be affected if they contain TypoScript that triggers the bug.

Test case:

.. code-block:: typoscript

	page = PAGE
	page.10 = TEXT
	page.10.value = 1+1
	page.10.value.prioriCalc.wrap =

Result was: 2
Correct result: 1+1

For some installations, the bug fix will make the TypoScript work as intended.
Other installations might accidentally rely on the broken code.

In the latter case, the TypoScript can be changed to:

.. code-block:: typoscript

	page = PAGE
	page.10 = TEXT
	page.10.value = 1+1
	page.10.value.prioriCalc = 1


Migration
=========

The usage of stdWrap sub-properties on boolean properties needs to be checked and possibly adapted to fit the fixed behavior.

.. index:: TypoScript, Frontend
