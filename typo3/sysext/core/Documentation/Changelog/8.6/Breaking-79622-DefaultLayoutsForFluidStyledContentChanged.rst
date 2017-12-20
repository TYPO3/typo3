.. include:: ../../Includes.txt

===================================================================
Breaking: #79622 - Default layouts for Fluid Styled Content changed
===================================================================

See :issue:`79622`

Description
===========

The content element layouts for Fluid Styled Content have been changed
to provide a better maintainability and to be more flexible.

Previously available content element layouts `ContentFooter`, `HeaderFooter`
and `HeaderContentFooter` have been dropped and replaced with a single
`Default` layout that is more flexible.


Impact
======

The content element layouts `ContentFooter`, `HeaderFooter` and
`HeaderContentFooter` are no longer available. Referencing these layouts will
result in an exception.


Affected Installations
======================

All instances that override or implement custom content elements based on
Fluid Styled Content that use the layouts `ContentFooter`, `HeaderFooter`
or `HeaderContentFooter`.


Migration
=========

All content elements and overrides need to be migrated to the new default
layout. Have a look at the feature description on how to use the new layout.

Feature-79622-NewDefaultLayoutForFluidStyledContent.rst


.. index:: Fluid, Frontend, ext:fluid_styled_content
