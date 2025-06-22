:navigation-title: Backend Modules

..  include:: /Includes.rst.txt
..  _backend-modules:

========================================
Backend modules provided by EXT:lowlevel
========================================

The Lowlevel system extension provides two backend modules:

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`DB Check <module-db-check>`

        Offers modules that search or give statistics about the database.

    ..  card:: :ref:`Configuration <module-configuration>`

        Gives insights into different configuration values. In cases where
        the final configuration gets combined at different levels this
        module can be used to debug the final output. Configuration values
        cannot be changed in this module.

..  toctree::
    :hidden:
    :titlesonly:

    DbCheck
    Configuration
