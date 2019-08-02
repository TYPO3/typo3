.. include:: ../../Includes.txt

===================================================================
Feature: #88901 - Render all fields in ElementInformationController
===================================================================

See :issue:`88901`

Description
===========

The element information modal now shows all fields of the current record and the selected type.


Impact
======

The TCA configuration `showRecordFieldList` inside the section `interface` is not evaluated anymore and all occurences have been removed.

.. index:: Backend
