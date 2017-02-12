.. include:: ../../Includes.txt

==================================================================
Feature: #79521 - Show list of failed input elements in FormEngine
==================================================================

See :issue:`79521`

Description
===========

When validating input fields of the FormEngine fails, a button is now rendered into the button bar in
the module document header. Clicking the button renders a list of all input elements whose validation failed.

Clicking onto a field in that list automatically focuses the field in the form.

.. index:: Backend