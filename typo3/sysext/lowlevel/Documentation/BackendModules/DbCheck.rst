.. include:: /Includes.rst.txt

.. _module-db-check:

=========================
DB Check
=========================

Access this module in the TYPO3 backend under :guilabel:`System > DB Check`.

.. include:: /Images/AutomaticScreenshots/Modules/DB_Check.rst.txt

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`Record statistics <module-db-check-Records-Statistics>`

        Gives you an overview of how many pages of which type and how many
        records of any table are present in the current system.


    ..  card:: :ref:`Database Relations <module-db-check-Database-Relations>`

        Gives an overview of the count of lost relations in select and group
        fields

    ..  card:: :ref:`Full Search <module-db-check-full-search>`

        Search the complete database or specific table / field combinations
        and offers you detail and edit links to jump directly to the records
        found.

    ..  card:: :ref:`Manage Reference Index <module-db-check-Manage-Reference-Index>`

        Can be used on smaller installations to check or update the
        reference index.

.. toctree::
    :hidden:
    :titlesonly:

    DbCheck/RecordsStatistics
    DbCheck/DatabaseRelations
    DbCheck/FullSearch
    DbCheck/ManageReferenceIndex
