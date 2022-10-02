.. include:: /Includes.rst.txt

.. _feature-92861:

============================================
Feature: #92861 - Introduce TCA option "min"
============================================

See :issue:`92861`

Description
===========

The new TCA option :php:`min` allows to define a minimum number of characters
for fields of type :php:`input` and :php:`text`. This option simply adds a
:html:`minlength` attribute to the input field. If at least one character is
typed in and the number of characters is less than :php:`min`, the FormEngine
marks the field as invalid, preventing the user to save the element.

When using :php:`min` in combination with :php:`max`, one has to make sure, the
:php:`min` value is less than or equal :php:`max`. Otherwise the option is
ignored.

Empty fields are not validated. If one needs to have non-empty values, it is
recommended to use :php:`required => true` in combination with :php:`min`.

.. note::

   This option does not work for text fields, if RTE is enabled.

Impact
======

Integrators and developers are now able to define a minimum number of characters
a simple text or textarea field should have. Editors are forced to provide the
specified minimum amount of characters. An alert badge, similar to the one of
the max value, will show, how many characters are missing.

.. index:: Backend, TCA, ext:backend
