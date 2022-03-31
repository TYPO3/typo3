.. include:: /Includes.rst.txt

===========================================
Feature: #94081 - TCA readOnly for t3editor
===========================================

See :issue:`94081`

Description
===========

The TCA :php:`'type' => 'text'` (texarea) based FormEngine
render type :php:`'renderType' => 't3editor'` now supports the
:php:`'readOnly' => true` option. If set, syntax highlighting
is applied as usual, but the corresponding text can not be edited.

Example:

.. code-block:: php

    't3editor_2' => [
        'label' => 't3editor_2',
        'description' => 'readOnly=true',
        'config' => [
            'type' => 'text',
            'renderType' => 't3editor',
            'format' => 'html',
            'readOnly' => true,
        ],
    ],


Impact
======

This minor feature allows rendering highlighted code without the edit option.

.. index:: Backend, TCA, ext:backend
