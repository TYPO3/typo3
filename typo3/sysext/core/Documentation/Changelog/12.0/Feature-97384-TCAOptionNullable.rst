.. include:: /Includes.rst.txt

.. _feature-97384:

=======================================
Feature: #97384 - TCA option "nullable"
=======================================

See :issue:`97384`

Description
===========

In order to further thin out the TCA :php:`eval` option, the `null` value has
been extracted into its own option: :php:`nullable`, which is a `boolean` value.

Example:

..  code-block:: php

    'columns' => [
       'nullable_column' => [
           'title' => 'A nullable field',
           'config' => [
               'nullable' => true,
               'eval' => 'trim',
           ],
       ],
    ],

Impact
======

It is now possible to define TCA fields as nullable by setting the
:php:`nullable` option to :php:`true`. The database field should have the
according :sql:`NULL` option set.

.. index:: TCA, ext:backend
