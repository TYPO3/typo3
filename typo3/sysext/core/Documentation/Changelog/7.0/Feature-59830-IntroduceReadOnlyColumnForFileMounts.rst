
.. include:: ../../Includes.txt

============================================================
Feature: #59830 - Introduce read-only column for file mounts
============================================================

See :issue:`59830`

Description
===========

File mount records now have a new flag "read only". This flag replaces the virtual flag introduced earlier,
so it can be defined natively in the record.


Impact
======

The impact is low as the old behavior still exists. A storage was never marked as read-only before. There is
an option to set this through UserTs, but now it is also possible to set it on the storage record directly.
