..  include:: /Includes.rst.txt

..  _feature-109126-1740000000:

=====================================================
Feature: #109126 - Introduce date editor for ext:form
=====================================================

See :issue:`109126`

Description
===========

A new web component :html:`<typo3-form--date-editor>`
has been introduced for the form editor backend. It replaces the plain text
input for the :yaml:`DateRange` validator minimum/maximum fields and the
:yaml:`defaultValue` field of the :yaml:`Date` form element.

Previously, editors had to manually type date values or relative expressions
like :yaml:`-18 years` into a plain text field. The new structured editor
provides a user-friendly UI with five modes:

- **No value** – Clears the constraint (empty value)
- **Today** – Sets the value to :yaml:`today`
- **Absolute date** – Native HTML5 date picker producing `Y-m-d` values
- **Relative date** – Structured input with direction (past/future), amount
  and unit (days, weeks, months, years) producing expressions like
  :yaml:`-18 years` or :yaml:`+1 month`
- **Custom relative expression** – Free-text input for arbitrary relative date
  expressions that go beyond the structured input, such as compound expressions
  like :yaml:`+1 month +3 days`. The input is validated against the configured
  relative date pattern.

Impact
======

The form editor backend now provides a structured, user-friendly editor for
date constraints and default values on :yaml:`Date` form elements. Editors no
longer need to know the PHP relative date syntax — they can simply select
a mode, direction, amount and unit from dropdown fields. For advanced use cases,
the custom mode allows typing arbitrary relative date expressions with
real-time validation. Existing form definitions are not affected and continue
to work without changes.

..  index:: Backend, ext:form

