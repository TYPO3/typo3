.. include:: /Includes.rst.txt

=======================================================================
Breaking: #89137 - Database fields t3ver_tstamp and t3ver_count removed
=======================================================================

See :issue:`89137`

Description
===========

The two workspace related database fields :sql:`t3ver_tstamp` and :sql:`t3ver_count`
have been dropped from all workspace aware database tables. Also, all code handling these
fields has been removed from the code base.


Impact
======

The core did not show these fields to the user. It's very unlikely extensions made use of them.
Admins upgrading to TYPO3 v11 can usually assume zero impact for their site functionality.


Affected Installations
======================

All instances are affected by this change, the database analyzer will propose to drop the fields
from a lot of tables including :sql:`pages` and :sql:`tt_content`.


Migration
=========

Use the database analyzer during upgrade to drop the fields from affected database tables.

.. index:: Database, NotScanned, ext:workspaces
