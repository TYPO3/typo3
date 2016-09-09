
.. include:: ../../Includes.txt

============================================
Deprecation: #64134 - Deprecate $BE_USER->OS
============================================

See :issue:`64134`

Description
===========

The public property in the global object `$BE_USER->OS` has been marked as deprecated.


Affected installations
======================

Instances with extensions that make use of the public property directly.


Migration
=========

Use the constant `TYPO3_OS` directly.
