.. include:: /Includes.rst.txt

===================================================
Feature: #83556 - Add toggle switches to FormEngine
===================================================

See :issue:`83556`

Description
===========

In order to give FormEngine a fresher look we add the following `renderTypes` to `type=check`.

renderType checkboxToggle
=========================

A pure toggle switch. No additional configuration is necessary.

Its state can be inverted via `invertStateDisplay`.


renderType checkboxLabeledToggle
================================

A toggle switch where both states can be labelled (ON/OFF, Visible / Hidden or alike).

Its state can be inverted via `invertStateDisplay`

.. code-block:: php

   'items' => [
      [
         0 => 'foo',
         1 => '',
         'labelChecked' => 'Enabled',
         'labelUnchecked' => 'Disabled',
         'invertStateDisplay' => false
      ]
   ]


renderType default
=============================

A toggle that toggles between two icon identifiers.

By default the toggle icons are visually designed to mimic a checkbox.

Its state can be inverted via `invertStateDisplay`.

.. code-block:: php

   'items' => [
      [
         0 => 'foo',
         1 => '',
         'iconIdentifierChecked' => 'styleguide-icon-toggle-checked',
         'iconIdentifierUnchecked' => 'styleguide-icon-toggle-checked',
         'invertStateDisplay' => false
      ]
   ]

.. index:: Backend, PHP-API, TCA
