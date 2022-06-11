.. include:: /Includes.rst.txt

.. _ReportInterfaceAPI:

===============
ReportInterface
===============

Classes implementing this interface are registered automatically as reports
in the module :guilabel:`Reports` if :yaml:`autoconfigure` is enabled in
:file:`Services.yaml` or if it was registered manually by the tag
:ref:`reports.report <register-custom-report>`.

If information from the current request is required for the report use
:php:interface:`TYPO3\\CMS\\Reports\\RequestAwareReportInterface`.

API
===

.. include:: /CodeSnippets/Generated/ReportInterface.rst.txt
