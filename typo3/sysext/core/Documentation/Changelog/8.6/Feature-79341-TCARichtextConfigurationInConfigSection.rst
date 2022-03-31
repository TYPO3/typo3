.. include:: /Includes.rst.txt

==============================================================
Feature: #79341 - TCA richtext configuration in config section
==============================================================

See :issue:`79341`

Description
===========

A new config setting `enableRichtext` has been introduced. It enables richtext editing on the text field and replaces the old setting `defaultExtras`.


Impact
======

Setting `enableRichtext` will result in the text field being rendered with a richtext editor. Config example:

.. code-block:: php

    'columns' => [
        'content' => [
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
    ];

.. index:: Backend, FlexForm, RTE, TCA
