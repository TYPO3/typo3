.. include:: ../../Includes.txt

=====================================================================
Deprecation: #80027 - Remove TCA config 'max' on inputDateTime fields
=====================================================================

See :issue:`80027`

Description
===========

The TCA migration removes the 'max' config option for renderType="inputDateTime" since
this should not be set for this renderType.


Impact
======

Has an impact on performance during saving of records.


Affected Installations
======================

All installations using the 'max' config option for renderType="inputDateTime".


Migration
=========

Remove the 'max' config option for renderType="inputDateTime".

.. index:: Backend, Database, TCA