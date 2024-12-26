..  include:: /Includes.rst.txt

..  _breaking-105863-1735234295:

===============================================================================================
Breaking: #105863 - Remove `exposeNonexistentUserInForgotPasswordDialog` setting in ext:felogin
===============================================================================================

See :issue:`105863`

Description
===========

The TypoScript setting :php:`exposeNonexistentUserInForgotPasswordDialog` has been
removed in ext:felogin.


Impact
======

Using the TypoScript setting :php:`exposeNonexistentUserInForgotPasswordDialog` has
no effect any more and the password recovery process in ext:felogin now always
shows the same message, when either a username or email address is submitted in
the password recovery form.


Affected installations
======================

Websites using the TypoScript setting :php:`exposeNonexistentUserInForgotPasswordDialog`
for ext:felogin.


Migration
=========

The setting has been removed without a replacement. It is possible to use the
PSR-14 event :php:`TYPO3\CMS\FrontendLogin\Event\SendRecoveryEmailEvent` to
implement a similar functionality if really needed. From a security perspective,
it is however highly recommended to not expose the existence of email addresses
or usernames.

..  index:: Frontend, NotScanned, ext:felogin
