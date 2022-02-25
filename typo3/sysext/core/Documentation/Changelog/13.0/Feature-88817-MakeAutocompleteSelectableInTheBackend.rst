.. include:: /Includes.rst.txt

.. _feature-88817:

=============================================================
Feature: #88817 - Make autocomplete selectable in the backend
=============================================================

See :issue:`88817`

Description
===========

Autocomplete options can already be added to input
fields in form via editing the YAML. This is
however hard or impossible to do for editors.

Where ever applicable the autocomplete tag has to
be set on form inputs to be accessibility
compliant, however.

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
are used in this change, the autocomplete field might be overriden and
not be displayed in the editor by default.

.. index:: Backend, YAML, ext:form
