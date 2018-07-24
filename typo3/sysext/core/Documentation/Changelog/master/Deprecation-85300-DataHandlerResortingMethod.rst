.. include:: ../../Includes.txt

==================================================
Deprecation: #85300 - DataHandler resorting method
==================================================

See :issue:`85300`

Description
===========

The public :php:`DataHandler->resorting` method has been marked as deprecated. It will be removed in v10.0.


Impact
======

Installations using this method will log deprecation message in the log.


Affected Installations
======================

All installations xclassing DataHandler, or having code calling mentioned method.


Migration
=========

Use newly introduced `increaseSortingOfFollowingRecords` method.

.. index:: Backend, FullyScanned, ext:core
