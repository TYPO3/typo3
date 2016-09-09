
.. include:: ../../Includes.txt

==========================================================================
Deprecation: #65381 - Deprecate DataHandler property "stripslashes_values"
==========================================================================

See :issue:`65381`

Description
===========

The DataHandler property `stripslashes_values` has been marked as deprecated.


Impact
======

A deprecation message is logged for every time DataHandler processes data if this property
is set to TRUE.


Affected installations
======================

All installations or extensions relying on the DataHandler property `stripslashes_values`.


Migration
=========

Set the `stripslashes_values` property to FALSE and apply `stripslashes()` in the code that
prepares the data if it was expected that DataHandler stripped the slashes from incoming
data.
