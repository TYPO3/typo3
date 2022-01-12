.. include:: /Includes.rst.txt

.. _feature-92760-1733907198:

==========================================================
Feature: #92760 - Configurable timezone for DateViewHelper
==========================================================

See :issue:`92760`

Description
===========

A new option :html:`timezone` has been added to the :php:`DateViewHelper` to render
a date with the provided time zone.

.. code-block:: html

    <f:format.date format="d.m.Y g:i a" date="1640995200" /><br>
    <f:format.date format="d.m.Y g:i a" date="1640995200" timezone="America/Phoenix" /><br>
    <f:format.date format="d.m.Y g:i a" date="1640995200" timezone="Indian/Mayotte" />

will render:

.. code-block:: html

    01.01.2022 12:00 am
    31.12.2021 5:00 pm
    01.01.2022 3:00 am

Impact
======

Using the new :html:`timezone` option, it's now possible to set a specific
time zone for the the :php:`DateViewHelper`.

.. index:: Fluid, ext:fluid
