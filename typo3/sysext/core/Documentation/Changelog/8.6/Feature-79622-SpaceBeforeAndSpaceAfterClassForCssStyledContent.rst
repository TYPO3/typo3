.. include:: ../../Includes.txt

=========================================================================
Feature: #79622 - SpaceBefore- and SpaceAfterClass for CSS Styled Content
=========================================================================

See :issue:`79622`

Description
===========

CSS Styled Content provided the possibility to the editor to fine-tune distances
between content elements. The concept of CSC relied on the editor
understanding what `margins` are, how they are calculated and had to maintain
an overview of pixels that were used on the site he/she is maintaining.

This led to different problems not only for the editor but also for the
integrator because he had no control about what the editor fills into these
fields. Also it was hardly controllable when these distances should be
variable and change on certain viewports for mobile usage.

To regain control over this behaviour we are now introducing a new concept
that purely relies on CSS classes, that can be defined by the integrator.

All CSS classes are prefixed with `frame-space-before-` or
`frame-spacer-after-` by default and added to the surrounding frame when available.
If the frame of the content element is set to none, placeholder elements
are placed before and after to generate that distance.

The default CSS definitions are placed in the optional static template
`TypoScript Content Elements CSS (optional)`. If this is not included
only the CSS classes will be added but without having CSS rules matching
these classes.


Example for before classes
--------------------------

=============   =============   ===============================   =============
Name            Value           CSS Class                         Margin
=============   =============   ===============================   =============
None            (empty)         (No CSS Class added)              (No Margin)
Extra Small     extra-small     csc-space-before-extra-small      1em
Small           small           csc-space-before-small            2em
Medium          medium          csc-space-before-medium           3em
Large           large           csc-space-before-large            4em
Extra Large     extra-large     csc-space-before-extra-large      5em
=============   =============   ===============================   =============


Example Output
--------------

.. code-block:: html

   <div id="c43" class="... csc-space-before-medium">
      ...
   </div>

.. code-block:: html

   <a id="c43"></a>
   <div class="csc-space-before-medium"></div>
   ...
   <div class="csc-space-after-medium"></div>


Edit Predefined Options
-----------------------

.. code-block:: typoscript

   TCEFORM.tt_content.space_before_class {
      removeItems = extra-small,small,medium,large,extra-large
      addItems {
         superspace = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:superSpace
      }
   }

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['space_before_class']['config']['items'][] = [
      0 = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:superSpace
      1 = superspace
   ];


Impact
======

SpaceBefore and SpaceAfter is now available to all CSS Styled Content elements.

.. index:: Frontend, ext:css_styled_content, TypoScript
