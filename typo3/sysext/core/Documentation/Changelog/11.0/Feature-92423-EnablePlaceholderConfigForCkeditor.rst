.. include:: /Includes.rst.txt

========================================================
Feature: #92423 - Enable placeholder config for ckeditor
========================================================

See :issue:`92423`

Description
===========

After the update of ckeditor to version 4.15.0, a new ckeditor plugin, called
**Editor Placeholder**, is now available. More information along with an example
can be found in the official documentation_.

The placeholder configuration of TCA type `text` is now fed into the ckeditor plugin:

.. code-block:: php

    'bodytext' => [
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.text',
        'config' => [
            'placeholder' => 'This is a placeholder',
            'type' => 'text',
            'enableRichtext' => true,
        ]
    ]


Impact
======

Editors can now be supported by providing a placeholder text also for the ckeditor.

.. _documentation: https://ckeditor.com/docs/ckeditor4/latest/examples/editorplaceholder.html

.. index:: Backend, ext:rte_ckeditor
