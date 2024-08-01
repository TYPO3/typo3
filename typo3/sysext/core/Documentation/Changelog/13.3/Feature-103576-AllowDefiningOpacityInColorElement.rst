.. include:: /Includes.rst.txt

.. _feature-103576-1720813198:

===================================================================
Feature: #103576 - Allow defining opacity in TCA type=color element
===================================================================

See :issue:`103576`

Description
===========

A new boolean property `opacity` has been added to the TCA configuration of
a TCA type `color` element to allow defining colors with an opacity using
the RRGGBBAA color notation.

..  code-block:: php

    'my_color' => [
        'label' => 'My Color',
        'config' => [
            'type' => 'color',
            'opacity' => true,
        ],
    ],


Impact
======

If `opacity` is enabled, editors can select not only a color but also its
opacity in a corresponding color element.

.. index:: TCA, ext:backend
