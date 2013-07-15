.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _results-of-database-comparison-operations:

Results of database comparison operations
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Whenever a database structure comparison is done, it is likely that
differences are detected, that have no real background. The
*unsigned* attribute used with integer fields in MySQL for instance
has no equivalent in PostgreSQL. Thus if a change is suggested that
would solely try to add this attribute, just ignore it. To make this
easier the changes that are usually preselected in the install tool
are not, when the DBAL extension is detected.

Similar "errors" will be detected for certain field types and probably
keys/indexes. You have to use common sense and your DB background
knowledge to move around those issues.

On some databases you might even see keys that should be dropped or
created, most of the time this is bogus, too.
