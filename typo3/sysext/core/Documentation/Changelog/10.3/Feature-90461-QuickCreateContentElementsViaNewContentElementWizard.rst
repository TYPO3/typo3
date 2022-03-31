.. include:: /Includes.rst.txt

===========================================================================
Feature: #90461 - Quick-Create Content Elements via NewContentElementWizard
===========================================================================

See :issue:`90461`

Description
===========

The new Content Element wizard within the Page Module now contains
an option called "saveAndClose" which directs a user back to the
Page Module directly instead of showing the FormEngine.

This is especially useful for custom content elements or container
content types where pre-defined values can be put in place directly,
saving editors one click on content creation.

The functionality is disabled by default, but explicitly enabled for the Content Type "divider".


Impact
======

This definition can be put into PageTSconfig (e.g. :file:`EXT:my_extension/Configuration/Page/main.tsconfig`) with the new flag "saveAndClose" enabled.

.. code-block:: typoscript

   mod.wizards.newContentElement.wizardItems {
       common.elements {
           my_element {
               iconIdentifier = content-my-icon
               title = LLL:EXT:my_extension/Resources/Private/Language/ContentTypes.xlf:my_element_title
               description = LLL:EXT:my_extension/Resources/Private/Language/ContentTypes.xlf:my_element_description
               tt_content_defValues {
                   CType = my_element
                   header = Hello my friend
               }
               saveAndClose = true
           }
       }
   }

.. index:: Backend, TSConfig, ext:backend
