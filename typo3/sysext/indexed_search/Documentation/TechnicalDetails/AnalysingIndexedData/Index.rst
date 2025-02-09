..  include:: /Includes.rst.txt
..  _analysing-indexed-data:
..  _complex-scenarios:

==========================
Analysing the indexed data
==========================

The indexer is constructed to work with TYPO3's page structure.
Opposite to a crawler which simply indexes all the pages it can find,
the TYPO3 indexer MUST take the following into account:

-   Only cached pages can be indexed.

-   Pages in more than one language must be indexed separately as
    "different pages".

-   Pages with plugins may have multiple indexed versions based on
    what is displayed on the page: For example a single view page for news
    must be indexed once for each news that is displayed on it.

-   Pages with access restricted to must be observed!

-   Because pages can contain different content whether a user is logged
    in or not and even based on which groups he is a member of, a single
    page (identified by the combination of id/type/language/arguments)
    may even be available in more than one indexed version based on the
    user-groups.
