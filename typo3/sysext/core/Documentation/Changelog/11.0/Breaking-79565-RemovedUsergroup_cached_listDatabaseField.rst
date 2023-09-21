.. include:: /Includes.rst.txt

.. _breaking-79565:

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

The PHP entry is removed from
:php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->user` array.
Accessing the array key will result in warnings since PHP 8.0.

Affected Installations
======================

TYPO3 installations using or querying this database field
with third-party extensions.

TYPO3 installations reading the array key from
:php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->user` array.

Migration
=========

Use the class :php:`\TYPO3\CMS\Core\Authentication\GroupResolver`
to fetch all groups of a user directly.

.. index:: Backend, PHP-API, NotScanned, ext:core
