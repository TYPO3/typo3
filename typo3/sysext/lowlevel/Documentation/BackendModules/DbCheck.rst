:navigation-title: DB Check

..  include:: /Includes.rst.txt
..  _module-db-check:

========================
Module System > DB Check
========================

Access this module in the TYPO3 backend under :guilabel:`System > DB Check`.

..  versionchanged:: 14.0
    The "Record statistics" submodule was moved into the module
    :guilabel:`System > Reports`, submodule
    `Record Statistics <https://docs.typo3.org/permalink/typo3/cms-reports:introduction>`_.

    The module is provided by the optional system extension
    :composer:`typo3/cms-reports`.

..  include:: /Images/AutomaticScreenshots/Modules/DB_Check.rst.txt

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`Full Search <module-db-check-full-search>`

        Search the complete database or specific table / field combinations
        and offers you detail and edit links to jump directly to the records
        found.

..  toctree::
    :hidden:
    :titlesonly:

    DbCheck/FullSearch
