..  include:: /Includes.rst.txt

..  _feature-111406-1794394738:

======================================================================
Feature: #111406 - Find the subpages of a page with one database query
======================================================================

See :issue:`111406`

Description
===========

The backend finds the descendants of one or more pages - their subpages, the
subpages of these and so on, down to a given depth - with a single recursive
database query (a common table expression). The query is built by the new
internal service
:php:`\TYPO3\CMS\Core\Domain\Repository\PageDescendantsRepository`.

Previously, the page tree was walked in PHP with one database query per page.
Several places in TYPO3 had their own implementation of this, each handling
workspaces, languages and permissions differently. With 780 pages below a page,
this resulted in up to 907 queries.

The pages are found as the backend sees the page tree:

*   Only pages of the default language are part of the page tree.
*   Pages are resolved for the workspace of the backend user: a page moved in
    the workspace is found at its new position, and pages created in the
    workspace are included.
*   Pages are ordered depth-first: each page is followed by its subpages, and
    pages with the same parent page are ordered by their :sql:`sorting` value.
*   Access restrictions, such as the page permissions of the backend user, are
    evaluated on the live page, as page permissions are not versioned. A page
    not matching them is left out together with its subpages.

Queries restricted to the records of a page and the pages below it no longer
collect the uids of these pages first and add them as :sql:`pid IN (...)`,
which made queries slow and exceeded the number of placeholders a database
accepts in one statement for large page trees. The database resolves the pages
of the tree within the query as a subquery instead. If the queried table lives
on another database connection than the :sql:`pages` table, a list of the page
uids is used as before.

The following parts of TYPO3 find subpages this way now:

*   :php:`\TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository::getFlattenedPages()`,
    used by the live search and the suggest wizard
*   the record list with search levels, the log module with a depth and the
    recycler, which restrict their queries with
    :php:`createPageTreeConstraint()`
*   the workspaces module, to collect the versions below a page
*   Extbase backend modules with :typoscript:`persistence.recursive`
*   the query search in the "System > Database" module
*   the indexed search administration
*   the page trees of the "Permissions" module, the "Localization Overview",
    the export of :php:`EXT:impexp` and the indexed search administration, which
    are rendered by :php:`\TYPO3\CMS\Backend\Tree\View\PageTreeView` and
    :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView`
*   the "Pagetree Overview" module
*   the link validator
*   the update of slugs of subpages and the integrity check of redirects

Impact
======

Finding the subpages of a page needs one database query, independent of the
size and depth of the page tree. Measured with 780 pages below a page:

==========================================================  ==========  =========
Usage                                                       Before      After
==========================================================  ==========  =========
:php:`PageTreeRepository::getFlattenedPages()`, live        6 queries   2 queries
:php:`PageTreeRepository::getFlattenedPages()`, workspace   130         5
Workspaces module, versions below a page                    907         1
Extbase :typoscript:`persistence.recursive` in backend      905         1
"System > Reports > Record Statistics", per statistic       1561        1
==========================================================  ==========  =========

The record list, the log module and the recycler need no query to restrict
their queries to a page tree anymore, and their queries contain no list of page
uids. The record list searches with search levels in the workspace of the
backend user, so records on pages that only exist in this workspace are found.

Extbase backend modules with :typoscript:`persistence.recursive` and the link
validator no longer receive the uids of workspace versions as page ids.

Page trees rendered by :php:`AbstractTreeView` show a page moved in a workspace
at its new position.

When the slug of a page is changed in a workspace, the slugs of its subpages
are updated from their state in the workspace: a changed slug of a subpage is
kept, and pages moved below the changed page in the workspace are updated as
well.

The "Record Statistics" report collects the complete page table - including
translations, deleted pages and workspace versions - and needed two queries per
page so far. It now fetches all pages with one query and builds the page tree
in PHP. The records of each table are counted per page, so the report no longer
sends the uids of all pages to the database, which failed with too many
placeholders in large installations. The method
:php:`DatabaseIntegrityCheck::genTree()` no longer has the internal
:php:`$versions` argument.

Page trees and page lists end 100 levels below the page they start from.

The clause handed to :php:`AbstractTreeView::init()` and
:php:`PageTreeRepository::setAdditionalWhereClause()` is evaluated on the live
page, also in a workspace. Its columns may be unqualified or qualified with
:sql:`pages`. A tree of :php:`AbstractTreeView` is built from the table
:sql:`pages` only, and an order other than :sql:`sorting` is applied in PHP:
the fields must be part of the selected fields, strings are compared in natural
order, ignoring case.

..  index:: Backend, Database, PHP-API, ext:core
