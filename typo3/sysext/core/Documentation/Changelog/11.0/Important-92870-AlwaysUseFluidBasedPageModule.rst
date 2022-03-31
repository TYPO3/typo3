.. include:: /Includes.rst.txt

======================================================
Important: #92870 - Always use Fluid based page module
======================================================

See :issue:`92870`

Description
===========

With :issue:`90348` a completely rewritten page module has been added as
a replacement for the :php:`PageLayoutView`. As this replacement is stable
enough, the feature toggle to switch between the different implementations
has been removed.


Impact
======

The feature `fluidBasedPageModule` is now always enabled. Extension authors
can therefore remove any check regarding this feature as it will always return
:php:`true`.

.. index:: Backend, ext:backend
