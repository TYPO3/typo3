.. include:: /Includes.rst.txt

.. _breaking-97320:

=======================================================================
Breaking: #97320 - Register Report and Status via Service Configuration
=======================================================================

See :issue:`97320`

Description
===========

The `reports` and `status` in EXT:reports are now registered via service
configuration, see the :doc:`feature changelog <Feature-97320-NewRegistrationForReportsAndStatus>`.
Therefore the registration via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']`
has been removed.

Additionally, to be able to use autoconfiguration, the following interfaces have been extended:

- :php:`TYPO3\CMS\Reports\ReportInterface`: :php:`getIdentifier`, :php:`getIconIdentifier`, :php:`getTitle`, :php:`getDescription`
- :php:`TYPO3\CMS\Reports\StatusProviderInterface`: :php:`getLabel`

Impact
======

Registration of custom `reports` via :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']`
are not evaluated anymore.

Registration of custom `status` via :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']`
are not evaluated anymore.

:php:`ReportInterface` and :php:`StatusProviderInterface`: are extended by the mentioned methods. If the required methods are not implemented it will lead to fatal errors.

Affected Installations
======================

All TYPO3 installations using the old registration.

All TYPO3 installations with custom `reports`, not implementing :php:`public function getIdentifier()`,
:php:`public function getIconIdentifier()`, :php:`public function getTitle()`, :php:`public function getDescription()`

All TYPO3 installations with custom `status`, not implementing
:php:`public function getLabel()`

Migration
=========

By implementing the required methods of the interfaces, the custom reports are fully backwards compatible.

If TYPO3 v12+ is the only supported version, the configuration :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']` from the :file:`ext_localconf.php` file can be removed as well.

Report
------

If :yaml:`autoconfigure` is not enabled in your :file:`Configuration/Services.(yaml|php)`,
add the tag :yaml:`reports.report` manually to your `reports` service.

..  code-block:: yaml

    Vendor\Extension\Report\MyReport:
      tags:
        - name: reports.report

The old registration can be removed, if support for TYPO3 v11 or lower is not
necessary.

..  code-block:: php

    // Before in ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['extension']['general'] = [
        'title' => 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:title',
        'description' => 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:description',
        'icon' => 'EXT:extension/Resources/Public/Icons/Extension.svg',
        'report' => \Vendor\Extension\Report::class
    ];

Additionally, make sure to implement all methods of :php:`TYPO3\CMS\Reports\ReportInterface`.

..  code-block:: php

    // Changes for the report

    class Report implements ReportInterface
    {
        public function getReport(): string
        {
            return 'Full report';
        }

        public function getIdentifier(): string
        {
            return 'general';
        }

        public function getTitle(): string
        {
            return 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:title';
        }

        public function getDescription(): string
        {
            return 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:description';
        }

        public function getIconIdentifier(): string
        {
            return 'module-reports';
        }
    }

Refer to the :ref:`Icon API <feature-94692-1657826754>`
on how to register the icon.

Status
------

If :yaml:`autoconfigure` is not enabled in your :file:`Configuration/Services.(yaml|php)`,
add the tag :yaml:`reports.status` manually to your `status` service.

..  code-block:: yaml

    Vendor\Extension\Status\MyStatus:
      tags:
        - name: reports.report

The old registration can be removed, if support for TYPO3 v11 or lower is not
necessary.

..  code-block:: php

    // Before in ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['label'] = [
        \Vendor\Extension\Status::class,
    ];

Additionally, make sure to implement all methods of :php:`TYPO3\CMS\Reports\StatusProviderInterface`.

..  code-block:: php

    // Changes for the Status

    class Status implements StatusProviderInterface
    {
        public function getStatus(): array
        {
            return [];
        }

        public function getLabel(): string
        {
            return 'label';
        }
    }

.. index:: Backend, LocalConfiguration, PHP-API, FullyScanned, ext:reports
