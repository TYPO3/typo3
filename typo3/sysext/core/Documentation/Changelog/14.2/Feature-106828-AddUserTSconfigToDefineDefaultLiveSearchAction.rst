..  include:: /Includes.rst.txt

..  _feature-106828-1751343863:

=========================================================================
Feature: #106828 - Add User TSconfig to define default Live Search action
=========================================================================

See :issue:`106828`

Description
===========

A new User TSconfig :typoscript:`options.liveSearch.actions` has been introduced
to allow an integrator to define default behaviors of a search result.

**Available actions:**

+-------------+------------------------------------------------------------------------------------+
| Action      | Description                                                                        |
+=============+====================================================================================+
| `edit`      | This opens the editing form for the record (Default for all tables except `pages`) |
+-------------+------------------------------------------------------------------------------------+
| `layout`    | This opens the page in the page module (Default for table `pages`)                 |
+-------------+------------------------------------------------------------------------------------+
| `list`      | This opens the storage page of the record in the "Records" module                  |
+-------------+------------------------------------------------------------------------------------+
| `preview`   | This opens the record in the frontend                                              |
+-------------+------------------------------------------------------------------------------------+

.. important::

    Action `layout` can only be used for table :sql:`pages` and :sql:`tt_content`

Examples
--------

**Set default for all tables**

:typoscript:`options.liveSearch.actions.default = edit`

**Set default for table tt_content**

:typoscript:`options.liveSearch.actions.tt_content.default = layout`

**Set default for custom table**

:typoscript:`options.liveSearch.actions.my_table.default = preview`


.. note::

    To use `preview` for a custom record, a valid preview configuration
    (`TCEMAIN.preview`) must exist for the table.


Impact
======

Live Search default actions can now be configured via User TSconfig. Integrators
can define global or per-table behavior for search results, improving backend
workflows.

The default behavior for pages has been changed to `layout`
to improve the user workflow.

..  index:: Backend, TSConfig, ext:backend
