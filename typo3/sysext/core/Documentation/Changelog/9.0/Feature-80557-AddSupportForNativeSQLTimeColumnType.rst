.. include:: /Includes.rst.txt

=============================================================
Feature: #80557 - Add support for native SQL time column type
=============================================================

See :issue:`80557`

Description
===========

It is now possible to use the native SQL time column type in `TCA`.

It is required to set the property :php:`dbType` to the value `time`.
In addition the field `eval` property must be set to `time` or `timesec`.

.. code-block:: php

   $GLOBALS['TCA']['tx_myext_table']['columns']['some_time']['config'] = [
      'type' => 'input',
      'dbType' => 'time',
      'eval' => 'time'
   ];

.. index:: Backend, Database, TCA
