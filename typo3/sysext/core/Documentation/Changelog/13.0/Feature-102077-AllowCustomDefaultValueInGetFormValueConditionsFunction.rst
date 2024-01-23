.. include:: /Includes.rst.txt

.. _feature-102077-1696240251:

===================================================================================
Feature: #102077 - Allow custom default value in getFormValue() conditions function
===================================================================================

See :issue:`102077`

Description
===========

The :yaml:`getFormValue()` function can be used in conditions of form variants to
safely retrieve form values. Before, `null` was returned as default value. This
made it impossible to use this, for example, with the :yaml:`in` operator to check
values in multi-value form fields. An additional check was necessary to avoid
type issues:

..  code-block:: yaml

    variants:
      - identifier: variant-1
        condition: 'getFormValue("multiCheckbox") && "foo" in getFormValue("multiCheckbox")'

A second argument has been added to this function to set a custom default value.
This allows shortening conditions accordingly:

..  code-block:: yaml

    variants:
      - identifier: variant-1
        condition: '"foo" in getFormValue("multiCheckbox", [])'


Impact
======

Form variant conditions can be shortened.

.. index:: YAML, ext:form
