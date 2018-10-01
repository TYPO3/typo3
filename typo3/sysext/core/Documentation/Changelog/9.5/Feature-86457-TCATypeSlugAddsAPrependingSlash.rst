.. include:: ../../Includes.txt

=======================================================
Feature: #86457 - TCA Type Slug adds a prepending slash
=======================================================

See :issue:`86457`

Description
===========

The new TCA type slug field now hard-codes a slash as a prefix for all pages, as this is
mandatory for URL resolving and ensuring a uniqueness within a site.

However, for slug types within regular records, it is not necessary to do so, therefore, the slash
is never prepended on a "regular" slug field.

If - in some special cases - the "slug" field should contain a slash (due to e.g. nested categories
with speaking segments), a new option `prependSlash` is added to TCA type slug.


Impact
======

Third-party extensions using the slug field now receive a slug value without a slash, and
can use this as a regular - sanitized - slug field. It is however recommended to use the
`uniqueInPid` eval option to ensure uniqueness.

If a nested record structure is given, it is recommended to use the new option `prependSlash`
by setting it to :php:`true`.

.. code-block:: php

    'type' => 'slug',
    'config' => [
        'generatorOptions' => [
            'fields' => ['title'],
        ]
        'fallbackCharacter' => '-',
        'prependSlash' => true,
        'eval' => 'uniqueInPid'
    ]

.. index:: TCA
