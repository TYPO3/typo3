.. include:: /Includes.rst.txt

.. _feature-97051:

===================================================
Feature: #97051 - Filter logs by page in Log module
===================================================

See :issue:`97051`

Description
===========

The :guilabel:`System > Log` module has been extended by a new filter option
`Page`. When used, the result is limited to logs related to the selected page.
Those are usually content related logs, such as "User X created record Y".

With the `Depth` option - which is only available in case a page is selected -
the result can be extended to further subpages.

Additionally, administrators are now able to define access permissions via
the module access logic for the :guilabel:`System > Log` module.

Impact
======

It's now possible to filter system logs in the :guilabel:`System > Log`
module by pages.

.. index:: Backend, ext:belog
