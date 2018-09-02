.. include:: ../../Includes.txt

==========================================================
Feature: #85355 - Support basic HTML5 fields in FormEngine
==========================================================

See :issue:`85355`

Description
===========

The FormEngine renders now HTML5 specific field types and attributes.


Impact
======

Depending on the :php:`eval` configuration, the input types may be :html:`text`, :html:`number`
or :html:`email.`
If :php:`range` is configured, its values are stored in :html:`min` and :html:`max` attributes
for number fields.

.. index:: Backend, ext:backend
