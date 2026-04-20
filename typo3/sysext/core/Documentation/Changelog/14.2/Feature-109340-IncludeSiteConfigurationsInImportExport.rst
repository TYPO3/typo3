..  include:: /Includes.rst.txt

..  _feature-109340-1742817600:

=================================================================
Feature: #109340 - Include site configurations in import / export
=================================================================

See :issue:`109340`

Description
===========

The import/export module now supports the inclusion of site configurations that belong
to exported page trees. Admin users can enable this via the
:guilabel:`Include site configurations for exported root pages` checkbox in the
:guilabel:`Advanced options` tab of the export module. This option is only
available to admin users.

When enabled and a page tree belonging to a root page with a site
configuration is exported, that configuration is embedded in the export file. On import,
the site configuration is restored with the remapped root page UID.

During import, embedded site configurations are processed after all records
have been written:

*   The `rootPageId` is remapped to the newly imported page UID.
*   If a site configuration already exists for the imported root page, the
    embedded configuration is skipped.
*   If the site identifier already exists but points to a different root page,
    a numeric suffix is appended (e.g. `my-site-1`) to avoid collisions.

The import preview screen shows embedded site configurations with their
identifier, base URL, and the title of the associated root page.

The CLI export command supports the same feature using the
`--include-site-configurations` option.

Impact
======

Site configurations can now be preserved across export and import cycles. This
simplifies the distribution of complete site packages and the migration of page
trees between TYPO3 instances.

..  index:: Backend, ext:impexp
