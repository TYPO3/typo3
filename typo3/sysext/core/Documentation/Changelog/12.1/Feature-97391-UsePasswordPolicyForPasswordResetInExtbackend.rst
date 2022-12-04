.. include:: /Includes.rst.txt

.. _feature-97391:

=======================================================================
Feature: #97391 - Use password policy for password reset in ext:backend
=======================================================================

See :issue:`97391`

Description
===========

The password reset feature for TYPO3 backend users now considers the
configurable password policy introduced in :ref:`#97388 <feature-97388>`.


Impact
======

The formerly hardcoded minimum length of 8 chars for the new password
has been removed. Instead, the globally configured password policy is now
taken into account when a TYPO3 backend user resets the password. The
TYPO3 default password policy contains the following password requirements:

* At least 8 chars
* At least one number
* At least one upper case char
* At least one special char
* Must be different than current password (if available)

.. index:: Backend, ext:backend
