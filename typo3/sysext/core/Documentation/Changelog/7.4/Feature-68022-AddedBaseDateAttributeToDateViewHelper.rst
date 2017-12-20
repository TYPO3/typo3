
.. include:: ../../Includes.txt

=============================================================
Feature: #68022 - Added base date attribute to DateViewHelper
=============================================================

See :issue:`68022`

Description
===========

The DateViewHelper has been improved with an optional attribute named `base`.
The attribute can be used to define a base-date when using a relative time specification for `date`.
If `date` is a `DateTime` object, `base` is ignored.

The possible relative date format specification can be found in:
http://www.php.net/manual/en/datetime.formats.relative.php

.. code-block:: html

	<f:format.date format="Y" base="{dateObject}">-1 year</f:format.date>

This will result in the output `2016` assuming the `dateObject` is some date in 2017.


.. index:: Fluid
