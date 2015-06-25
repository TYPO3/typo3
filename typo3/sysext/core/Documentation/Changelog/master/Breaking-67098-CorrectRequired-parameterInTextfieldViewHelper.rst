====================================================================
Breaking: #67098 - Correct required-parameter in TextfieldViewHelper
====================================================================

Description
===========

The value comparison of the required parameter is corrected. Before, a textfield was required as soon as it had a parameter "required" set to any value. Even if this value was set to FALSE, the textfield was still required.


Impact
======

Textfelds with required="FALSE" are not required any longer.


Affected Installations
======================

Every installation that uses the textfield viewhelper with the required attribute.


Migration
=========

none