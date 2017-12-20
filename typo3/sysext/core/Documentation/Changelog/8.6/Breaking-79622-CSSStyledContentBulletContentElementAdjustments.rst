.. include:: ../../Includes.txt

========================================================================
Breaking: #79622 - CSS Styled Content Bullet Content Element Adjustments
========================================================================

See :issue:`79622`

Description
===========

In order to streamline the options and enhance compatibility across CSS Styled
Content and Fluid Styled Content the bullet content element has been partly
refactored.

The bullet content elements of CSS Styled Content now also uses the field
`bullets_type` instead of `layout` to match the behavior of Fluid Styled Content.


Affected Installations
======================

Installations that use the CSS Styled Content element bullets.


Migration
=========

Run the upgrade wizard in the install tool to migrate the layout field to the
dedicated database field `bullets_type`.


.. index:: FlexForm, Frontend, TCA, TypoScript, ext:css_styled_content
