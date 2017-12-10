.. include:: ../../Includes.txt

====================================================================
Deprecation: #81534 - BackendUtility::getListGroupNames() deprecated
====================================================================

See :issue:`81534`

Description
===========

PHP method :php:`BackendUtility::getListGroupNames()` has been dropped due to
the removal of database field :php:`hide_in_lists`.


Impact
======

The methods shouldn't be used anymore. If still used, the where constraint on filed
hide_in_lists is no longer considered.


Affected Installations
======================

Extensions using above method should switch to an alternative.


Migration
=========

Use method :php:`BackendUtility::getGroupNames()` instead and keep an eye on the
different non-admin use of the method if switching.

.. index:: Database, PHP-API, TCA, FullyScanned