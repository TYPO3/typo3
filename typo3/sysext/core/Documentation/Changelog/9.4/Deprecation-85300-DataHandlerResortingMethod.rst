.. include:: /Includes.rst.txt

==================================================
Deprecation: #85300 - DataHandler resorting method
==================================================

See :issue:`85300`

Description
===========

The public :php:`DataHandler->resorting` method has been marked as deprecated. It will be removed in v10.0.


Impact
======

Installations using this method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations xclassing DataHandler, or having call the method mentioned.


Migration
=========

Use the newly introduced :php:`DataHandler->increaseSortingOfFollowingRecords` method instead.

.. index:: Backend, FullyScanned, ext:core
