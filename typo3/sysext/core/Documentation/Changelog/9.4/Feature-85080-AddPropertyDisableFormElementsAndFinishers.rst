.. include:: ../../Includes.txt

=====================================================================
Feature: #85080 - Add property to disable form elements and finishers
=====================================================================

See :issue:`85080`

Description
===========

A a new rendering option for form elements and finishers has been introduced named :yaml:`enabled`
which takes a boolean value (:yaml:`true` or :yaml:`false`).

Setting :yaml:`enabled: true` for a form element renders it in the frontend and enables processing
of its value including property mapping and validation. Setting :yaml:`enabled: false` instead
disables the form element in the frontend.

Setting :yaml:`enabled: true` for a finisher executes it when submitting forms, setting :yaml:`enabled: false`
skips the finisher instead.

By default :yaml:`enabled` is set to :yaml:`true`.


Usage:
======

All form elements and finishers except the root form element and the first form page can be enabled
or disabled.

An example:

.. code-block:: yaml

    type: Form
    identifier: test
    label: test
    prototypeName: standard
    renderables:
      -
        type: Page
        identifier: page-1
        label: Step
        renderables:
          -
            type: Text
            identifier: text-1
            label: Text
            defaultValue: ''
          -
            type: Checkbox
            identifier: checkbox-1
            label: Checkbox
            renderingOptions:
              enabled: true
      -
        type: SummaryPage
        identifier: summarypage-1
        label: 'Summary step'
        renderingOptions:
          enabled: false
    finishers:
      -
        identifier: Confirmation
        options:
          message: thx
      -
        identifier: Confirmation
        options:
          message: 'thx again'
          renderingOptions:
            enabled: '{checkbox-1}'

In this example the form element :yaml:`checkbox-1` has been enabled explicitly but it is fine to
leave this out since this is the default state (which can be seen in the element :yaml:`text-1`).

The :yaml:`summarypage-1` has been disabled completely, for example to temporarily remove it from
the form.

The second :yaml:`Confirmation` finisher takes the fact into account that finishers can refer to
form values. It is only enabled if the form element :yaml:`checkbox-1` has been activated by the
user. Otherwise the finisher is skipped.

.. index:: Frontend, ext:form, NotScanned
