:navigation-title: Backend Module

..  include:: /Includes.rst.txt

..  _administration:
..  _module:

=========================
Backend module "Indexing"
=========================

The system extension Indexed Search provides the backend module
:guilabel:`Web > Indexing` where administrators or power editors can
view search statistics and remove listings from the search index.

..  figure::  /Images/Module/Overview.png
    :alt: TYPO3 backend overview with the Indexing module opened

    Open the module via :guilabel:`Web > Indexing`

..  tip:: If the backend module "Indexing" is not visible and you have an
    editor account your permissions might not be sufficient.

    If you have an administrator account see
    `Trouble shooting: Backend module "Indexing" does not show <https://docs.typo3.org/permalink/typo3-cms-indexed-search:module-trouble-shooting>`_.

..  contents:: Table of contents

..  _monitoring-global-picture:
..  _monitoring-indexed-content:

Submodule "Detailed statistics", module "Indexing"
==================================================

In the :guilabel:`Web > Indexing` module (sub module
:guilabel:`Detailed statistics`) you can see an overview of indexed pages:

..  figure:: /Images/Module/MultipleIndexes.png
    :alt: Screenshot of the "Detailed statistics" in module "Web > Indexing" in the TYPO3 backend

    The "Login" page is indexed 3 times, the "Search" page not at all.

It can happen that a page is indexed multiple times. In the screenshot above
the page "Login" is indexed multiple times, once for each user group that logged
in.

Pages containing a plugin sometimes have a large number of indexes, for example
a page displaying the detail view of a page will be indexed once for each
news that is being displayed.

In this module you can also delete the index of a page. It will then be
re-indexed next time it is opened in the frontend or visited by a crawler.

..  _general-statistics:

Submodule "General statistics", module "Indexing"
=================================================

..  figure:: /Images/Module/GeneralStatistics.png
    :alt: Screenshot of the "General statistics" in module "Web > Indexing" in the TYPO3 backend

    See statistics like the most frequently searched words or that table usage

..  _list-typo3-pages:

Submodule "List of indexed pages", module "Indexing"
====================================================

This view shows a list of indexed pages with all the technical
details:

..  figure:: /Images/Module/ListOfIndexedPages.png
    :alt: Screenshot of the "List of indexed pages" in module "Web > Indexing" in the TYPO3 backend

    Technical details for each page, including size, language, word count, modification time etc.

..  _module-trouble-shooting:

Trouble shooting: Backend module "Indexing" does not show
=========================================================

If the backend module "Indexing" is not visible, and you have an
editor account, your permissions might not be sufficient.

If you have an administrator account and still cannot see the module
check the following:

*   Is indexed search `installed <https://docs.typo3.org/permalink/typo3-cms-indexed-search:installation>`_?
*   Did you delete the cache and reload the backend?
*   Was the module disabled via TSconfig?
