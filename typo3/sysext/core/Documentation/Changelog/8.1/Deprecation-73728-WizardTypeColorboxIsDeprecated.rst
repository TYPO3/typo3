
.. include:: ../../Includes.txt

========================================================
Deprecation: #73728 - Wizard type colorbox is deprecated
========================================================

See :issue:`73728`

Description
===========

The color-picker is now available as dedicated render-type which will integrate
an inline color-picker widget based on bootstrap. Thus, the old wizard type
`colorbox` has been marked as deprecated.


Impact
======

Using the TCA wizard type `colorbox` will trigger a deprecation log entry.
The possibility to pick the color from a custom image has been removed
without substitution together with the possibility to use color names like
"red" or "white".


Affected Installations
======================

All TCA fields that are using the wizard type `colorbox`, like e.g.


.. code-block:: php

   $GLOBALS['TCA']['tableName']['fieldName']['config']['wizards']['colorbox'] = [
      'type' => 'colorbox',
      'script' => 'wizard_colorpicker.php',
      ...
   ];


Migration
=========

Use the new render-type `colorpicker` in the TCA field configuration, like e.g.

.. code-block:: php

   $GLOBALS['TCA']['tableName']['fieldName']['config']['renderType'] = 'colorpicker';

.. index:: PHP-API, TCA, Backend
