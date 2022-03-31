.. include:: /Includes.rst.txt

=======================================
Feature: #78899 - TCA maxitems optional
=======================================

See :issue:`78899`

Description
===========

The :code:`TCA` config setting :code:`maxitems` for :code:`type=select` and :code:`type=group` fields is now an optional setting that defaults to a high value (99999) instead of 1 as before.


Impact
======

Fields that typically relate to multiple relations like the group element and some select elements no longer need :code:`maxitems` set to some value to enable multiple values.

Example before:

.. code-block:: php

    aField => [
        'config' => [
            'type' => `select',
            'renderType' => 'multipleSideBySide',
            'maxitems' => 99999,
        ],
    ],

Example after:

.. code-block:: php

    aField => [
        'config' => [
            'type' => `select',
            'renderType' => 'multipleSideBySide',
        ],
    ],

This simplifies :code:`TCA` of those fields and removes a cross dependency between :code:`renderType` and :code:`maxitems`.

.. index:: Backend, TCA
