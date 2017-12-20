
.. include:: ../../Includes.txt

==============================================================
Breaking: #64229 - Trim submitted login-form-data before usage
==============================================================

See :issue:`64229`

Description
===========

Data submitted through the login-forms (frontend and backend) will now be trimmed before the login is performed.
So now all fields (like username or password) with leading/trailing whitespace will have those removed.
Any whitespace inside fields will not be touched.


Impact
======

Users that have had whitespace at the beginning or end of their username or password will not be able to log in anymore.


Affected installations
======================

Any installation relying on whitespace at the beginning or end of either a username or a password.
Please note that the TYPO3 backend didn't allow whitespace for username in frontend or backend
and only allowed whitespace for passwords of backend users until now.


Migration
=========

Update usernames and/or passwords.


.. index:: PHP-API, Backend, Frontend
