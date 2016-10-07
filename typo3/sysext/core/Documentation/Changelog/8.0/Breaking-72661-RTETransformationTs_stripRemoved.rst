
.. include:: ../../Includes.txt

======================================================
Breaking: #72661 - RTE Transformation ts_strip removed
======================================================

See :issue:`72661`

Description
===========

The Rich Text Editor transformation that removes all HTML tags except a hard-coded white-list of allowed
HTML tags when saving data from the RTE to the database - called `ts_strip` - has been removed.


Impact
======

Using the command `ts_strip` in the list of transformations via PageTSconfig or TCA directly will result in keeping
the HTML tags inside the database.


Affected Installations
======================

TYPO3 installations that use `ts_strip` explicitly in their TSconfig options, or instances with extensions that set this
option.


Migration
=========

Use TSconfig options like `RTE.default.removeTags` to specify which tags should be removed when saving data
to the database, or even better `RTE.default.proc.allowTags` which tags are whitelisted to the database together
with the default RTE processing command `ts_css`.

.. index:: TSConfig, Backend, RTE
