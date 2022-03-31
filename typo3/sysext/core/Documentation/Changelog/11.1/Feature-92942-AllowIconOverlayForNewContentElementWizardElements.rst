.. include:: /Includes.rst.txt

=========================================================================
Feature: #92942 - Allow icon overlay for newContentElementWizard elements
=========================================================================

See :issue:`92942`

Description
===========

The new Content Element wizard within the page module now allows
to define an icon overlay for each wizard element using the new
TSconfig option :typoscript:`iconOverlay` next to a defined :typoscript:`iconIdentifier`.

This is especially useful for custom content elements that use the
same :typoscript:`iconIdentifier` several times, but still have to be differentiated.

The full configuration path is
:typoscript:`mod.wizards.newContentElement.wizardItems.*.elements.*.iconOverlay`.

An example configuration could look like this:

.. code-block:: typoscript

   mod.wizards.newContentElement.wizardItems {
       common.elements {
           my_element {
               iconIdentifier = content-my-icon
               iconOverlay = content-my-icon-overlay
               title = LLL:EXT:my_extension/Resources/Private/Language/ContentTypes.xlf:my_element_title
               description = LLL:EXT:my_extension/Resources/Private/Language/ContentTypes.xlf:my_element_description
               tt_content_defValues {
                   CType = my_element
               }
           }
       }
   }


Impact
======

It's now possible to define an :html:`iconOverlay` next to an :html:`iconIdentifier`
for newContentElementWizard elements.

.. index:: Backend, TSConfig, ext:backend
