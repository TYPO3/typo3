.. include:: /Includes.rst.txt

=========================================================================
Feature: #88907 - Always enable filter in SelectMultipleSideBySide fields
=========================================================================

See :issue:`88907`

Description
===========

The filter functionality of fields :php:`type = select` with :php:`renderType = selectMultipleSideBySide`
is always enabled now.


Impact
======

Before:

.. code-block:: php

   'tsconfig_includes' => [
      'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tsconfig_includes',
      'config' => [
         'type' => 'select',
         'renderType' => 'selectMultipleSideBySide',
         'size' => 10,
         'items' => [],
         'enableMultiSelectFilterTextfield' => true,
         'softref' => 'ext_fileref'
      ]
   ],

Now just omit the line :php:`'enableMultiSelectFilterTextfield' => true`, the behaviour will stay the same.

A migration wizard is available that removes the option from your TCA and leaves a message where code
adaption has to take place.

.. index:: Backend, TCA, ext:core
