
.. include:: ../../Includes.txt

===================================================
Breaking: #66991 - TCA value slider based on jQuery
===================================================

See :issue:`66991`

Description
===========

The TCA value slider has been ported from ExtJS to jQuery and Bootstrap.


Impact
======

Since TYPO3 CMS 7 uses a DateTimePicker, the time selection conflicts with the value slider and therefore
time-sliding has been dropped.


Affected Installations
======================

All installations are affected whose TCA uses the value slider wizard in combination with `time` evaluation.


Migration
=========

Remove the slider wizard from affected TCA.


.. index:: TCA, JavaScript, Backend
