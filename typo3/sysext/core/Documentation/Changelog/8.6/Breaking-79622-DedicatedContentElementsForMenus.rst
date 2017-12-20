.. include:: ../../Includes.txt

=======================================================
Breaking: #79622 - Dedicated content elements for menus
=======================================================

See :issue:`79622`

Description
===========

For better maintainability the currently existing content element
menu has been split into dedicated content elements.

==========================   ==========================   ==========================================================
Database Key                 Name                         Description
==========================   ==========================   ==========================================================
menu_abstract                Abstracts                    Menu of subpages of selected pages including abstracts
menu_categorized_content     Categorized content          Content elements for selected categories
menu_categorized_pages       Categorized pages            Pages for selected categories
menu_pages                   Pages                        Menu of selected pages
menu_subpages                Subpages                     Menu of subpages of selected pages
menu_recently_updated        Recently updated pages       Menu of recently updated pages
menu_related_pages           Related pages                Menu of related pages based on keywords
menu_section                 Section index                Page content marked for section menus
menu_section_pages           Section index of subpages    Menu of subpages of selected pages including sections
menu_sitemap                 Sitemap                      Expanded menu of all pages and subpages for selected pages
menu_sitemap_pages           Sitemaps of selected pages   Expanded menu of all subpages for selected pages
==========================   ==========================   ==========================================================


Affected Installations
======================

All installations that use the content element "menu".


Migration
=========

Run the migration wizard in the install tool. All shipped menu types from the
TYPO3 core will be migrated to the new dedicated elements.

The migration is optional, you can also enable the extension `compatibility7`
that will make the old menu content element available again.

.. index:: Frontend, TypoScript, TCA
