.. include:: /Includes.rst.txt

.. _feature-97320:

=========================================================
Feature: #97320 - New registration for reports and status
=========================================================

See :issue:`97320`

Description
===========

The system extension `reports` provides the possibility to render various reports.
The most prominent and (only one) provided by the TYPO3 Core is the one called `Status`.
The Status Report itself is extendable and shows status like a system environment check
and status of the used extensions.

Reports
-------

As all `reports` have to implement the :php:`ReportInterface` this fact is now
used to automatically register the `report`, based on the interface,
if :yaml:`autoconfigure` is enabled in :file:`Services.yaml`. Alternatively,
one can manually tag a custom `report` with the
:yaml:`reports.report` tag (see section "Migration" in the
:doc:`breaking changelog <Breaking-97320-RegisterReportAndStatusViaServiceConfiguration>`).

Due to the autoconfiguration, the following methods have to be implemented:

- :php:`getIdentifier`
- :php:`getIconIdentifier`
- :php:`getTitle`
- :php:`getDescription`

Status
------

As all `status` have to implement the :php:`StatusProviderInterface` this fact is now
used to automatically register the `status`, based on the interface,
if :yaml:`autoconfigure` is enabled in :file:`Services.yaml`. Alternatively,
one can manually tag a custom `report` with the
:yaml:`reports.status` tag (eee section "Migration" in the
:doc:`breaking changelog <./Breaking-97320-RegisterReportAndStatusViaServiceConfiguration>`).

Due to the autoconfiguration, the label has to be provided by the
class directly, using the now required :php:`getLabel()` method.

Impact
======

`reports` and `status` are now automatically registered through the service
configuration, based on the implemented interface.

.. index:: Backend, PHP-API, ext:reports
