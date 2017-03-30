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

- :file:`constants.txt` - The file with the default constants. The "Constant Editor", as
  described above, is using this file for its default settings.

- :file:`setup.txt` - This file will first include some other files which are located in
  the "Setup" folder in the same directory. More about these files later.

In the folder :file:`ContentElement` there are files which are included by the file
:file:`setup.txt` as mentioned above. These files contain the rendering definitions of all
content elements, that are provided by the TYPO3 core. These are:

- :file:`Bullets.txt` - Configuration for Content Element "Bullet List"

- :file:`Div.txt` - Configuration for Content Element "Divider"

- :file:`Header.txt` - Configuration for Content Element "Header Only"

- :file:`Html.txt` - Configuration for Content Element "Plain HTML"

- :file:`Image.txt` - Configuration for Content Element "Image"

- :file:`List.txt` - Configuration for Content Element "General Plugin"

- :file:`MenuAbstract.txt` - Configuration for Content Element "Menu of subpages of selected pages including abstracts"

- :file:`MenuCategorizedContent.txt` - Configuration for Content Element "Content elements for selected categories"

- :file:`MenuCategorizedPages.txt` - Configuration for Content Element "Pages for selected categories"

- :file:`MenuPages.txt` - Configuration for Content Element "Menu of selected pages"

- :file:`MenuRecentlyUpdated.txt` - Configuration for Content Element "Recently updated pages"

- :file:`MenuRelatedPages.txt` - Configuration for Content Element "Related pages (based on keywords)"

- :file:`MenuSection.txt` - Configuration for Content Element "Section index (page content marked for section menus)"

- :file:`MenuSectionPages.txt` - Configuration for Content Element "Menu of subpages of selected pages including sections"

- :file:`MenuSitemap.txt` - Configuration for Content Element "Sitemap"

- :file:`MenuSitemapPages.txt` - Configuration for Content Element "Sitemaps of selected pages"

- :file:`MenuSubpages.txt` - Configuration for Content Element "Menu of subpages of selected pages"

- :file:`Shortcut.txt` - Configuration for Content Element "Insert records"

- :file:`Table.txt` - Configuration for Content Element "Table"

- :file:`Text.txt` - Configuration for Content Element "Regular Text Element"

- :file:`Textmedia.txt` - Configuration for Content Element "Text and Media"

- :file:`Textpic.txt` - Configuration for Content Element "Text and Images"

- :file:`Uploads.txt` - Configuration for Content Element "File Links"

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
:file:`setup.txt` as mentioned above. These are:

- :file:`ContentElement.txt` - Default configuration for content elements using
  FLUIDTEMPLATE

- :file:`ParseFunc.txt` - Creates persistent ParseFunc setup for non-HTML content
