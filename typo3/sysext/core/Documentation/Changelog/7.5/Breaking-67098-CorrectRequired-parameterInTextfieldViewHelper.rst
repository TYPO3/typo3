
.. include:: ../../Includes.txt

====================================================================
Breaking: #67098 - Correct required-parameter in TextfieldViewHelper
====================================================================

See :issue:`67098`

Description
===========

The value comparison of the required parameter has been corrected. Prior to this
change, a textfield was required as soon as it had a parameter "required" set to
any value even if this value was set to FALSE, the textfield was still required.


Impact
======

Textfields with required="FALSE" are not required any longer.


Affected Installations
======================

Every installation that uses the textfield viewhelper with the required attribute.


Migration
=========

No migration is necessary.
