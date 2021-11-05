.. include:: /Includes.rst.txt

.. _backend-modules:

===============
Backend modules
===============

The Lowlevel system extension provides two backend modules:

.. container:: row m-0 p-0

   .. container:: col-md-6 pl-0 pr-3 py-3 m-0

      .. container:: card px-0 h-100

         .. rst-class:: card-header h3

            .. rubric:: :ref:`DB Check <module-db-check>`

         .. container:: card-body

            Offers modules that search or give statistics about the database.

   .. container:: col-md-6 pl-0 pr-3 py-3 m-0

      .. container:: card px-0 h-100

         .. rst-class:: card-header h3

            .. rubric:: :ref:`Configuration <module-configuration>`

         .. container:: card-body

            Gives insights into different configuration values. In cases where
            the final configuration gets combined at different levels this
            module can be used to debug the final output. Configuration values
            cannot be changed in this module.

.. toctree::
    :hidden:
    :titlesonly:

    DbCheck
    Configuration
