:navigation-title: Editor manual

..  include:: /Includes.rst.txt
..  _user-manual:

========================================
Editor manual: How to use indexed search
========================================

..  contents:: Table of contents


..  _adding-search-plugin:

Adding the search plugin to a page
==================================

..  tip::
    If you do not see the plugin as described here, you might not have the
    permissions to insert the plugin yourself, `indexed_search` may not be
    `installed <https://docs.typo3.org/permalink/typo3/cms-indexed-search:installation>`_
    or perhaps your site is using a different search engine like ke_search,
    Solr or Elastic Search. Talk to your site administrator.

Create a page called "Search" or something like that. This is where the search
box will appear.

In the backend module :guilabel:`Web > Page` open the new page called "Search",
then click the "+ Create new content" button.

..  figure:: /Images/Plugin/NewPageContent.png
    :alt: Screenshot of the Indexed Search plugin, displayed in tab "Form elements" of the "New content elements" wizard in the TYPO3 page module.

    Insert the Indexed Search Form

There are no special settings that you can make in this plugin.
You will now see a search form in the frontend. Otherwise refer to the
trouble shooting section below.

..  _adding-search-plugin-troubleshooting:

Indexed search plugin trouble shooting for TYPO3 backend editors
----------------------------------------------------------------

..  accordion::
    :name: editorTroubleShooting

    ..  accordion-item:: Error: Please check that TypoScript for the Indexed Search plugin is included
        :name: noTypoScript
        :header-level: 4
        :show:

        If you see this message instead of a search plugin, your administrator might not
        yet have included the
        `Site set "Indexed Search" <https://docs.typo3.org/permalink/typo3/cms-indexed-search:site-set>`_
        or there might be something wrong with the TypoScript. Try to delete the caches
        if you have permissions to do so.

        If the problem prevails, there is nothing you can do with editor permissions
        here. Hide the page and ask your administrator.

    ..  accordion-item:: The search form is not styled
        :name: noStyle
        :header-level: 4

        If this form is missing styles, ask your frontend developer or administrator
        to improve the styles.

    ..  accordion-item:: I do not want the "Advanced search" link
        :name: advancedSearchLink
        :header-level: 4

        This link can be removed by an integrator via
        :ref:`TypoScript setting plugin.tx_indexedsearch.settings.displayAdvancedSearchLink <typo3/cms-indexed-search:confval-displayadvancedsearchlink>`.

    ..  accordion-item:: No search results but the search page itself displayed
        :name: noSearchResults
        :header-level: 4

        As the name suggests, indexed search works with an internal index. Depending
        on how your integrator configured the extension this index is rebuilt
        whenever a page has been changed or periodically at certain times or both.

        Small webpages often do not use a crawler which rebuilds the index periodically,
        here pages get added to the index whenever they are first visited after the
        installation of indexed search. Click through the website and see if you have
        more results after that. If not, ask your administrator.

    ..  accordion-item:: The search form is not translated, displayed in English on a German / French / Chinese page
        :name: noTranslation
        :header-level: 4

        Ask your administrator to do the following:

        *   Update the `Language packs in the Admin Tools <https://docs.typo3.org/permalink/t3coreapi:managing-translating>`_.
        *   Check the language settings for your site.
        *   Some languages might not yet have a translation available for the Indexed
            Search form. Consider if you can provide translations on
            `Crowdin <https://docs.typo3.org/permalink/t3coreapi:xliff-translating-server-crowdin>`_
            so the everyone using this language can profit.

    ..  accordion-item:: Unwanted pages appear in the search results
        :name: unwantedPages
        :header-level: 4

        See see chapter

    ..  accordion-item:: Link to entries appear in the search, that are not working
        :name: outdatedEntries
        :header-level: 4

        *   The search index might be outdated. Ask an administrator to empty and
            regenerate it.
        *   There might be an error in how the links are being generated. Ask an
            administrator about that.

..  _user-manual-exclude:

Exclude a page from the search results
======================================

Some pages should not appear in the search themselves. This includes overview
pages like the sitemap, a page listing all news or the search page itself.

Editors can manually exclude such pages from the search index by going to the
`Page properties <https://docs.typo3.org/permalink/t3editors:pages-properties>`_,
tab :guilabel:`Behaviour` and toggeling the button :guilabel:`Include in Search`.

If you cannot see this button or cannot edit the properties of a page, speak to
your administrator.

If the search results still contain the excluded pages the search index might
have to be rebuilt. Ask your administrator about this.

..  _user-manual-module-indexing:

The backend module "Indexing"
=============================

If you have extended permissions as an editor, you might have the backend module
:guilabel:`Web > Indexing` available. In this module you, as a power user,
can view which pages are indexed and delete pages from the index if necessary.

Please refer to chapter
`Monitoring indexed content <https://docs.typo3.org/permalink/typo3/cms-indexed-search:monitoring-indexed-content>`_.
