.. include:: ../../Includes.txt

=============================================================
Deprecation: #80614 - TCA itemListStyle and selectedListStyle
=============================================================

See :issue:`80614`

Description
===========

The TCA property ``itemListStyle`` available in renderType ``selectSingleBox`` and
``selectMultipleSideBySide``, as well as the property ``selectedListStyle`` available in
renderType ``selectMultipleSideBySide`` have been deprecated.


Impact
======

Using these properties throws a deprecation warning and will not be considered with TYPO3 v9.


Affected Installations
======================

Instances using one of the above properties in TCA


Migration
=========

The properties can be dropped. Changing styles of above elements should be done in own renderTypes
or by overloading CSS in the backend.

.. index:: Backend, TCA
