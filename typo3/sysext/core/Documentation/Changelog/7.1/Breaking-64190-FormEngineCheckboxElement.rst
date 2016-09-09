
.. include:: ../../Includes.txt

=========================================================================
Breaking: #64190 - FormEngine Checkbox Element limitation of cols setting
=========================================================================

See :issue:`64190`

Description
===========

The TCA configuration for checkbox cols has been changed. We reduced the
number of accepted values to 1, 2, 3, 4 and 6 to provide a responsive experience.

For usecases like checkboxes for weekdays like mo, tu, we, th, fr, sa, su
we introduced a new value `inline`.

Impact
======

For values equals 5 or above 6 the rendering of 6 will be used.


Affected installations
======================

Installations with TCA column configurations for checkboxes with values
equals 5 or above 6.

Migration
=========

Choose between one of the supported values or change the display to `inline`.
