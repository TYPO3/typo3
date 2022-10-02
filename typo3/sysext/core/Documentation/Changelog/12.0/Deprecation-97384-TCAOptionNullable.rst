.. include:: /Includes.rst.txt

.. _deprecation-97384:

=====================================================================
Deprecation: #97384 - TCA option "eval=null" replaced with "nullable"
=====================================================================

See :issue:`97384`

Description
===========

The TCA option :php:`eval=null` has been replaced with the boolean option
:php:`nullable`.

Impact
======

The TCA option :php:`eval=null` will be automatically migrated to
:php:`'nullable' => true`. The migration will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

All installations defining the :php:`null` value in their :php:`eval` list.

Migration
=========

To migrate your TCA add the TCA option :php:`'nullable' => true` and remove the
:php:`null` value from the field's :php:`eval` list.

..  code-block:: php

    // Before

    'columns' => [
       'nullable_column' => [
           'title' => 'A nullable field',
           'config' => [
               'eval' => 'null',
           ],
       ],
    ],

    // After

    'columns' => [
       'nullable_column' => [
           'title' => 'A nullable field',
           'config' => [
               'nullable' => true,
           ],
       ],
    ],

.. index:: TCA, NotScanned, ext:backend
