.. include:: ../../Includes.txt

============================================================
Feature: #76085 - Add fluid debug information to admin panel
============================================================

See :issue:`76085`

Description
===========

A new setting in the admin panel (Preview > Show fluid debug output) enables showing fluid debug output.
If the checkbox is enabled, the path to the template file of a partial and the name of a section will be shown in the
frontend directly above the markup.
With this feature an integrator can easily find the correct template and section.


Impact
======

Activating this option can break the output in frontend or may result in unexpected behavior.

.. index:: Frontend
