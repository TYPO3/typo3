.. include:: ../../Includes.txt

==================================================================
Feature: #79622 - Header Position support for Fluid Styled Content
==================================================================

See :issue:`79622`

Description
===========

Header position as known from CSS Styled Content is now also supported by
Fluid Styled Content. This will allow the editor to have more control about
the alignment of the header in the frontend.

By default all CSS classes for header alignment are prefixed with
`ce-headline-` to make the css class unique and to allow even more adjustments
without breaking your styling somewhere else.


Predefined values for header alignment and resulting CSS classes
----------------------------------------------------------------

==========   ==========   ====================
Name         Value        CSS Class
==========   ==========   ====================
Default      (empty)      (No CSS Class added)
Center       center       ce-headline-center
Right        right        ce-headline-right
Left         left         ce-headline-left
==========   ==========   ====================


Implementation Example
----------------------

The following examples are taken from the partials of fluid_styled_content that
can be found here `EXT:fluid_styled_content/Resources/Private/Partials/Header/All.html`
and here `EXT:fluid_styled_content/Resources/Private/Partials/Header/Header.html`.

.. code-block:: html

   <f:render partial="Header/Header" arguments="{
      header: data.header,
      layout: data.header_layout,
      positionClass: '{f:if(condition: data.header_position, then: \'ce-headline-{data.header_position}\')}',
      link: data.header_link,
      default: settings.defaultHeaderType}" />

.. code-block:: html

   <h1 class="{positionClass}">
     <f:link.typolink parameter="{link}">{header}</f:link.typolink>
   </h1>


Edit Predefined Options
-----------------------

.. code-block:: typoscript

   TCEFORM.tt_content.header_position {
      removeItems = center,left,right
      addItems {
         fancyheader = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:fancyHeader
      }
   }

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['header_position']['config']['items'][] = [
      0 = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:fancyHeader
      1 = fancyheader
   ];


Impact
======

Header positions are now available to all editors by default.


.. index:: Fluid, Frontend, ext:fluid_styled_content, TypoScript
