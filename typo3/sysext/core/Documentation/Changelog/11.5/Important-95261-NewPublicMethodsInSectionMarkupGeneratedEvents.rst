.. include:: /Includes.rst.txt

=======================================================================
Important: #95261 - New public methods in SectionMarkupGenerated events
=======================================================================

See :issue:`95261`

Description
===========

With :issue:`88921`, two new events had been introduced. Those can be used to
add additional content to the columns in the page layout module. Due to the
different approach and the different code base of the new Fluid-based page
module, transforming the backend to always use the new approach in TYPO3 v11
also required to extend those events for two new public methods.

The new :php:`getPageLayoutContext()` should be used as a direct replacement
for the deprecated :php:`getPageLayoutView()` method, as it contains nearly
the same information, except for the records of the current column. This
information can from now on be retrieved using the new :php:`getRecords()`
method.

.. note::

    Due to the nature of the new Fluid-based page module, the content
    added through the events is now always displayed. Previously this
    was only possible in the columns mode.

.. index:: Backend, PHP-API, ext:backend
