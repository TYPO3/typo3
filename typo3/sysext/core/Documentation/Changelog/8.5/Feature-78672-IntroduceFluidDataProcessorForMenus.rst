.. include:: /Includes.rst.txt

==========================================================
Feature: #78672 - Introduce fluid data processor for menus
==========================================================

See :issue:`78672`

Description
===========

This menu processor utilizes HMENU to generate a json encoded menu
string that will be decoded again and assigned to FLUIDTEMPLATE as a
variable. Additional DataProcessing is supported and will be applied
to each record.

Options:
`as` The variable to be used within the result
`levels` Number of levels of the menu
`expandAll` If false, submenus will only render if the parent page is active
`includeSpacer` If true, the doctype "Spacer" will be included in the menu
`titleField` Field that should be used for the title

See HMENU docs for more options.
https://docs.typo3.org/typo3cms/TyposcriptReference/ContentObjects/Hmenu/Index.html

Example TypoScript configuration:

.. code-block:: typoscript

   10 = TYPO3\CMS\Frontend\DataProcessing\MenuProcessor
   10 {
      special = list
      special.value.field = pages
      levels = 7
      as = menu
      expandAll = 1
      includeSpacer = 1
      titleField = nav_title // title
      dataProcessing {
         10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
         10 {
            references.fieldName = media
         }
      }
   }

.. index:: Fluid, TypoScript, Frontend
