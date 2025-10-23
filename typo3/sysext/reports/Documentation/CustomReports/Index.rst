.. include:: /Includes.rst.txt

..  _custom-reports-more:
..  _custom-reports:

============================
Custom reports registration
============================

The only report provided by the TYPO3 core is the one
called :guilabel:`Status`.

The status report itself is extendable and shows status messages like a system
environment check and the status of the installed extensions.

Reports and status are automatically registered through the service
configuration, based on the implemented interface.

..  _register-custom-report:

Register a custom report
========================

Create a custom submodule extending the reports module.

.. code-block:: php

    <?php
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

Implement the logic into your class:

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace MyVender\MyExtension\Reports;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Backend\Attribute\AsController;
    use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

    #[AsController]
    final readonly class MyReportController
    {
        public function __construct(
            protected ModuleTemplateFactory $moduleTemplateFactory,
        ) {}

        public function handleRequest(ServerRequestInterface $request): ResponseInterface
        {
            $view = $this->moduleTemplateFactory->create($request);
            $view->makeDocHeaderModuleMenu();
            $view->assign('data', $this->collectReportData());
            return $view->renderResponse('MyReport');
        }
    }

Use a template like below:

.. code-block:: html

    <html
        xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
        data-namespace-typo3-fluid="true"
    >

    <f:layout name="Module"/>
    <f:section name="Content">
        <h1>My report</h1>
        <p>Report it!</p>
    </f:section>
    </html>

..  _register-custom-status:

Register a custom status
========================

All status providers must implement
:php:interface:`TYPO3\\CMS\\Reports\\StatusProviderInterface`.
If :yaml:`autoconfigure` is enabled in :file:`Services.yaml`,
the status providers implementing this interface will be automatically
registered.

.. include:: /CodeSnippets/Manual/Autoconfigure.rst.txt

Alternatively, one can manually tag a custom report with the
:yaml:`reports.status` tag:
