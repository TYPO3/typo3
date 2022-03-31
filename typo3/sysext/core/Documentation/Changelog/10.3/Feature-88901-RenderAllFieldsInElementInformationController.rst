.. include:: /Includes.rst.txt

===================================================================
Feature: #88901 - Render all fields in ElementInformationController
===================================================================

See :issue:`88901`

Description
===========

The element information modal now shows all fields of the current record and
the selected type.


Impact
======

The TCA configuration :php:`showRecordFieldList` inside the section :php:`interface` is
not evaluated anymore and all occurrences have been removed.

A migration wizard is available that removes the option from your TCA and adds
a deprecation message to the deprecation log where code adaption has to take place.

.. index:: Backend
