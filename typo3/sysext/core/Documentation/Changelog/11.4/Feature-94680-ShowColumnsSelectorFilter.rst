.. include:: /Includes.rst.txt

==============================================
Feature: #94680 - Show columns selector filter
==============================================

See :issue:`94680`

Description
===========

In :issue:`94474`, the column selector in the record list, formerly known
as "field selector", got improved by adding a couple of actions, such
as "check all" and by moving the selection into a modal instead of a dropdown.
However, since there are tables, e.g. :sql:`pages` or :sql:`tt_content`, which
contain a lot of columns, it could sometimes still be unnecessarily hard
to find a specific column in such a list.

Therefore, the columns selectors' action bar has been extended for
a new filter, which can be used to quickly find the desired column
in such large lists.

When the filter is active - at least one character was entered - all other
actions are bound to the current filter result. This means, when using the
"check all" action, while the list is filtered, the action is only applied
to the currently visible items. This comes in handy in case a group of
columns, sharing the same name (e.g. "backend layouts" in :sql:`pages`) should
be selected.


Impact
======

It's now possible to filter the list of columns in the "Show column selector"
of the recordlist module.

.. index:: Backend, ext:recordlist
