.. include:: /Includes.rst.txt

.. _feature-103706-1713883119:

=========================================================
Feature: #103706 - Default search level for record search
=========================================================

See :issue:`103706`

Description
===========

When searching for records in the :guilabel:`Web > List` module as well as
the database browser, it's possible to select the search levels (page tree
levels to respect in the search).

An editor is therefore able to select between the current page, a couple of
defined levels (e.g. 1, 2, 3) as well as the special "infinite levels".

Those options can already be extended using the TSconfig option
:typoscript:`mod.web_list.searchLevel.items`.

Next to this, a new TSconfig option :typoscript:`mod.web_list.searchLevel.default`
has been introduced, which allows to define one of the available level options
as the default level to use.

Example
-------

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/page.tsconfig

    # Set the default search level to "infinite levels"
    mod.web_list.searchLevel.default = -1


Impact
======

It's now possible to define a default search level using the new page
TSconfig option :typoscript:`mod.web_list.searchLevel.default`.

.. index:: Backend, TSConfig, ext:backend
