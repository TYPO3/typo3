
.. include:: /Includes.rst.txt

===================================================
Deprecation: #73209 - Deprecated flex page TSConfig
===================================================

See :issue:`73209`

Description
===========

Setting page TSConfig values `PAGE_TSCONFIG_ID`, `PAGE_TSCONFIG_IDLIST` and
` PAGE_TSCONFIG_STR` for flexform fields globally has been marked as deprecated, specific
fields must be set now.

Example for a now deprecated global TSConfig value:

`TCEFORM.tt_content.pi_flexform.PAGE_TSCONFIG_ID = 42`

This should now be restricted to specific fields of the flexfrom data structure, if for example
the flexform `foreign_table_where` of field `settings.categories` of a `tt_content` plugin`s
data structure should be set, the new page TSConfig option should look like:

`TCEFORM.tt_content.pi_flexform.theDataStructure.theSheet.settings\.categories.PAGE_TSCONFIG_ID = 42`

Note that any dots within the field name must be escaped with '\\', this is a typical
scenario for extbase.


Impact
======

This pageTSConfig cannot be set for section elements anymore: `PAGE_TSCONFIG_ID`,
`PAGE_TSCONFIG_IDLIST` and `PAGE_TSCONFIG_STR` do not have any effect on repeatable
elements.


Affected Installations
======================

Installations that set `PAGE_TSCONFIG_ID`, `PAGE_TSCONFIG_IDLIST` and `PAGE_TSCONFIG_STR`
for flexform fields globally should be restricted to set those values for single elements.


Migration
=========

Search for `PAGE_TSCONFIG_ID`, `PAGE_TSCONFIG_IDLIST` and `PAGE_TSCONFIG_STR` and restrict
them to single fields as outlined above.

.. index:: PHP-API, TSConfig, FlexForm
