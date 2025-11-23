..  include:: /Includes.rst.txt

..  _feature-106072-1738662608:

===============================================================
Feature: #106072 - Introduce regex-based replacements for slugs
===============================================================

See :issue:`106072`

Description
===========

A second replacement configuration array has been added to support
regular expression (regex)-based definitions. This allows defining
case-insensitive or wildcard replacements for slug generation.

..  note::

    Regular expression replacements are applied **before** any sanitation
    of the field values takes place. This means:

    *   Values are still in their original form - not lowercased or otherwise processed.
    *   The replacements apply only to the fields defined in
        :php:`['generatorOptions']['fields']`.
    *   No automatic escaping or character detection is performed, so
        patterns must be written carefully.

    Keep these points in mind when defining regex patterns.

Impact
======

Slug fields now support a new `regexReplacements` configuration array
inside `generatorOptions`.

..  code-block:: php
    :caption: Example TCA configuration

    $GLOBALS['TCA'][$table]['columns']['slug']['config']['generatorOptions']['regexReplacements'] => [
        // Case-insensitive replacement of Foo, foo, FOO, etc. with "bar"
        '/foo/i' => 'bar',
        // Remove string wrapped in parentheses
        '/\(.*\)/' => '',
        // Same, using a custom regex delimiter
        '@\(.*\)@' => '',
    ];

..  index:: TCA, ext:core
