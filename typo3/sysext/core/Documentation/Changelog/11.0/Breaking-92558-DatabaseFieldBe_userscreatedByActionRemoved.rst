.. include:: /Includes.rst.txt

==================================================================
Breaking: #92558 - Database Field be_users.createdByAction removed
==================================================================

See :issue:`92558`

Description
===========

The database field :sql:`be_users.createdByAction` which was used
as a type of history for the extracted `sys_action` extension,
has been removed from TYPO3 Core.


Impact
======

Accessing or writing to this database field directly will result
in a SQL error.


Affected Installations
======================

TYPO3 installations using this database field directly.


Migration
=========

Re-add this field manually if needed, otherwise it is recommended
to put such information in the History functionality of TYPO3 Core.

.. index:: Database, FullyScanned, ext:core
