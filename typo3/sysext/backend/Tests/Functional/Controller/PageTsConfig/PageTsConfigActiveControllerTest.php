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
use TYPO3\CMS\Backend\Controller\PageTsConfig\PageTsConfigActiveController;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Crypto\HashAlgo;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTsConfigActiveControllerTest extends FunctionalTestCase
{
    private const string CONDITION = 'page["uid"] == 1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pagesWithCondition.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function conditionToggledWithLegacyHashIsStillActive(): void
    {
        $moduleData = $this->createModuleData([HashAlgo::XXH3->hash(self::CONDITION)]);

        $html = (string)$this->get(PageTsConfigActiveController::class)->handleRequest($this->createRequest($moduleData))->getBody();

        $conditionHash = HashAlgo::canonicalize()->hash(self::CONDITION);
        self::assertMatchesRegularExpression('/id="condition' . $conditionHash . '"[^>]*checked="checked"/', $html);
    }

    #[Test]
    public function conditionToggledWithLegacyHashIsStoredWithCurrentHashOnSave(): void
    {
        $legacyHash = HashAlgo::XXH3->hash(self::CONDITION);
        $conditionHash = HashAlgo::canonicalize()->hash(self::CONDITION);
        $moduleData = $this->createModuleData([$legacyHash]);

        $this->get(PageTsConfigActiveController::class)->handleRequest(
            $this->createRequest($moduleData, ['pageTsConfigConditions' => [$conditionHash => '1']])
        );

        self::assertSame([$conditionHash], $moduleData->get('pageTsConfigConditions'));
    }

    private function createModuleData(array $pageTsConfigConditions): ModuleData
    {
        $module = $this->get(ModuleProvider::class)->getModule('pagetsconfig_active');
        return ModuleData::createFromModule($module, ['pageTsConfigConditions' => $pageTsConfigConditions]);
    }

    private function createRequest(ModuleData $moduleData, ?array $parsedBody = null): ServerRequest
    {
        $module = $this->get(ModuleProvider::class)->getModule('pagetsconfig_active');
        $request = new ServerRequest('https://example.com/typo3/module/pagetsconfig/active')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/pagetsconfig/active', ['_identifier' => 'pagetsconfig_active']))
            ->withAttribute('module', $module)
            ->withAttribute('moduleData', $moduleData)
            ->withQueryParams(['id' => 1]);
        if ($parsedBody !== null) {
            $request = $request->withMethod('POST')->withParsedBody($parsedBody);
        }
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        return $request;
    }
}
