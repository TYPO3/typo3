.. include:: ../../Includes.txt

===============================================================================================
Breaking: #80412 - New shared content element TypoScript libary object for Fluid Styled Content
===============================================================================================

See :issue:`80412`

Description
===========

To solve an inconsistency issue for API based content element registration between
CSS Styled Content (CSC) and Fluid Styled Content (FSC) through `Extbase` or
:php:`addPItoST43` we are now introducing a new shared content object for content elements
and drop the active usage of :typoscript:`lib.fluidContent`.

The generated code relied on the existence of the removed :typoscript:`lib.stdheader` and also
ignored layouts, frames, spacebefore, spaceafter in context of Fluid Styled Content.

For content element registration the TypoScript :typoscript:`lib.contentElement` is now used for `CSC` and `FSC`
and replaces the usage of :typoscript:`lib.contentElement`. The generated code was slightly
adjusted to match the requirements of all content rendering definitions and can be
adapted to the specific needs of a content element rendering definition anytime
since a reference is used now instead of a hard definition.

Generated code before change
----------------------------

.. code-block:: typoscript

   tt_content.myce = COA
   tt_content.myce {
      10 =< lib.stdheader
      20 =< plugin.myContent
   }

Generated code after change
---------------------------

.. code-block:: typoscript

   tt_content.myce =< lib.contentElement
   tt_content.myce {
      templateName = Generic
      20 =< plugin.myContent
   }

CSS Styled Content
------------------

CSS Styled Content adds the missing :typoscript:`lib.stdheader` and everything works as
before, no migration or adjustments to your code nessesary. Because :typoscript:`COA`
does not understand the option :typoscript:`templateName` it will simply be ignored.

.. code-block:: typoscript

   lib.contentElement = COA
   lib.contentElement {
      10 =< lib.stdheader
   }

Fluid Styled Content
--------------------

Fluid Styled Content adds the logic it needs through :typoscript:`lib.contentElement`.
All content elements registered through the TYPO3 APIs will now share a
multifunctional `Generic` template. That will provide the necessary layouts
and overriding options known from FSC.

.. code-block:: typoscript

   lib.contentElement = FLUIDTEMPLATE
   lib.contentElement {
      templateRootPaths {
         0 = EXT:fluid_styled_content/Resources/Private/Templates/
         10 = {$styles.templates.templateRootPath}
      }
      partialRootPaths {
         0 = EXT:fluid_styled_content/Resources/Private/Partials/
         10 = {$styles.templates.partialRootPath}
      }
      layoutRootPaths {
         0 = EXT:fluid_styled_content/Resources/Private/Layouts/
         10 = {$styles.templates.layoutRootPath}
      }
      ...
   }

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers" data-namespace-typo3-fluid="true">
   <f:layout name="Default" />
   <f:section name="Main">

      <f:comment>This templates is used to provide necessary functionality for external processed content and could be used across multiple sources, for example the frontend login content element.</f:comment>
      <f:if condition="{content}">
         <f:then>{content -> f:format.raw()}</f:then>
         <f:else><f:cObject typoscriptObjectPath="tt_content.{data.CType}.20" data="{data}" table="tt_content" /></f:else>
      </f:if>

   </f:section>
   </html>

The TypoScript Object :typoscript:`lib.fluidContent` will be kept as copy of :typoscript:`lib.contentElement`
for compatibility for the duration of TYPO3 v8 LTS and will be removed in TYPO3 v9.


Impact
======

Assignments and overrides made directly to :typoscript:`lib.fluidContent` are not recognized
anymore for core content elements provided by Fluid Styled Content. They need to be
migrated to :typoscript:`lib.contentElement`. Only not modified versions of :typoscript:`lib.fluidContent`
will keep working as expected.


Affected Installations
======================

Installations that directly modify :typoscript:`lib.fluidContent`.


Migration
=========

Rename assignments and modifications to :typoscript:`lib.contentElement`.

.. index:: Fluid, Frontend, TypoScript, ext:fluid_styled_content
