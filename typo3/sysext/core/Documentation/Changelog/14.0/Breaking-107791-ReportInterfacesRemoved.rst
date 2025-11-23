..  include:: /Includes.rst.txt

..  _breaking-107791-1234567890:

=============================================
Breaking: #107791 - Report interfaces removed
=============================================

See :issue:`107791`

Description
===========

The Reports module has been refactored to use native backend submodules instead
of dynamic service-based report registration. Individual reports are now
registered as proper backend submodules in :file:`Configuration/Backend/Modules.php`.

As a result, the following public interfaces have been removed:

*   :php:`\TYPO3\CMS\Reports\ReportInterface`
*   :php:`\TYPO3\CMS\Reports\RequestAwareReportInterface`

Impact
======

Extensions that register custom reports by implementing
:php:`\TYPO3\CMS\Reports\ReportInterface` or
:php:`\TYPO3\CMS\Reports\RequestAwareReportInterface` will no longer work.

These reports will no longer appear in the backend Reports module.

Affected installations
======================

TYPO3 installations with custom extensions that provide reports by implementing
:php-short:`\TYPO3\CMS\Reports\ReportInterface` or
:php-short:`\TYPO3\CMS\Reports\RequestAwareReportInterface`.

Migration
=========

Custom reports must be migrated to backend submodules.

**Register as a submodule under `system_reports`:**

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Modules.php

    use Vendor\MyExtension\Controller\MyReportController;

    return [
        'system_reports_myreport' => [
            'parent' => 'system_reports',
            'access' => 'admin',
            'path' => '/module/system/reports/myreport',
            'iconIdentifier' => 'module-reports',
            'labels' => [
                'title' => 'my_extension.messages:myreport.title',
                'description' => 'my_extension.messages:myreport.description',
            ],
            'routes' => [
                '_default' => [
                    'target' => MyReportController::class . '::handleRequest',
                ],
            ],
        ],
    ];

The controller should implement a standard PSR-7 request handler that returns a
:php:`\Psr\Http\Message\ResponseInterface` instance.

Alternatively, you can create a standalone module with
`showSubmoduleOverview` enabled if you need to group multiple reports
under your own container module.

..  index:: Backend, PHP-API, NotScanned, ext:reports
