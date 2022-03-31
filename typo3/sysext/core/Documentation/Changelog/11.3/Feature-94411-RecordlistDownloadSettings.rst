.. include:: /Includes.rst.txt

===============================================
Feature: #94411 - Record list download settings
===============================================

See :issue:`94411`

Description
===========

In :issue:`94366`, the record download functionality in the List
module was improved. Since then, the download could be triggered via a
button in each table's header and no longer just in the single table view.

The download however did still not allow to adjust any settings, such as
the definition of a custom filename. Furthermore, only CSV was available
as possible download format.

Therefore, and to further improve the already existing record download
functionality, the download button in the table's header does not longer
trigger the download directly, but opens a modal with various adjustable
download settings such as:

*  Selection of columns to download: All columns or selected columns
*  Selection of the record values format: Either raw database values
   or processed (resolved) values
*  Definition of a custom filename
*  Selection of the download format (for example CSV)

Also download format specific options are available, for example selection of
the delimiter for CSV downloads.

In case your installation already defines related TSconfig options
(for example :typoscript:`mod.web_list.csvDelimiter`), they will be added
as default value to the configuration modal.

Besides introducing those settings, also JSON is now available as
an alternative download format, including a format specific option,
which allows to define additional meta information to be included in
the download.

Impact
======

It's now possible to configure the download of records in the
record list. Furthermore, the new format option :php:`json` is available.

.. index:: Backend, ext:recordlist
