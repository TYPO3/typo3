.. include:: /Includes.rst.txt

.. _StatusProviderInterfaceAPI:

=======================
StatusProviderInterface
=======================

Classes implementing this interface are registered automatically as status
in the module :guilabel:`Reports > Status` if :yaml:`autoconfigure` is enabled in
:file:`Services.yaml` or if it was registered manually by the tag
:ref:`reports.status <register-custom-status>`.

If information from the current request is required for the status report implement
:php:interface:`TYPO3\\CMS\\Reports\\RequestAwareStatusProviderInterface`.

If you need to provide extended information implement
:php:interface:`TYPO3\\CMS\\Reports\\ExtendedStatusProviderInterface`.

.. note::
    In PHP it is possible to implement several interfaces, so you can
    give detailed status reports that are request-aware:

    .. code-block:: php
        :caption: EXT:my_extension/Classes/Status/MyStatus.php

        class MyStatus implements RequestAwareStatusProviderInterface, ExtendedStatusProviderInterface
        {
            // ...
        }

API
===

.. include:: /CodeSnippets/Generated/StatusProviderInterface.rst.txt
