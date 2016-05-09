============================================================
Feature: #76085 - Add fluid debug information to admin panel
============================================================

Description
===========

A new setting in the admin panel (Preview > Show fluid debug output) enable fluid debug output.
If the checkbox is enabled, the path to the template file of a partial and the name of a section will be shown in the
frontend directly above the markup.
With this feature an integrator can easily find the correct template and section.

.. note::

      This feature is only available in development context.
      Set TYPO3_CONTEXT to "Development" to enable the checkbox in the admin panel.

Impact
======

Activating this option can break the output in frontend or result in unexpected behavior.
