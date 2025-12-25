..  include:: /Includes.rst.txt

..  _feature-108557-1768611915:

===============================================================
Feature: #108557 - TCA option allowedRecordTypes for Page Types
===============================================================

See :issue:`108557`

Description
===========

A new TCA option :php:`allowedRecordTypes` is introduced for Page Types to
configure allowed database tables for specific types ("doktype").

.. code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    // Allow any record on that Page Type.
    $GLOBALS['TCA']['pages']['types']['116']['allowedRecordTypes'] = ['*'];

    // Only allow specific tables on that Page Type.
    $GLOBALS['TCA']['pages']['types']['116']['allowedRecordTypes'] = ['tt_content', 'my_custom_record'];

The array can contain a list of table names or a single entry with an asterisk `*`
to allow all types.

Per default only the tables `pages`, `sys_category`, `sys_file_reference` and
`sys_file_collection` are allowed, if not overridden with this option.

The defaults are extended, if TCA tables enable the option
`ctrl.security.ignorePageTypeRestriction`. Again, this won't be considered if
:php:`allowedRecordTypes` is set. They need to be configured there as well.

Impact
======

The allowed record types for pages can now be configured in TCA. This
centralizes the configuration for Page Types and further slims down
the need for :file:`ext_tables.php`, which was utilized before.

..  index:: TCA, ext:core
