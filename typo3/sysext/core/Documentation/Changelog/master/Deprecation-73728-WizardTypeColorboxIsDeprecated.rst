========================================================
Deprecation: #73728 - Wizard type colorbox is deprecated
========================================================

Description
===========

The color-picker is now available as dedicated render-type which will integrate
an inline color-picker widget based on bootstrap. Thus, the old wizard type
``colorbox`` is deprecated.


Impact
======

Using the TCA wizard type ``colorbox`` will trigger an internal deprecation
message. The possibility to pick the color from a custom image has been removed
without any substitution as well as the possibility to use color names like
"red" or "white".


Affected Installations
======================

All TCA fields that are using the wizard type ``colorbox``, like e.g.


.. code-block:: php

   $GLOBALS['TCA']['tableName']['fieldName']['config']['wizards']['colorbox'] = [
      'type' => 'colorbox',
      'script' => 'wizard_colorpicker.php',
      ...
   ];


Migration
=========

Use the new render-type ``colorpicker`` in the TCA field configuration, like e.g.

.. code-block:: php

   $GLOBALS['TCA']['tableName']['fieldName']['config']['renderType'] = 'colorpicker';
