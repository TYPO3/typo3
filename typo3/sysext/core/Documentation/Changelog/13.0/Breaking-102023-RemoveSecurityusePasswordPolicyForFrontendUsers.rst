.. include:: /Includes.rst.txt

.. _breaking-102023-1695557477:

=====================================================================
Breaking: #102023 - Remove security.usePasswordPolicyForFrontendUsers
=====================================================================

See :issue:`102023`

Description
===========

The feature toggle :php:`security.usePasswordPolicyForFrontendUsers` has been
removed, because TypoScript-based password validation in ext:felogin has been
removed, too.


Impact
======

The password policy configured in
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy']` is now always active
for frontend user records in DataHandler and for the password recovery
functionality in ext:felogin.


Affected installations
======================

Installations, where :php:`security.usePasswordPolicyForFrontendUsers` is
deactivated.


Migration
=========

To disable the password policy for frontend users,
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy']` must be set to an
empty string. Note, that it is not recommended to disable the password policy
on production websites.

.. index:: Backend, Frontend, NotScanned, ext:felogin
