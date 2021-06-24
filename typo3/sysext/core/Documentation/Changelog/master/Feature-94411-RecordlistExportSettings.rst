.. include:: ../../Includes.txt

============================================
Feature: #94411 - Recordlist export settings
============================================

See :issue:`94411`

Description
===========

In :issue:`94366`, the record export functionality in the recordlist
module was improved. Since then, the export could be triggered via a
button in each tables' header and no longer just in the single table view.

The export however did still not allow to adjust any settings, such as
the definition of a custom filename. Furthermore, only `csv` was available
as possible export format.

Therefore, and to further improve the already existing record export
functionality, the export button in the tables' header does not longer
trigger the export directly, but opens a modal with various adjustable
export settings such as:

* Selection of columns to export: All columns or selected columns
* Selection of the record values format: Either raw database values or processed (resolved) values
* Definition of a custom filename
* Selection of the export format (e.g. `csv`)

Also export format specific options are available, e.g. selection of
the delimiter for `csv` exports.

In case your installation already defines related TSconfig options
(e.g. :typoscript:`mod.web_list.csvDelimiter`), they will be added
as default value to the configuration modal.

Besides introducing those settings, also `json` is now available as
an alternative export format, including a format specific option,
which allows to define additional meta information to be included in
the export.

Impact
======

It's now possible to configure the export of records in the
recordlist. Furthermore, the new format option `json` is available.

.. index:: Backend, ext:recordlist
