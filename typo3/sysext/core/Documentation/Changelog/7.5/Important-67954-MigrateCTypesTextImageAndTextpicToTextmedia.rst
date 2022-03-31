
.. include:: /Includes.rst.txt

=======================================================================
Important: #67954 - Migrate CTypes text, image and textpic to textmedia
=======================================================================

See :issue:`67954`

Description
===========

EXT:fluid_styled_content simplifies the available CTypes which results in types `text`, `image` and `textpic`
being dropped in favor of type `textmedia`. The relation field is changed from `image` to `media_references`.


Impact
======

When EXT:fluid_styled_content is installed and EXT:css_styled_content isn't installed an "Upgrade Wizard" is available in the
Install Tool to migrate all CE of type `text`, `image` or `textpic` to type `textmedia`.
Furthermore the relations to field `image` will be adjusted to `media_references` for the migrated CE's.

The frontend rendering has to be adjusted so the new type is rendered after the migration is done.
Migration will not happen automatically but has to be triggered manually.


Affected Installations
======================

All installations where EXT:fluid_styled_content is installed and EXT:css_styled_content isn't installed.


Migration
=========

First un-install EXT:css_styled_content and install EXT:fluid_styled_content. After that an "Upgrade Wizard" will be
available in the install tool to migrate all existing CE elements of type `text`, `image` or `textpic` to type `textmedia`.


.. index:: ext:fluid_styled_content, ext:css_styled_content, Backend, Frontend
