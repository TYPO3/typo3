
.. include:: ../../Includes.txt

==============================================================
Feature: #61463 - Allow processed folders in different storage
==============================================================

See :issue:`61463`

Description
===========

The processing folder of a storage can now be a combined identifier.
This makes it possible to have the processed files outside of the
storage in case of a read-only storage for instance.


Impact
======

For existing systems there is no impact. When the processing folder is changed
to a folder in a different storage you need to make sure the folder exists
and is writable.


.. index:: FAL, Database, Backend
