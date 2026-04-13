<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\ColumnSelectorController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ColumnSelectorControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function showColumnsSelectorActionHandlesFieldsWithoutLabel(): void
    {
        $request = (new ServerRequest('https://example.com/typo3/ajax/show-columns-selector'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/typo3/ajax/show-columns-selector', ['_identifier' => 'ajax_show_columns_selector']))
            ->withQueryParams(['table' => 'pages', 'id' => 0]);

        $controller = $this->get(ColumnSelectorController::class);
        $response = $controller->showColumnsSelectorAction($request);

        self::assertSame(200, $response->getStatusCode());
        $body = (string)$response->getBody();

        // Verify that workspace fields (which have no TCA column definition) are rendered
        // without causing an exception. t3ver_stage specifically had no label defined.
        self::assertStringContainsString('t3ver_stage', $body);
    }

    #[Test]
    public function showColumnsSelectorActionRendersWorkspaceFieldLabels(): void
    {
        $request = (new ServerRequest('https://example.com/typo3/ajax/show-columns-selector'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/typo3/ajax/show-columns-selector', ['_identifier' => 'ajax_show_columns_selector']))
            ->withQueryParams(['table' => 'pages', 'id' => 0]);

        $controller = $this->get(ColumnSelectorController::class);
        $response = $controller->showColumnsSelectorAction($request);

        $body = (string)$response->getBody();

        // Fields that have labels defined in locallang_core.xlf should be resolved
        self::assertStringContainsString('Workspace stage', $body);
        self::assertStringContainsString('Workspace status', $body);
        self::assertStringContainsString('Workspace ID', $body);
    }
}
