.. include:: ../../Includes.txt

==============================================================================
Deprecation: #83403 - EXT:form - deprecate translation for "options" as string
==============================================================================

See :issue:`83403`

Description
===========

The templates for RadioButton and MultiCheckbox form elements have been changed. This was necessary
to allow dots and special chars within labels and values for the "options" property of the
aforementioned elements.
For compatibility reasons, the old template syntax is still supported but is deprecated and will be
removed with TYPO3 v9.

Impact
======

If a user utilizes his own templates for MultiCheckbox and/ or RadioButton form elements and
translates the "options" property in the following way, a deprecation warning will be thrown.

.. code-block:: html

   {formvh:translateElementProperty(element: element, property: 'options.{value}')}

Affected Installations
======================

Any installation which uses ext:form and own templates for MultiCheckbox and/ or RadioButton form
elements.


Migration
=========

Use

.. code-block:: html

   {formvh:translateElementProperty(element: element, property: '{0: \'options\', 1: value}')}

to translate the "options" property within MultiCheckbox and RadioButton form element templates.

.. index:: Frontend, ext:form