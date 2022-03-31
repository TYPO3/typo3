.. include:: /Includes.rst.txt

=======================================================
Deprecation: #79622 - Deprecation of CSS Styled Content
=======================================================

See :issue:`79622`

Description
===========

CSS Styled Content has been the preferred way of rendering
content in the frontend for a long time. Fluid Styled Content has been introduced as
successor of CSC, but the feature set diverged from the beginning. The
lack of flexibility and incomplete feature set in comparison to CSC made
it hard to migrate existing instances.

Since TYPO3 CMS 7.6 Fluid-Templates are the defined standard and
official recommendation for content rendering. The feature set of FSC is
now matching CSC. Both content renderings are now streamlined to be fully
compatible with each other. For the period of CMS 8 CSC will share
the same capabilities to make a transition as easy as possible. CSC is
now deprecated and goes into maintenance mode and will be removed with
CMS 9.


Affected Installations
======================

All installations that still use or rely on the content rendering of `css_styled_content`.


Migration
=========

Create a custom content rendering definition or switch to a maintained one like `fluid_styled_content`.

.. index:: Frontend, ext:css_styled_content
