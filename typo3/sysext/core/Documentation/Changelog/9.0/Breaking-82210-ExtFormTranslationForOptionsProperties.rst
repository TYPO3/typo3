.. include:: /Includes.rst.txt

============================================================================
Breaking: #82210 - EXT:form - translation for "options" properties as string
============================================================================

See :issue:`82210`

Description
===========

The templates for RadioButton and MultiCheckbox form elements have been changed. This was necessary
to allow dots and special chars within labels and values for the "options" property of the
aforementioned elements.


Impact
======

If a user utilizes his own templates for MultiCheckbox and/ or RadioButton form elements and
translates the "options" property in the following way, the label will not be shown anymore.

.. code-block:: html

   {formvh:translateElementProperty(element: element, property: 'options.{value}')}


Affected Installations
======================

Any installation which uses ext:form and own templates for MultiCheckbox and RadioButton form
elements.


Migration
=========

Use

.. code-block:: html

   {formvh:translateElementProperty(element: element, property: '{0: \'options\', 1: value}')}

to translate the "options" property within MultiCheckbox and RadioButton form element templates.

.. index:: Frontend, ext:form, NotScanned
