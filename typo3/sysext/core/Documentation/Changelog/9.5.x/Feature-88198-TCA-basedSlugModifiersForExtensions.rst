.. include:: /Includes.rst.txt

=========================================================
Feature: #88198 - TCA-based Slug modifiers for extensions
=========================================================

See :issue:`88198`

Description
===========

The new "slug" TCA type now includes a possibility to hook into the generation of a slug via custom TCA generation options.

Hooks can be registered via

.. code-block:: php

    $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['generatorOptions']['postModifiers'][] = My\Class::class . '->method';

in :file:`EXT:myextension/Configuration/TCA/Overrides/$tableName.php`, where $tableName can be a table like `pages` and
`$fieldName` matches the slug field name, e.g. `slug`.

Example:

.. code-block:: php

    $GLOBALS['TCA']['pages']['columns']['slug']['config']['generatorOptions']['postModifiers'][] = My\Class::class . '->modifySlug';

The method then receives a parameter array with the following values:

.. code-block:: php

    [
        'slug' ... the slug to be used
        'workspaceId' ... the workspace ID, "0" if in live workspace
        'configuration' ... the configuration of the TCA field
        'record' ... the full record to be used
        'pid' ... the resolved parent page ID
        'prefix' ... the prefix that was added
        'tableName' ... the table of the slug field
        'fieldName' ... the field name of the slug field
   ];

All hooks need to return the modified slug value.


Impact
======

Any extension can modify a specific slug, for instance only for a specific part of the page tree.

It is also possible for extensions to implement custom functionality like "Do not include in slug generation" as known from RealURL.

.. index:: TCA
