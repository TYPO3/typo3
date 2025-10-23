..  include:: /Includes.rst.txt

..  _feature-107791-1234567890:

================================================================
Feature: #107791 - Reports module uses native submodule overview
================================================================

See :issue:`107791`

Description
===========

The Reports module has been refactored to use TYPO3's native submodule system
with the :php:`showSubmoduleOverview` feature instead of a custom
report registration mechanism. This provides a more consistent user experience
and aligns the Reports module with other TYPO3 backend modules.

The module now displays a card-based overview of available reports, similar to
other modules like the :guilabel:`Web > Info` module. Each report is registered
as a proper backend submodule in :file:`Configuration/Backend/Modules.php`.

Changes
=======

**Module structure:**

*   :guilabel:`System > Reports` is now a container module with
    :php:`showSubmoduleOverview` enabled
*   Individual reports (Status, Record Statistics) are registered as submodules
*   The native submodule overview automatically provides cards with icons,
    titles, and descriptions

**Benefits:**

*   Automatic "Module Overview" menu item in the submodule dropdown
*   Consistent navigation experience across all TYPO3 backend modules
*   Simpler architecture without custom registration infrastructure
*   Easier to extend - just add submodules to :file:`Modules.php`

Example
=======

Creating a custom report is now as simple as registering a backend submodule:

.. code-block:: php
   :caption: EXT:my_extension/Configuration/Backend/Modules.php

   return [
       'system_reports_myreport' => [
           'parent' => 'system_reports',
           'access' => 'admin',
           'path' => '/module/system/reports/myreport',
           'iconIdentifier' => 'my-report-icon',
           'labels' => [
               'title' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myreport.title',
               'description' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myreport.description',
           ],
           'routes' => [
               '_default' => [
                   'target' => \Vendor\MyExtension\Controller\MyReportController::class . '::handleRequest',
               ],
           ],
       ],
   ];

The controller returns a standard PSR-7 response with rendered content:

.. code-block:: php
   :caption: EXT:my_extension/Classes/Controller/MyReportController.php

   #[AsController]
   final readonly class MyReportController
   {
       public function __construct(
           protected ModuleTemplateFactory $moduleTemplateFactory,
       ) {}

       public function handleRequest(ServerRequestInterface $request): ResponseInterface
       {
           $view = $this->moduleTemplateFactory->create($request);
           $view->assign('data', $this->collectReportData());
           return $view->renderResponse('MyReport');
       }
   }

Impact
======

The Reports module provides a cleaner, more intuitive interface using the
standard TYPO3 module navigation patterns. Extension developers can now create
custom reports using familiar backend module registration instead of
implementing specific interfaces.

..  index:: Backend, PHP-API, ext:reports
