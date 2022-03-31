
.. include:: /Includes.rst.txt

===================================================================================
Feature: #23156 - Indexed search: Make path separator of search result configurable
===================================================================================

See :issue:`23156`

Description
===========

A new TypoScript configuration option :typoscript:`breadcrumbWrap` has been added. It allows to configure
the page path separator used in breadcrumbs in Indexed Search results. This option supports TypoScript
option split syntax.


Impact
======

By default Indexed Search is configured to use "/" as a path separator, so it's backward compatible.
Use following configuration for Indexed Search Extbase plugin:

.. code-block:: typoscript

   plugin.tx_indexedsearch.settings.breadcrumbWrap = / || /

For plugin based on AbstractPlugin use:

.. code-block:: typoscript

   plugin.tx_indexedsearch.breadcrumbWrap = / || /


.. index:: TypoScript, ext:indexed_search
