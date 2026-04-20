..  include:: /Includes.rst.txt

..  _important-109585-1776329549:

====================================================================
Important: #109585 - Serialized Credential Data in be_users settings
====================================================================

See :issue:`109585`

Description
===========

The new mechanism of using serialized JSON data for storing
backend user settings since TYPO3 14.2 has introduced a vulnerability
that stored the "password" and "verify password" input data
when changing a user's password inside the serialized user
settings representation.

These passwords are no longer stored in the database columns
:sql:`be_users.uc` and :sql:`be_users.user_settings` anymore,
but may exist in database records during the period where
TYPO3 v14.2 was used.

An upgrade wizard has been added that will remove these credentials
from the serialized representation.

This upgrade wizard will detect possible records that contain
the string `"password` or `:"password` and then unserialize
the data, remove the two fields and re-serialize the data. It
is important to execute this wizard for safety. If the wizard
does not show up, no serialized credential data is found.

..  index:: Backend, PHP-API, ext:backend, NotScanned
