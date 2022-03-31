.. include:: /Includes.rst.txt

=======================================================================
Deprecation: #81213 - Render method arguments on ViewHelpers deprecated
=======================================================================

See :issue:`81213`

Description
===========

Support for arguments on the :php:`render()` method of ViewHelpers has been deprecated.


Impact
======

Usage of render method arguments will cause a deprecation message to be logged about the specific Viewhelper class.


Affected Installations
======================

Any TYPO3 site or extension using ViewHelpers with one or more arguments on the :php:`render()` method.


Migration
=========

Switch to :php:`initializeArguments` method (override this from parent) and call :php:`registerArgument` to register each
argument supported by the ViewHelper.

.. index:: Fluid, NotScanned
