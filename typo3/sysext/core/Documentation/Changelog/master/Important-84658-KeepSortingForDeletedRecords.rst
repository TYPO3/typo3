.. include:: ../../Includes.txt

===========================================================
Important: #84658 - Keep sorting  value for deleted records
===========================================================

See :issue:`84658`


Description
===========

Keep the value for the defined sorting field when a record is deleted.


Impact
======

Functional tests that are based on sorting value to be 1000000000 for deleted records will fail.


Affected Installations
======================

All third party extensions with functional tests using the sorting field for deleted records.


Migration
=========

Check your functional tests fixtures and set the expected sorting value for deleted records equal to
the starting value.

.. index:: NotScanned
