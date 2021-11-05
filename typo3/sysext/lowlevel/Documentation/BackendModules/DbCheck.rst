.. include:: /Includes.rst.txt

.. _module-db-check:

=========================
DB Check
=========================

Access this module in the TYPO3 backend under :guilabel:`System > DB Check`.

.. include:: /Images/AutomaticScreenshots/Modules/DB_Check.rst.txt


.. container:: row m-0 p-0

   .. container:: col-md-6 pl-0 pr-3 py-3 m-0

      .. container:: card px-0 h-100

         .. rst-class:: card-header h3

            .. rubric:: :ref:`Record statistics <module-db-check-Records-Statistics>`

         .. container:: card-body

            Gives you an overview of how many pages of which type and how many
            records of any table are present in the current system.

   .. container:: col-md-6 pl-0 pr-3 py-3 m-0

      .. container:: card px-0 h-100

         .. rst-class:: card-header h3

            .. rubric:: :ref:`Database Relations <module-db-check-Database-Relations>`

         .. container:: card-body

            Gives an overview of the count of lost relations in select and group
            fields

   .. container:: col-md-6 pl-0 pr-3 py-3 m-0

      .. container:: card px-0 h-100

         .. rst-class:: card-header h3

            .. rubric:: :ref:`Full Search <module-db-check-full-search>`

         .. container:: card-body

            Search the complete database or specific table / field combinations
            and offers you detail and edit links to jump directly to the records
            found.

   .. container:: col-md-6 pl-0 pr-3 py-3 m-0

      .. container:: card px-0 h-100

         .. rst-class:: card-header h3

            .. rubric:: :ref:`Manage Reference Index <module-db-check-Manage-Reference-Index>`

         .. container:: card-body

            Can be used on smaller installations to check or update the
            reference index.

.. toctree::
    :hidden:
    :titlesonly:

    DbCheck/RecordsStatistics
    DbCheck/DatabaseRelations
    DbCheck/FullSearch
    DbCheck/ManageReferenceIndex
