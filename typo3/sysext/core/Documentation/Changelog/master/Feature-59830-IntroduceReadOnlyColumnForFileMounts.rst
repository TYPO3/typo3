============================================================
Feature: #59830 - Introduce read-only column for file mounts
============================================================

Description
===========

File mount records got a new flag "read only". This flag replaces the virtual flag introduced earlier,
so it can be defined natively in the record.


Impact
======

The impact is low as old behavior still exists. Before a storage was never marked as read-only, there is
an option to set this through UserTs but now it is also possible to set it on the storage record directly.