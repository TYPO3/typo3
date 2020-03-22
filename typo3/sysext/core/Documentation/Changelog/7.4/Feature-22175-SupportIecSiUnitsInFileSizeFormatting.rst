
.. include:: ../../Includes.txt

==============================================================
Feature: #22175 - Support IEC/SI units in file size formatting
==============================================================

See :issue:`22175`

Description
===========

Size formatting supports two keywords additionally to the list of labels:

- iec: uses the Ki, Mi, etc prefixes and binary base (power of two, 1024)
- si: uses the k, M, etc prefixes and decimal base (power of ten, 1000)

The default formatting is set to "iec" base size calculations on the same base as before.
The fractional part, when present, is changed to two numbers instead of only one.

The list of labels is still supported and defaults to using binary base. It is also
possible to explicitly choose between binary or decimal base when it is used.


Impact
======

Default formatted output of file sizes changes, see example below.

TypoScript `stdWrap` property `bytes` defaults to a different label set.
`bytes.labels = iec`, a specifically defined label string with pipe separated
label keywords is obsolete, but can still be used if required. The keyword
`iec` resolves to ` | Ki| Mi| Gi| Ti| Pi| Ei| Zi| Yi` (binary base) and `si` resolves
to ` | k| M| G| T| P| E| Z| Y` (based on ten).


Example
=======

.. code-block:: php

	echo GeneralUtility::formatSize(85123);
	// => Before "83.1 K"
	// => Now "83.13 Ki"


.. index:: PHP-API, Backend
