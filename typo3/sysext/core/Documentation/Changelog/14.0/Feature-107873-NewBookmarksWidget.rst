..  include:: /Includes.rst.txt

..  _feature-107873-1761641914:

=======================================
Feature: #107873 - New Bookmarks Widget
=======================================

See :issue:`107873`

Description
===========

A new Bookmarks dashboard widget has been added to display the current user's
bookmarks directly in the TYPO3 Dashboard. This allows editors and administrators
to quickly access frequently used pages, records, or modules without navigating
through the backend menu.

Building upon the configurable dashboard widgets functionality introduced in
:ref:`feature-107036-1738837673`, the Bookmarks widget supports the following
settings:

*Widget Label*
    Custom title for the widget instance. If left empty and a specific group
    is selected, the group title is used as widget label automatically

*Group*
    Filter bookmarks by a specific bookmark group, or show all groups.

*Limit*
    Maximum number of bookmarks to display (default: 10).

Impact
======

*   Editors can add the Bookmarks widget to their dashboard for quick access to
    their saved bookmarks.
*   Each widget instance can be configured independently, allowing multiple
    Bookmarks widgets with different group filters on the same dashboard.
*   Existing bookmarks are displayed automatically without additional setup.

..  index:: Backend, ext:dashboard
