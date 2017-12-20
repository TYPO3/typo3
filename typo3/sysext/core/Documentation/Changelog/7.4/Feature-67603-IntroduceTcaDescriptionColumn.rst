
.. include:: ../../Includes.txt

==========================================================
Feature: #67603 - Introduce TCA > ctrl > descriptionColumn
==========================================================

See :issue:`67603`

Description
===========

To annotate database table column fields as internal description for editors and admins a new setting
for TCA is introduced. Setting is called `['TCA']['ctrl']['descriptionColumn']` and holds column name.

This description should only displayed in the backend to guide editors and admins.

Usage of descriptionColumn is added under different issues.

Impact
======

None, since annotation itself is added only. Does not impact.


.. index:: TCA, Backend
