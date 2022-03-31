
.. include:: /Includes.rst.txt

===========================================================
Feature: #28230 - Add support for PBKDF2 to saltedpasswords
===========================================================

See :issue:`28230`

Description
===========

A new password hashing algorithm `PBKDF2` has been added to the system extension `saltedpasswords`.
PBKDF2 is designed to be computationally expensive to resist brute force password cracking.


Impact
======

None, the new hashing algorithm needs to be enabled by the system administrator in the extension
configuration and will upgrade existing passwords transparently on login.

.. index:: Backend, ext:saltedpasswords
