.. include:: /Includes.rst.txt

============================================================
Feature: #94210 - Information about inherited backend layout
============================================================

See :issue:`94210`

Description
===========

When editing a page record, the field :php:`pages.backend_layout_next_level` can
be used to apply a backend layout to all subpages.

This can make it difficult for the editor to determine the currently applied
backend layout. To help the editor in case of an inherited layout a message
is now displayed below the :php:`pages.backend_layout` field label via a
new FormEngine field information.


.. index:: Backend, TCA, ext:backend
