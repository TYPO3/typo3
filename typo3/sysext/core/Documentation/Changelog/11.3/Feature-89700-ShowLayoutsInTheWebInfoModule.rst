.. include:: /Includes.rst.txt

=====================================================
Feature: #89700 - Show layouts in the Web Info module
=====================================================

See :issue:`89700`

Description
===========

It's now possible to get an overview of the configured backend and
frontend layouts of a page in the :guilabel:`Web->Info` module. Therefore a
new entry "Layouts" is available for the page tree overview.

Besides the "Backend Layout (this page only)", the "Backend Layout (subpages of this page)"
and the "Layout" fields, which do all just display the title of the current
field value, an additional field "Actual backend layout" is displayed. This
field contains the title of the backend layout, which is actually used for
the page. If set, this is the same as "Backend Layout (this page only)".
Otherwise, it contains the inherited layout from a parent page, which
defined "Backend Layout (subpages of this page)".

This is especially useful for editors to determine the actually used
backend layout, which was previously often difficult. For example in
installations with large page trees and highly developed inheritance.

In case the current field value is invalid, e.g. referencing a non-existent
backend layout, this is now also shown to the editor.

Impact
======

The :guilabel:`Web->Info` module now contains a new page tree overview type, which
contains the layout related fields as well as an additional field,
displaying the actually used backend layout for the corresponding page.

.. index:: Backend, TSConfig, ext:core
