
.. include:: ../../Includes.txt

==================================================
Feature: #63561 - Add TypoScript stdWrap strtotime
==================================================

See :issue:`63561`

Description
===========

A new TypoScript property `strtotime` is now available within `stdWrap` which allows for conversion of formatted
dates to timestamp, e.g. to perform date calculations.

Possible values are `1` or any time string valid as first argument of the PHP `strtotime()` function.

Basic usage to convert date string to timestamp:

.. code-block:: typoscript

	date_as_timestamp = TEXT
	date_as_timestamp {
		value = 2015-04-15
		strtotime = 1
	}

Convert incoming date string to timestamp, perform date calculation and output as date string again:

.. code-block:: typoscript

	next_weekday = TEXT
	next_weekday {
		data = GP:selected_date
		strtotime = + 2 weekdays
		strftime = %Y-%m-%d
	}

Impact
======

The new property is available everywhere in TypoScript where `stdWrap` is applied.


.. index:: TypoScript, Frontend
