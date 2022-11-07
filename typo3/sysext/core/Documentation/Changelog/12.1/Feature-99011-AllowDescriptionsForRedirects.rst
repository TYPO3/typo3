.. include:: /Includes.rst.txt

.. _feature-99011-1667944274:

==================================================
Feature: #99011 - Allow descriptions for redirects
==================================================

See :issue:`99011`

Description
===========

A new field :php:`description` has been added to the :sql:`sys_redirects` table.

In the backend edit form, the new field is located under the :guilabel:`Notes`
tab. It can be used to add context to the corresponding redirect. Since the
field is defined as the records' :php:`descriptionColumn`, the added
information is also displayed in the "Record information" info box
above the edit form, like known form e.g. content elements or pages.

Impact
======

It's now possible to add additional information to a redirect
using the new description field, whose value is also displayed
in the corresponding backend edit form.

.. index:: Database, TCA, Backend
