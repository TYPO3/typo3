..  include:: /Includes.rst.txt

..  _feature-106637-1760521627:

========================================================
Feature: #106637 - Implement accessible combobox pattern
========================================================

See :issue:`106637`

Description
===========

A new ARIA 1.2–compliant combobox web component has been introduced, replacing
the legacy value picker select pattern. The implementation follows the W3C
accessibility guidelines and provides complete keyboard navigation support.

FormEngine elements, including
:php-short:`\TYPO3\CMS\Backend\Form\Element\EmailElement`,
:php-short:`\TYPO3\CMS\Backend\Form\Element\InputTextElement`, and
:php-short:`\TYPO3\CMS\Backend\Form\Element\NumberElement`, have been updated to
use the new combobox component instead of the previous value picker
implementation. The link browser components have also been adapted to use the
combobox pattern.

Impact
======

The new combobox component offers full keyboard navigation using the arrow keys,
Enter, Tab, and Escape.

It includes visual selection indicators with checkmarks and a clear button
for resetting the input value, improving accessibility and overall usability
in the TYPO3 backend.

..  index:: Backend, ext:backend
