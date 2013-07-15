.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _configuration:

Configuration
-------------

This section describes user TSconfig configuration for backend user or
groups.


.. _recordspagelimit:

recordsPageLimit
^^^^^^^^^^^^^^^^

.. container:: table-row

   Property
         recordsPageLimit
   
   Data type
         integer
   
   Description
         How many records displaying in grid per page. Default is 50.
         
         Example:
         
         mod.recycler.recordsPageLimit = 100



.. _allowdelete:

allowDelete
^^^^^^^^^^^

.. container:: table-row

   Property
         allowDelete
   
   Data type
         boolean
   
   Description
         Editors are not allowed to delete any record by default. Setting this
         property allows editor deleting records.
         
         Example:
         
         mod.recycler.allowDelete = 1


