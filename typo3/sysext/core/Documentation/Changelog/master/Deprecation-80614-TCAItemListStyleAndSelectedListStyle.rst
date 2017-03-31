.. include:: ../../Includes.txt

=============================================================
Deprecation: #80614 - TCA itemListStyle and selectedListStyle
=============================================================

See :issue:`80614`

Description
===========

The TCA property :code:`itemListStyle` available in renderType :code:`selectSingleBox` and
:code:`selectMultipleSideBySide`, as well as the property :code:`selectedListStyle` available in
renderType :code:`selectMultipleSideBySide` have been deprecated.


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