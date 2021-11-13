.. include:: /Includes.rst.txt

.. _content-element-table:

=====
Table
=====

.. include:: /Images/AutomaticScreenshots/ContentOverview/TableTypicalPageContent.rst.txt

The :guilabel:`Table` content element can be used to display tabular data.

.. hint::
    In the database the data is saved as comma separated values (CSV), a
    plain text format for storing table data (numbers and text). This format
    can be used to import data from external sources.

.. include:: /Images/AutomaticScreenshots/ContentElements/TableBackend.rst.txt

By default the :guilabel:`Field delimiter` is a vertical bar "|", the
:guilabel:`Text enclosure` set to **none**.

A :guilabel:`Table caption` can be provided as a heading for the table.

.. include:: /Images/AutomaticScreenshots/ContentElements/TableAppearanceBackend.rst.txt

Also some appearance options are available for the table. These can be found in the
:guilabel:`Appearance` tab:

Columns
   The maximum amount of columns. Even when more columns are defined in the
   :guilabel:`Table content` field, the table will only show the maximum amount of columns.

Table class
   Some predefined classes to style the table output.

Table header position
   The first row or the first column can be used as a table header.

Use table footer
   The last row will be used to group the table's footer area (which may be a summary, an
   addition of column values, or some call to action based on the preceding content).
