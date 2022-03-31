.. include:: /Includes.rst.txt

===========================================================
Feature: #84466 - Request aware interfaces added to reports
===========================================================

See :issue:`84466`

Description
===========

Two new interfaces where added to mark reports and status providers as request aware:

* :php:`TYPO3\CMS\Reports\RequestAwareReportInterface` (extends :php:`TYPO3\CMS\Reports\ReportInterface`)
* :php:`TYPO3\CMS\Reports\RequestAwareStatusProviderInterface` (extends :php:`TYPO3\CMS\Reports\StatusProviderInterface`)

Both interfaces allow reports or status providers to receive an optional PSR-7 server request argument for their
respective interface methods:

* :php:`getReport()`
* :php:`getStatus()`


Impact
======

Reports and status providers can now cleanly access information from the current server request.
They only need to implement one of the interfaces to get the current server request injected.

.. index:: Backend, PHP-API, ext:reports
