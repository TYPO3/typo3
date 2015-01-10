========================================
Deprecation: #64762 - FormEngine wizards
========================================

Description
===========

The following TCA wizards properties are removed:

* _PADDING
* _VALIGN
* _DISTANCE


Impact
======

Usage of the mentioned TCA properties has no effect anymore.


Affected installations
======================

Installations with special TCA wizard position settings ignore those now.


Migration
=========

Remove above properties.