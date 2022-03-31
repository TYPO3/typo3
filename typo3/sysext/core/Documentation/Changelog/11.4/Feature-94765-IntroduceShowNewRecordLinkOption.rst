.. include:: /Includes.rst.txt

====================================================
Feature: #94765 - Introduce showNewRecordLink option
====================================================

See :issue:`94765`

Description
===========

Previously, it was not possible to disable the "new record" link in
TCA :php:`inline` elements, without simultaneously also disabling either the
"+" button in each inline records' header (using
:php:`['appearance']['enabledControls']['new']`) or all other
"level links" (using :php:`['appearance']['levelLinksPosition'] = 'none'`).

To allow integrators to disable this link without any further side
effects, the option :php:`showNewRecordLink` has been introduced
to TCA type :php:`inline`.

With this introduction, the already mentioned
:php:`['appearance']['enabledControls']['new']` option does from now on
only manage the display of the "+" button of each inline record and does
not longer affect the "New record" link.

Furthermore the :php:`['appearance']['levelLinksPosition']` option does
no longer support `none` as value. This option should only be used to
position the level links, not to hide them. This can be
achieved by setting the corresponding link specific options
:php:`showAllLocalizationLink`, :php:`showSynchronizationLink` and
:php:`showNewRecordLink` to :php:`false`. A TCA migration is in place,
replacing all TCA configurations, using the
:php:`['appearance']['levelLinksPosition']` option with `none` as value
and showing where code adaptations need to take place.

If not set, the new :php:`showNewRecordLink` option defaults to :php:`true`.

An example to disable the "New record" button:

.. code-block:: php

    'inlineField' => [
        'label' => 'Inline without New record link',
        'config' => [
            'type' => 'inline',
            'appearance' => [
                'showNewRecordLink' => false,
            ],
        ],
    ],

Impact
======

It's now possible to disable the "New record" link of TCA :php:`inline` elements
without any side effects.

.. index:: TCA, ext:backend
