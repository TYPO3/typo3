.. include:: ../../Includes.txt

===================================================================
Breaking: #79622 - Removal of Fluid Styled Content Menu ViewHelpers
===================================================================

See :issue:`79622`

Description
===========

Fetching data directly in the view is not recommended and the temporary
solution of menu ViewHelpers has been replaced by its successor, the menu
processor that is based on HMENU.

Menu ViewHelpers have been moved to the `compatibility7` extension, and are
replaced in the core menu content elements.

List of removed ViewHelpers:

- menu.categories
- menu.directory
- menu.keywords
- menu.list
- menu.section
- menu.updated


Affected Installations
======================

All installations that use the `fluid_styled_content` menu ViewHelpers.


Migration
=========

Use `TYPO3\CMS\Frontend\DataProcessing\MenuProcessor` instead of ViewHelpers.

For CMS 8 the ViewHelpers will be available as soon as `compatibility7` is
installed, but it's highly recommended to migrate your configuration.

Example (Directory)
-------------------

Before:

.. code-block:: typoscript

   tt_content.menu_subpages.dataProcessing {
      10 = TYPO3\CMS\Frontend\DataProcessing\SplitProcessor
      10 {
         if.isTrue.field = pages
         fieldName = pages
         delimiter = ,
         removeEmptyEntries = 1
         filterIntegers = 1
         filterUnique = 1
         as = pageUids
      }
   }

.. code-block:: html

   <ce:menu.directory pageUids="{pageUids}" as="pages" levelAs="level">
      <f:for each="{pages}" as="page">
         ...
      </f:for>
   </ce.menu.directory>

After:

.. code-block:: typoscript

   tt_content.menu_subpages.dataProcessing {
      10 = TYPO3\CMS\Frontend\DataProcessing\MenuProcessor
      10.special = directory
      10.special.value.field = pages
   }

.. code-block:: html

   <f:for each="{menu}" as="page">
      ...
   </f:for>

.. index:: Fluid, Frontend, ext:fluid_styled_content
