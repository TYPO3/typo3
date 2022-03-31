.. include:: /Includes.rst.txt

==================================================================
Feature: #79622 - Introducing Frame Class for Fluid Styled Content
==================================================================

See :issue:`79622`

Description
===========

In CSS Styled Content it is possible to provide additional CSS classes
for the wrapping container element. This feature is now available
for Fluid Styled Content, too.

The default layout of Fluid Styled Content is now passing the value of
`Frame Class` directly to the template and prefixes the value by default
with `frame-<key>`.


Implementation in Fluid Styled Content
--------------------------------------

.. code-block:: html

   <div id="c{data.uid}" class="frame frame-{data.frame_class} ...">
      ...
   </div>


Explanation of Keys and Effects of Frame Classes
-------------------------------------------------

===============   ===============   =====================   ==================================================
Name              Key               CSS Class               Additional Effects
===============   ===============   =====================   ==================================================
Default           default           frame-default           -
Ruler Before      ruler-before      frame-ruler-before      A ruler is added after the output.
Ruler After       ruler-after       frame-ruler-after       A ruler is added after the output.
Indent            indent            frame-indent            Margin of 15% is added to the left and right side.
Indent, 33/66%    intent-left       frame-indent-left       Margin of 33% is added to the left side.
Indent, 66/33%    indent-right      frame-indent-right      Margin of 33% is added to the right side.
No Frame          none              (none)                  No Frame is rendered.
===============   ===============   =====================   ==================================================

Please note that you need to include the optional static template "Fluid Styled
Content Styling" to have a visual effect on the new added CSS classes.


Edit Predefined Options
-----------------------

.. code-block:: typoscript

   TCEFORM.tt_content.frame_class {
      removeItems = default,ruler-before,ruler-after,indent,indent-left,indent-right,none
      addItems {
         superframe = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:superframe
      }
   }

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['frame_class']['config']['items'][] = [
      0 = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:superframe
      1 = superframe
   ];


Impact
======

`Frame Class` is now available to all Fluid Styled Content elements.


.. index:: Fluid, Frontend, ext:fluid_styled_content, TypoScript
