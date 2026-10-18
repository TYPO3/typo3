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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\PageTsConfig;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigRecordsOverviewController;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTsConfigRecordsOverviewControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function pagesAreListedInPageTreeOrder(): void
    {
        $module = $this->get(ModuleProvider::class)->getModule('pagetsconfig_pages', $GLOBALS['BE_USER']);
        $request = (new ServerRequest('https://example.com/typo3/module/pagetsconfig/records'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/pagetsconfig/records', [
                'packageName' => 'typo3/cms-backend',
                '_identifier' => 'pagetsconfig_pages',
            ]))
            ->withAttribute('module', $module)
            ->withAttribute('moduleData', new ModuleData('pagetsconfig_pages', []));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $content = (string)$this->get(PageTsConfigRecordsOverviewController::class)->handleRequest($request)->getBody();

        preg_match_all('/data-contextmenu-uid="(\d+)"/', $content, $matches);
        self::assertSame(['1', '4', '6', '5', '3', '2'], $matches[1]);
    }
}
