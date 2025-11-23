..  include:: /Includes.rst.txt

..  _breaking-105863-1735234295:

===============================================================================================
Breaking: #105863 - Remove `exposeNonexistentUserInForgotPasswordDialog` setting in EXT:felogin
===============================================================================================

See :issue:`105863`

Description
===========

The TypoScript setting
:php:`exposeNonexistentUserInForgotPasswordDialog` has been removed in
EXT:felogin.

Impact
======

Using the TypoScript setting
:php:`exposeNonexistentUserInForgotPasswordDialog` has no effect anymore. The
password recovery process in `EXT:felogin` now always shows the same message
when a username or email address is submitted in the password recovery form.

Affected installations
======================

Websites using the TypoScript setting
:php:`exposeNonexistentUserInForgotPasswordDialog` in EXT:felogin are affected.

Migration
=========

The setting has been removed without replacement. It is possible to use the
PSR-14 event
:php-short:`\TYPO3\CMS\FrontendLogin\Event\SendRecoveryEmailEvent` to implement
similar functionality if absolutely necessary. From a security perspective,
however, it is strongly recommended not to expose the existence of email
addresses or usernames.

..  index:: Frontend, NotScanned, ext:felogin
