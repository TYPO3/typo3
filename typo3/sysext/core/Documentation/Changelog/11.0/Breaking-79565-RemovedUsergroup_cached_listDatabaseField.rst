.. include:: /Includes.rst.txt

=================================================================
Breaking: #79565 - Removed "usergroup_cached_list" database field
=================================================================

See :issue:`79565`

Description
===========

The database field :sql:`be_users.usergroup_cached_list` has been
removed. It was populated by a list of all groups (including
subgroups) the user belongs to, and stored when a user logged in.
The field however was never updated when an admin added or removed a group
from the users group list.


Impact
======

The mentioned database field is removed, any direct SQL queries
accessing or writing this field will result in a database error.


Affected Installations
======================

TYPO3 installations using or querying this database field
with third-party extensions.


Migration
=========

Use the class :php:`GroupResolver` to fetch all groups of a user directly.

.. index:: Backend, PHP-API, NotScanned, ext:core
