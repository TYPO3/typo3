..  include:: /Includes.rst.txt

..  _feature-108557-1768611915:

===============================================================
Feature: #108557 - TCA option allowedRecordTypes for page types
===============================================================

See :issue:`108557`

Description
===========

A new TCA option :php:`allowedRecordTypes` is introduced for page types to
configure which database tables are allowed for specific types (`doktype`).

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    // Allow any record on that page type.
    $GLOBALS['TCA']['pages']['types']['116']['allowedRecordTypes'] = ['*'];

    // Allow only specific tables on that page type.
    $GLOBALS['TCA']['pages']['types']['116']['allowedRecordTypes'] = [
        'tt_content',
        'my_custom_record',
    ];

The array can contain a list of table names or a single asterisk entry (`*`)
to allow all record types.

By default, only the tables `pages`, `sys_category`, `sys_file_reference`, and
`sys_file_collection` are allowed if this option is not overridden.

The defaults are extended if TCA tables enable the option
`ctrl.security.ignorePageTypeRestriction`. Again, this is not considered if
:php:`allowedRecordTypes` is set. These tables must then also be configured
there.

Impact
======

The allowed record types for pages can now be configured in TCA. This
centralizes the configuration for page types and further reduces the need for
:file:`ext_tables.php`, which was used previously.

..  index:: TCA, ext:core
