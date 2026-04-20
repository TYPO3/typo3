..  include:: /Includes.rst.txt

..  _feature-106828-1751343863:

=========================================================================
Feature: #106828 - Add user TSconfig to define default live search action
=========================================================================

See :issue:`106828`

Description
===========

A new user TSconfig option :typoscript:`options.liveSearch.actions` has
been introduced to allow integrators to define the default behavior of a
search.

Available actions:

*   `edit`: Opens the edit form of the record. This is the default for
    all tables except `pages`.
*   `layout`: Opens the page in the Page module. This is the default for
    the `pages` table.
*   `list`: Opens the storage page of the record in the Record List
    module.
*   `preview`: Opens the record in the frontend.

..  important::

    The `layout` action can only be used for the :sql:`pages` and
    :sql:`tt_content` tables.

Examples
========

Set the default for all tables:

..  code-block:: typoscript

    options.liveSearch.actions.default = edit

Set the default for the `tt_content` table:

..  code-block:: typoscript

    options.liveSearch.actions.tt_content.default = layout

Set the default for a custom table:

..  code-block:: typoscript

    options.liveSearch.actions.my_table.default = preview

..  note::

    To use `preview` for a custom record, a valid preview configuration
    must exist for the table in `TCEMAIN.preview`.

Impact
======

The default actions of live search results can now be configured with
user TSconfig. Integrators can define global and table-specific behavior
for search results, improving backend workflows.

The default behavior for `pages` has been changed to `layout` to improve
the user workflow.

..  index:: Backend, TSConfig, ext:backend
