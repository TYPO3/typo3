.. include:: /Includes.rst.txt

===========================================================================
Breaking: #92802 - User-database-based authentication timeout field removed
===========================================================================

See :issue:`92802`

Description
===========

The :php:`AbstractUserAuthentication` object had the possibility to
theoretically use a database field where a session timeout value
for the session storage could be set. This was never implemented but
rather separated into a separate property called :php:`sessionTimeout`.

This functionality, together with the public property
:php:`auth_timeout_field`, has been removed.


Impact
======

Setting the property via a custom extension will result in a PHP warning, as
the property does not exist anymore.

In addition, this property is never evaluated anymore when determining the
session timeout.


Affected Installations
======================

TYPO3 installations that used third-party code to modify the session timeout
value based on a database field, which relied on the public property for
implementation purposes.


Migration
=========

Use a custom implementation with custom hooks or custom authentication provider
to achieve the same results.

.. index:: PHP-API, FullyScanned, ext:core
