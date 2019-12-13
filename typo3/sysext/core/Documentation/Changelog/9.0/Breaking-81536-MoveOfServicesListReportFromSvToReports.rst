.. include:: ../../Includes.txt

.. _changelog-MoveOfServicesListReportFromSvToReports:

=============================================================
Breaking: #81536 - Move ServicesListReport From Sv to Reports
=============================================================

See :issue:`81536`

Description
===========

The service list report has been moved from `EXT:sv` to `EXT:reports`.


Impact
======

Because of the new location the namespace of the class :php:`ServicesListReport` has been
changed from :php:`TYPO3\CMS\Sv\Report` to :php:`TYPO3\CMS\Reports\Report`. Additionally the
relevant language file has been moved from :file:`EXT:sv/Resources/Private/Language/locallang.xlf`
to :file:`EXT:reports/Resources/Private/Language/serviceReport.xlf`.


Affected Installations
======================

All installations or 3rd party extensions which directly access the used files of the report:

- :php:`TYPO3\CMS\Sv\Report\ServicesListReport`
- :file:`EXT:sv/Resources/Private/Language/locallang.xlf`
- :file:`EXT:sv/Resources/Private/Templates/ServicesListReport.html`
- :file:`EXT:sv/Resources/Public/Images/service-reports.png`


Migration
=========

Use the new namespace :php:`TYPO3\CMS\Reports\Report\ServicesListReport` and the location of the files:

- :file:`EXT:reports/Resources/Private/Language/serviceReport.xlf`
- :file:`EXT:reports/Resources/Private/Templates/ServicesListReport.html`
- :file:`EXT:reports/Resources/Public/Images/service-reports.png`

Related
=======

- :ref:`changelog-Breaking-81735-GetRidOfSysextsv`

More Information
================

- :ref:`t3coreapi:services-developer-service-api` in "TYPO3 Explained"


.. index:: Backend, PartiallyScanned, ext:sv, ext:reports
