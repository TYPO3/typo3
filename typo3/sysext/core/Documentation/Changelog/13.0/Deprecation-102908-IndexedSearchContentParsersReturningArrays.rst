.. include:: /Includes.rst.txt

.. _deprecation-102908-1706081017:

======================================================================
Deprecation: #102908 - Indexed Search content parsers returning arrays
======================================================================

See :issue:`102908`

Description
===========

Content parsers implemented in Indexed Search return an array solely defined for
the internal indexer. It is now encouraged to return an instance of
:php:`\TYPO3\CMS\IndexedSearch\Dto\IndexingDataAsString` instead.


Impact
======

Returning an array is deprecated. The indexing process will catch such cases,
convert the result to an :php:`IndexingDataAsString` object and raise a
deprecation warning.


Affected installations
======================

All installations with custom content parsers are affected, which very
unlikely do exist.


Migration
=========

Let the custom content parser return an instance of :php:`IndexingDataAsString`.
Either use the constructor, or the static helper method :php:`fromArray()`.

In both cases, the following input is accepted:

* :php:`title`
* :php:`body`
* :php:`keywords`
* :php:`description`

Examples
--------

..  code-block:: php

    // Using constructor
    return new IndexingDataAsString('<title>', '<body>', '<keywords>', '<description>');


..  code-block:: php

    // Using static helper
    return IndexingDataAsString::fromArray([
        'title' => '<title>',
        'body' => '<body>',
        'keywords' => '<keywords>',
        'description' => '<description>',
    ]);

.. index:: PHP-API, NotScanned, ext:indexed_search
