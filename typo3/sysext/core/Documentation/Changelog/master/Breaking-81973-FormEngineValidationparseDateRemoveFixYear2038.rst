.. include:: ../../Includes.txt

========================================================================
Breaking: #81973 - FormEngineValidation.parseDate remove fixed year 2038
========================================================================

See :issue:`81973`

Description
===========

In issue :issue:`81940` the TCA range upper bound was removed.
But in the file ``typo3/sysext/backend/Resources/Public/JavaScript/FormEngineValidation.js`` in method
:js:`FormEngineValidation.parseDate` a fixed year 2038 was included.

The result: it was impossible to set a date after year 2038 in datetime fields.
This limitation is now removed. The date is always set to the current date.

A second problem: It was impossible to enter a date < 100 because there were magically added numbers: 2000 for values
between 0 and 38 and 1900 for values between 39 and 100.


Impact
======

The magic in calculating date values, e.g. entering 12 will result in 2012, is now removed.


Affected Installations
======================

This affects only the behavior in backend record editing forms. Values of 0 to 100 will not be changed anymore.


Migration
=========

No migration, this behavior was wrong and there is no migration possible.

.. index:: Backend, FlexForm, JavaScript, NotScanned
