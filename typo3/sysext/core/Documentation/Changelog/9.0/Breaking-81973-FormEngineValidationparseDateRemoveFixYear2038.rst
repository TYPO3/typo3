.. include:: ../../Includes.txt

========================================================================
Breaking: #81973 - FormEngineValidation.parseDate remove fixed year 2038
========================================================================

See :issue:`81973`

Description
===========

The limitation to be only able to set dates up to the year 2038 in datetime fields
has been removed. The date by default is always set to the current date.

Additionally it is now also possible to enter dates in years below the year 100 as
those years are no longer automatically converted to either 19xx or 20xx.


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
