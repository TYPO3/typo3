.. include:: /Includes.rst.txt

.. _breaking-102763-1706375934:

======================================================================
Breaking: #102763 - Frontend user password recovery hashes invalidated
======================================================================

See :issue:`102763`

Description
===========

The replacement of deprecated class
:php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService` results in existing
password recovery hashes of frontend users being invalid.


Impact
======

A frontend user with a valid and unexpired password recovery link created with
a TYPO3 version < 13 can not use the password recovery link to reset the
password.

These hashes have a limited lifetime already (12 hours). On large installations
that require hashes to survive a major update, you could write a small CLI task
that re-adds missing hashes created in the maintenance time window of the upgrade.


Affected installations
======================

TYPO3 installations which use the "Display Password Recovery Link" option of
ext:fe_login.


Migration
=========

Frontend users need to request a new password recovery link to reset the
password.

.. index:: Frontend, NotScanned, ext:felogin
