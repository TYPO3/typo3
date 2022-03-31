.. include:: /Includes.rst.txt

================================================================================
Breaking: #79622 - SpaceBefore and SpaceAfter adjustments for CSS Styled Content
================================================================================

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

The original fields `spaceAfter` and `spaceBefore` have been dropped, and also
the method :php:`\TYPO3\CMS\CssStyledContent\Controller\CssStyledContentController::renderSpace()`
is not called anymore.


Old TypoScript Rendering
------------------------

.. code-block:: typoscript

   tt_content.stdWrap.innerWrap.cObject.default.20.20 = USER
   tt_content.stdWrap.innerWrap.cObject.default.20.20 {
      userFunc = TYPO3\CMS\CssStyledContent\Controller\CssStyledContentController->renderSpace
      space = before
      constant = {$content.spaceBefore}
      classStdWrap {
         required = 1
         noTrimWrap = |csc-space-before-| |
      }
   }


New TypoScript Rendering
------------------------

.. code-block:: typoscript

   tt_content.stdWrap.innerWrap.cObject.default.20.20 = TEXT
   tt_content.stdWrap.innerWrap.cObject.default.20.20 {
      field = space_before_class
      required = 1
      noTrimWrap = |csc-space-before-| |
   }


Impact
======

User-defined distances between content elements are missing.


Affected Installations
======================

All instances that use  CSS Styled Content and have spaceBefore or spaceAfter
values set to generate more space between their content elements.


Check if your site is affected
------------------------------

.. code-block:: mysql

   SELECT
      uid,
      pid,
      spaceBefore,
      spaceAfter
   FROM
      tt_content
   WHERE
      (spaceBefore > 0 OR spaceAfter > 0)
      AND deleted = 0


Migration
=========

There is no automatic migration available. If a migration is necessary you need
to check the new presets available and migrate the pixels defined before to the
a preset of your choice.


Example
-------

.. code-block:: mysql

   UPDATE
      tt_content
   SET
      spaceAfter = 0,
      space_after_class = 'medium'
   WHERE
      spaceAfter >= 42
      AND spaceAfter < 56


Replacement Documentation
-------------------------

For detailed information about the replacement of spaceBefore and spaceAfter,
please head over to the feature documentation of SpaceBefore- and SpaceAfterClass
for CSS Styled Content.

Feature-79622-SpaceBeforeAndSpaceAfterClassForCssStyledContent.rst


.. index:: Frontend, Database, ext:css_styled_content, TypoScript
