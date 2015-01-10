==============================================================
Breaking: #64229 - Trim submitted login-form-data before usage
==============================================================

Description
===========

Data submitted through the login-forms (frontend and backend) will now be trimmed before the login is performed.
So now all fields (like username or password) with leading/following whitespaces will have those removed.
Any whitespaces inside fields will however not be touched.


Impact
======

Users that have had whitespaces at the beginning or end of their usernames or password will not be able to log in anymore.


Affected installations
======================

Any installation relying on whitespaces at the beginning or end of either a username or a password.
Please note that the TYPO3 backend didn't allow whitespaces for usernames in frontend or backend
and only allowed whitespaces for passwords of backend users until now.


Migration
=========

Update usernames/passwords.
