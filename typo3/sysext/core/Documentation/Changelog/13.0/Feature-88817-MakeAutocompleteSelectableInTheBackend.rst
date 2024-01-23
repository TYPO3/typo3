.. include:: /Includes.rst.txt

.. _feature-88817:

==================================================================
Feature: #88817 - Make autocomplete selectable in EXT:form backend
==================================================================

See :issue:`88817`

Description
===========

Autocomplete options can already be added to input
fields in `EXT:form` via editing the YAML. This was
hard or impossible to do for editors.

The autocomplete tag has to be set for form fields
to be accessibility compliant, where applicable.

The parameter can have arbitrary content according
to the HTML standard. However, assistive
technology only supports a finite number of values,
of which only few are commonly used in contact
forms.

Projects that desire additional parameter values
can set them via YAML.

Impact
======

Editors can now select the autocomplete input purpose when editing forms.

In installations that extended the form YAML configuration with keys that
are used in this change, the autocomplete field might be overridden and
not be displayed in the editor by default.

.. index:: Backend, YAML, ext:form
