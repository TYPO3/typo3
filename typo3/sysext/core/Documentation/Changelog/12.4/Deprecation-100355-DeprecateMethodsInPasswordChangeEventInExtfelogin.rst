.. include:: /Includes.rst.txt

.. _deprecation-100355-1680608322:

==============================================================================
Deprecation: #100355 - Deprecate methods in PasswordChangeEvent in ext:felogin
==============================================================================

See :issue:`100355`

Description
===========

The following methods in the PSR-14 event :php:`PasswordChangeEvent` of
ext:felogin have been marked as deprecated and should not be used any more:

* :php:`setAsInvalid()`
* :php:`getErrorMessage()`
* :php:`isPropagationStopped()`
* :php:`setHashedPassword()`


Impact
======

Event listeners, who use one of the deprecated methods of the
:php:`PasswordChangeEvent` PSR-14 event, will raise a deprecation level log
message. The functionality is kept in TYPO3 v12 but will be removed in v13.


Affected installations
======================

Instances who use the PSR-14 event :php:`PasswordChangeEvent` for password
validation and who use one of the deprecated methods.

The extension scanner reports usages as a weak match.


Migration
=========

Password validation for the password recovery functionality in ext:felogin
must be implemented using a custom password policy validator.

See :issue:`97388` for details.

.. index:: Backend, FullyScanned, ext:felogin
