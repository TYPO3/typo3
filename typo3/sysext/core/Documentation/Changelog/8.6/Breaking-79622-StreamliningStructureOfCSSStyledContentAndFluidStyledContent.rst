.. include:: ../../Includes.txt

========================================================================================
Breaking: #79622 - Streamlining structure of CSS Styled Content and Fluid Styled Content
========================================================================================

See :issue:`79622`

Description
===========

The file structures of CSS Styled Content and Fluid Styled Content have been streamlined.


File structure of CSS Styled Content
------------------------------------

.. code-block:: php

   - Configuration/TypoScript
   | - ContentElement
   | | - Bullets.txt
   | | - Div.txt
   | | - Header.txt
   | | - Html.txt
   | | - Image.txt
   | | - List.txt
   | | - MenuAbstract.txt
   | | - MenuCategorizedContent.txt
   | | - MenuCategorizedPages.txt
   | | - MenuPages.txt
   | | - MenuRecentlyUpdated.txt
   | | - MenuRelatedPages.txt
   | | - MenuSection.txt
   | | - MenuSectionPages.txt
   | | - MenuSitemap.txt
   | | - MenuSitemapPages.txt
   | | - MenuSubpages.txt
   | | - Shortcut.txt
   | | - Table.txt
   | | - Text.txt
   | | - Textmedia.txt
   | | - Textpic.txt
   | | - Uploads.txt
   | - ContentElementPartials
   | | - Menu.txt
   | - Helper
   | | - ParseFunc.txt
   | | - StandardHeader.txt
   | | - StylesContent.txt
   | - Styling
   | | - setup.txt
   | - constants.txt
   | - setup.txt


File structure of Fluid Styled Content
--------------------------------------

.. code-block:: php

   - Configuration/TypoScript
   | - ContentElement
   | | - Bullets.txt
   | | - Div.txt
   | | - Header.txt
   | | - Html.txt
   | | - Image.txt
   | | - List.txt
   | | - MenuAbstract.txt
   | | - MenuCategorizedContent.txt
   | | - MenuCategorizedPages.txt
   | | - MenuPages.txt
   | | - MenuRecentlyUpdated.txt
   | | - MenuRelatedPages.txt
   | | - MenuSection.txt
   | | - MenuSectionPages.txt
   | | - MenuSitemap.txt
   | | - MenuSitemapPages.txt
   | | - MenuSubpages.txt
   | | - Shortcut.txt
   | | - Table.txt
   | | - Text.txt
   | | - Textmedia.txt
   | | - Textpic.txt
   | | - Uploads.txt
   | - Helper
   | | - FluidContent.txt
   | | - ParseFunc.txt
   | - Styling
   | | - setup.txt
   | - constants.txt
   | - setup.txt


Impact
======

TYPO3 will fail to load the rendering definitions correctly if the paths are
not included matching the new file locations.


Affected Installations
======================

All installations that are referring to the previous location of the rendering
definitions. Please check if your using any of these paths for including the
rendering definitions.


CSS Styled Content
------------------

- EXT:css_styled_content/static/v4.5
- EXT:css_styled_content/static/v4.6
- EXT:css_styled_content/static/v4.7
- EXT:css_styled_content/static/v6.0
- EXT:css_styled_content/static/v6.1
- EXT:css_styled_content/static/v6.2
- EXT:css_styled_content/static
- EXT:css_styled_content/Configuration/TypoScript/v7


Fluid Styled Content
--------------------

- EXT:fluid_styled_content/TypoScript/Static


Migration
=========

Database entries can be automatically upgraded to the new locations. If you have
references in your TypoScript files you need to do the migration manually.

Use the new locations for accessing the TypoScript configuration.

- `CSS Styled Content` = EXT:css_styled_content/Configuration/TypoScript/
- `Fluid Styled Content` = EXT:fluid_styled_content/Configuration/TypoScript/


.. index:: Fluid, Frontend, ext:fluid_styled_content
