.. include:: /Includes.rst.txt

.. _module-db-check-full-search:

=================================================
Full Search
=================================================

Users with administrator rights can find this module at
:guilabel:`System > DB Check > Full Search`.

Raw search of all fields
========================

This module offers a :guilabel:`raw search of all fields`. Here you can enter
a string and do a raw search.

The result is grouped by record type and there are buttons to edit or get to
the info of each record.

.. include:: /Images/AutomaticScreenshots/Modules/DB_Check_Full_search.rst.txt

Advanced query
==============

If you select the option :guilabel:`Advanced query` you can choose the table
to search and search in different fields.

.. include:: /Images/AutomaticScreenshots/Modules/DB_Check_Advanced_query.rst.txt

Chose a table to search in. Then you can search for certain fields. The GUI
will let you choose from values that can be entered according to the
:doc:`TCA <t3tca:Index>`.

For example you can search in table :guilabel:`Page Content`
(SQL table :sql:`tt_content`) for content elements of a certain type, stored in
data base field :guilabel:`Type`, SQL field :sql:`CType`. A drop down gives you
options on the search mode (equals, contains, is in list, ...) and another
drop down lets you choose the value to search for. In this case it lists all
available content element types.

You can select more options like group by, order by and limit.

It is also possible to save and load queries. The queries will be saved
serialized in the column :sql:`uc` of table :sql:`be_users`. It can therefore
not be shared between users.

Just like in the raw search there are buttons to edit or get to
the info of each record.

The :guilabel:`Advanced query` action can be
:ref:`configured with user TSconfig <configuration-full-search>`.

Example
-------

Search for content elements of type "Header":

.. include:: /Images/AutomaticScreenshots/Modules/DB_Check_Advanced_query_tt_content.rst.txt
