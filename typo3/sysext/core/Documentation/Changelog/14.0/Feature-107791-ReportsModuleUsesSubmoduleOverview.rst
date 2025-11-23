..  include:: /Includes.rst.txt

..  _feature-107791-1234567890:

================================================================
Feature: #107791 - Reports module uses native submodule overview
================================================================

See :issue:`107791`

Description
===========

The :guilabel:`Administration > Reports` module has been refactored to use
TYPO3 Core’s native submodule system with the `showSubmoduleOverview` feature
instead of a custom report registration mechanism. This provides a more
consistent user experience and aligns the :guilabel:`Reports` module with
other TYPO3 backend modules.

The module now displays a card-based overview of available reports, similar to
other modules like the :guilabel:`Content > Status` module. Each report is registered
as a proper backend submodule in :file:`Configuration/Backend/Modules.php`.

..  note::
    The top-level backend modules and some second level modules were renamed
    in TYPO3 v14.

    For details, see:
    `Feature: #107628 – Improved backend module naming and structure <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.


Changes
=======

**Module structure:**

*   :guilabel:`Administration > Reports` is now a container module with
    :php:`showSubmoduleOverview` enabled.
*   Individual reports (Status, Record Statistics) are registered as submodules.
*   The native submodule overview automatically provides cards with icons,
    titles, and descriptions.

**Benefits:**

*   Automatic "Module Overview" menu item in the submodule dropdown.
*   Consistent navigation experience across all TYPO3 backend modules.
*   Simpler architecture without custom registration infrastructure.
*   Easier to extend - just add submodules to :file:`Modules.php`.


Example
=======

Creating a custom report now follows the same mechanism as registering a
backend submodule:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Modules.php

    return [
        'system_reports_myreport' => [
            'parent' => 'system_reports',
            'access' => 'admin',
            'path' => '/module/system/reports/myreport',
            'iconIdentifier' => 'my-report-icon',
            'labels' => 'my_extension.module',
            'routes' => [
                '_default' => [
                    'target' => \MyVendor\MyExtension\Controller\MyReportController::class . '::handleRequest',
                ],
            ],
        ],
    ];

The controller returns a standard PSR-7 response with rendered content:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyReportController.php

    use TYPO3\CMS\Core\Attribute\AsController;
    use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;

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

The :guilabel:`Administration > Reports` module now provides a cleaner and more
intuitive interface using standard TYPO3 backend navigation patterns.

Extension developers can create custom reports by registering them as backend
submodules, using the same module registration mechanisms already available in
TYPO3, instead of implementing a separate custom report interface.

..  index:: Backend, PHP-API, ext:reports
