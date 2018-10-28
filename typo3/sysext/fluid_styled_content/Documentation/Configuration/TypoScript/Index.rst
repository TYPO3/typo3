.. include:: ../../Includes.txt

.. _typoscript:

==========
TypoScript
==========

At the section :ref:`using-the-rendering-definitions` you've already added the static
templates. Static templates are a collection of TypoScript files. These files are located
in the directory :file:`EXT:fluid_styled_content/Configuration/TypoScript/`.

.. figure:: Images/FileStructure.png
   :alt: Structure of the TypoScript files

   Structure of the TypoScript files

In this folder there are two files:

- :file:`constants.typoscript` - The file with the default constants. The "Constant Editor", as
  described above, is using this file for its default settings.

- :file:`setup.typoscript` - This file will first include some other files which are located in
  the "Setup" folder in the same directory. More about these files later.

In the folder :file:`ContentElement` there are files which are included by the file
:file:`setup.typoscript` as mentioned above. These files contain the rendering definitions of all
content elements, that are provided by the TYPO3 core. These are:

- :file:`Bullets.typoscript` - Configuration for Content Element "Bullet List"

- :file:`Div.typoscript` - Configuration for Content Element "Divider"

- :file:`Header.typoscript` - Configuration for Content Element "Header Only"

- :file:`Html.typoscript` - Configuration for Content Element "Plain HTML"

- :file:`Image.typoscript` - Configuration for Content Element "Image"

- :file:`List.typoscript` - Configuration for Content Element "General Plugin"

- :file:`MenuAbstract.typoscript` - Configuration for Content Element "Menu of subpages of selected pages including abstracts"

- :file:`MenuCategorizedContent.typoscript` - Configuration for Content Element "Content elements for selected categories"

- :file:`MenuCategorizedPages.typoscript` - Configuration for Content Element "Pages for selected categories"

- :file:`MenuPages.typoscript` - Configuration for Content Element "Menu of selected pages"

- :file:`MenuRecentlyUpdated.typoscript` - Configuration for Content Element "Recently updated pages"

- :file:`MenuRelatedPages.typoscript` - Configuration for Content Element "Related pages (based on keywords)"

- :file:`MenuSection.typoscript` - Configuration for Content Element "Section index (page content marked for section menus)"

- :file:`MenuSectionPages.typoscript` - Configuration for Content Element "Menu of subpages of selected pages including sections"

- :file:`MenuSitemap.typoscript` - Configuration for Content Element "Sitemap"

- :file:`MenuSitemapPages.typoscript` - Configuration for Content Element "Sitemaps of selected pages"

- :file:`MenuSubpages.typoscript` - Configuration for Content Element "Menu of subpages of selected pages"

- :file:`Shortcut.typoscript` - Configuration for Content Element "Insert records"

- :file:`Table.typoscript` - Configuration for Content Element "Table"

- :file:`Text.typoscript` - Configuration for Content Element "Regular Text Element"

- :file:`Textmedia.typoscript` - Configuration for Content Element "Text and Media"

- :file:`Textpic.typoscript` - Configuration for Content Element "Text and Images"

- :file:`Uploads.typoscript` - Configuration for Content Element "File Links"

Since we move away from TypoScript as much as possible, these rendering
definitions only declare the following:

- Can FLUIDTEMPLATE be used immediately or do we need data processing first?
  A processor is sometimes used to do some data manipulation before all the data is sent
  to the Fluid template.

- Assigning the Fluid template file for each type of content element separately.

- The configuration of the edit panel and the edit buttons for frontend editing. You
  need to activate the extension "Frontend Editing (feedit)" in the Extension Manager to
  see this in action.

In the folder :file:`Helper` there are files which are included by the file
:file:`setup.typoscript` as mentioned above. These are:

- :file:`ContentElement.typoscript` - Default configuration for content elements using
  FLUIDTEMPLATE

- :file:`ParseFunc.typoscript` - Creates persistent ParseFunc setup for non-HTML content
