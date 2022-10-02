.. include:: /Includes.rst.txt

.. _feature-96983:

===================================
Feature: #96983 - TCA type "folder"
===================================

See :issue:`96983`

Description
===========

A new TCA type :php:`folder` has been introduced, which replaces the old
combination of :php:`type => 'group'` together with
:php:`internal_type => 'folder'`. Other than that, there is nothing new about
this type.

Example usage:

..  code-block:: php

    'columns' => [
        'aColumn' => [
            'config' => [
                'type' => 'folder',
            ],
        ],
    ],

Impact
======

You may now use the new TCA type :php:`folder` as a quicker way to define a
field, which can select multiple folders in an element browser window.

.. index:: Backend, TCA, ext:backend
