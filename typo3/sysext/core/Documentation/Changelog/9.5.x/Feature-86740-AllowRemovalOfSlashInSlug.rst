.. include:: /Includes.rst.txt

============================================
Feature: #86740 - Replace characters in slug
============================================

See :issue:`86740`

Description
===========

The configuration of the TCA type `slug` has been extended by the setting `replace`.
It allows to replace strings of a slug part.


Impact
======

Especially for enhancing the site configuration it might be useful to set the configuration.

Easy example
------------

By using the following configuration, slashes are removed from the slug.

.. code-block:: php

    'type' => 'slug',
    'config' => [
        'generatorOptions' => [
            'fields' => ['title'],
            'replacements' => [
                '/' => ''
            ],
        ]
        'fallbackCharacter' => '-',
        'prependSlash' => true,
        'eval' => 'uniqueInPid'
    ]

Advanced examples
-----------------

The following configuration uses more replacements:

.. code-block:: php

    'type' => 'slug',
    'config' => [
        'generatorOptions' => [
            'fields' => ['title'],
            'replacements' => [
                '(f/m)' => '',
                '/' => '-'
            ],
        ]
        'fallbackCharacter' => '-',
        'prependSlash' => true,
        'eval' => 'uniqueInPid'
    ]

This will change the provided slug `Some Job in city1/city2 (f/m)` to `some-job-in-city1-city2`.

.. index:: Frontend, ext:core
