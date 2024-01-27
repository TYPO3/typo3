.. include:: /Includes.rst.txt

.. _breaking-102985-1706549304:

==========================================================
Breaking: #102985 - Declare Indexed Search as Content Type
==========================================================

See :issue:`102985`

Description
===========

The plugin configuration of the "Indexed Search" plugin has been changed. The
plugin is now configured as a proper "content element" using the `CType` plugin
type. This allows to further shrink down the `CType=list` and
`list_type=<plugin_name>` combination, like it has already been done with other
plugins, e.g. the "Frontend Login" plugin.

Impact
======

The "Indexed Search" plugin is now configured as a content element, using
`CType=indexedsearch_pi2` instead of the `CType=list` and
`list_type=indexedsearch_pi2` combination.

An upgrade wizard is in place, migrating existing content elements as well
as corresponding backend user group permissions.

Affected installations
======================

All installations with extensions, relying on the "Indexed Search" plugin
using the `CType=list` and `list_type=indexedsearch_pi2` combination. This
might be done in custom database queries, frontend data providers or in
TSconfig. Also in cases where the corresponding backend user group permissions
(:sql:`be_groups.explicit_allowdeny`) are manually evaluated.

Migration
=========

Execute the `Migrate "Indexed Search" plugins to content elements.` upgrade
wizard to automatically migrate existing records. Make sure to have the
`Migrate backend groups "explicit_allowdeny" field to simplified format.`
upgrade wizard executed beforehand.

Additionally, adjust any place relying on the plugin using the
`CType=list` and `list_type=indexedsearch_pi2` combination.

Example SQL migrations:

.. code-block:: sql

    -- Before
    SELECT * FROM tt_content WHERE CType = 'list' AND list_type = 'indexedsearch_pi2';

    -- After
    SELECT * FROM tt_content WHERE CType = 'indexedsearch_pi2';

.. code-block:: sql

    -- Before
    SELECT * FROM be_groups WHERE explicit_allowdeny LIKE '%tt_content:list_type:indexedsearch_pi2%';

    -- After
    SELECT * FROM be_groups WHERE explicit_allowdeny LIKE '%tt_content:CType:indexedsearch_pi2%';

.. index:: TCA, NotScanned, ext:indexed_search
