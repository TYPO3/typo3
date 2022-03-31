
.. include:: /Includes.rst.txt

============================================================
Feature: #74038 - Report for checking database character set
============================================================

See :issue:`74038`

Description
===========

If a database has e.g. latin1 as default character set, new tables or fields created
by TYPO3 will be created with this default charset as utf-8 is not enforced.
A report has been added that checks the default charset and warns administrators if a
wrong charset is used.


Impact
======

If the default database character set is not utf-8, the report warns administrators
about a wrong charset.

.. index:: Backend, Database, ext:reports
