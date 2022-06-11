.. include:: /Includes.rst.txt

.. _custom-reports:

============================
Custom reports registration
============================

The only report provided by the TYPO3 core is the one
called :guilabel:`Status`.

The status report itself is extendable and shows status messages like a system
environment check and the status of the installed extensions.

.. versionchanged:: 12.0
    Reports and status are  automatically registered through the service
    configuration, based on the implemented interface. See section
    :ref:`migration` for more information.

.. _register-custom-report:

Register a custom report
========================

All reports have to implement the interface
:php:interface:`TYPO3\\CMS\\Reports\\ReportInterface`.
This, way, the report is automatically registered if :yaml:`autoconfigure`
is enabled in :file:`Services.yaml`:

.. include:: /CodeSnippets/Manual/Autoconfigure.rst.txt

Alternatively, one can manually tag a custom report with the
:yaml:`reports.report` tag:

.. include:: /CodeSnippets/Manual/RegisterReport.rst.txt


.. _register-custom-status:

Register a custom status
========================

All status providers must implement
:php:class:`TYPO3\\CMS\\Reports\\StatusProviderInterface`.
If :yaml:`autoconfigure` is enabled in :file:`Services.yaml`,
the status providers implementing this interface will be automatically
registered.

Alternatively, one can manually tag a custom report with the
:yaml:`reports.status` tag:

.. include:: /CodeSnippets/Manual/RegisterStatus.rst.txt

More about custom reports
=========================

.. toctree::
    :titlesonly:

    Migration
