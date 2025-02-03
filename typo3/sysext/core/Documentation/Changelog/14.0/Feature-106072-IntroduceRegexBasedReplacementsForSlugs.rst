..  include:: /Includes.rst.txt

..  _feature-106072-1738662608:

===============================================================
Feature: #106072 - Introduce regex based replacements for slugs
===============================================================

See :issue:`106072`

Description
===========

Adds a second replacement config array to provide regex based
definitions. This way it's possible to define case-insensitive
or wildcard replacements.

..  note::

    The regexp based replacement operates on the original field values,
    which are not sanitized yet. That means, not lowercased or otherwise
    processed and only on field level for the record configured with the
    :php:`['generatorOptions']['fields']` option. Additional, not automatic
    character detection and escaping is processed and needs to be taken care
    when configure patterns. Keep these points in mind.

Impact
======

Slug fields have now a new `regexReplacements` configuration array inside `generatorOptions`.

..  code-block:: php

    'generatorOptions' => [
      'regexReplacements' => [
          '/foo/i' => 'bar', // case-insensitive replace of Foo, foo, FOO,... with "bar", ignoring casing
          '/\(.*\)/' => '',  // Remove string wrapped in parentheses
          '@\(.*\)@' => '',  // Remove string wrapped in parentheses with custom regex delimiter
       ],
    ],

..  index:: TCA, ext:core
