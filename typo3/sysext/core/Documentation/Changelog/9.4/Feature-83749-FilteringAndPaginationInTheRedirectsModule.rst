.. include:: /Includes.rst.txt

==================================================================
Feature: #83749 - Filtering and Pagination in the redirects module
==================================================================

See :issue:`83749`

Description
===========

The backend module "Redirects" received filtering and pagination to improve the overall usability.

The list of redirects can be filtered by:

- The source host
- The source path
- The destination (either the path or the Page ID)
- The target status code

All filters are concatenated by a logical AND.

Pagination is set to 50 records per page.

Some minor usability improvements have been made as well:

- The source path crops after 100 characters to keep the table from expanding too much.
- The destination column also shows the Page ID if the target is a page.
- Redirects are sorted by source host (1st) and source path (2nd).


Impact
======

With these improvements it is now possible to easily manage a big amount of redirect records.

.. index:: Backend, ext:redirects
