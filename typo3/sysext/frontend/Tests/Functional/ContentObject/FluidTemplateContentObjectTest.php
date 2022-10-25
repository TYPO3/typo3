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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FluidTemplateContentObjectTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [];
    protected const ROOT_PAGE_ID = 1;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet('typo3/sysext/frontend/Tests/Functional/ContentObject/Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'fluid_template',
            $this->buildSiteConfiguration(self::ROOT_PAGE_ID, '/'),
        );
    }

    /**
     * @test
     */
    public function renderWorksWithNestedFluidTemplate(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/nested_fluid_template.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('ABC', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function renderWorksWithNestedFluidTemplateWithLayouts(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/nested_fluid_template_with_layout.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        $responseBody = (string)$response->getBody();
        self::assertStringContainsString('Default Layout', $responseBody);
        self::assertStringContainsString('LayoutOverride', $responseBody);
    }

    /**
     * @test
     */
    public function stdWrapAppliesForTemplateRootPaths(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/template_rootpaths_stdwrap.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Foobar', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function optionFileIsUsedAsTemplate(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/file.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Foobar', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function optionTemplateIsUsedAsCObjTemplate(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/template.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('My fluid template', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function optionTemplateNameIsUsedAsHtmlFileInTemplateRootPaths(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/template_name.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Foobar', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function stdWrapIsAppliedOnOptionTemplateName(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/template_name_stdwrap.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Foobar', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function layoutIsFoundInLayoutRootPath(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/layout_root_path.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        $responseBody = (string)$response->getBody();
        self::assertStringContainsString('Alternative Layout', $responseBody);
        self::assertStringContainsString('Alternative Template', $responseBody);
    }

    /**
     * @test
     */
    public function layoutRootPathHasStdWrapSupport(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/layout_root_path_stdwrap.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        $responseBody = (string)$response->getBody();
        self::assertStringContainsString('Alternative Layout', $responseBody);
        self::assertStringContainsString('Alternative Template', $responseBody);
    }

    /**
     * @test
     */
    public function layoutRootPathsHasStdWrapSupport(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/layout_root_paths_stdwrap.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        $responseBody = (string)$response->getBody();
        self::assertStringContainsString('Alternative Layout', $responseBody);
        self::assertStringContainsString('Alternative Template', $responseBody);
    }

    /**
     * @test
     */
    public function fallbacksForLayoutRootPathsAreApplied(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/layout_root_paths_fallback.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        $responseBody = (string)$response->getBody();
        self::assertStringContainsString('LayoutOverride', $responseBody);
        self::assertStringContainsString('Main Template', $responseBody);
    }

    /**
     * @test
     */
    public function fallbacksForLayoutRootPathAreAppendedToLayoutRootPath(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/layout_root_path_and_paths_fallback.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        $responseBody = (string)$response->getBody();
        self::assertStringContainsString('LayoutOverride', $responseBody);
        self::assertStringContainsString('Main Template', $responseBody);
    }

    /**
     * @test
     */
    public function partialsInPartialRootPathAreFound(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/partial.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Template with Partial', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function partialRootPathHasStdWrapSupport(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/partial_stdwrap.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Template with Partial', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function partialRootPathsHasStdWrapSupport(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/partial_root_paths_stdwrap.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Template with Partial', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function fallbacksForPartialRootPathsAreApplied(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/partial_root_paths_fallback.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Template with Partial Override', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function fallbacksForPartialRootPathAreAppendedToPartialRootPath(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/partial_root_path_and_paths_fallback.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Template with Partial Override', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function formatOverridesDefaultHtmlSuffix(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/format.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('FoobarXML', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function stdWrapIsAppliedOnOptionFormat(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/format_stdwrap.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('FoobarXML', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function settingsAreAssignedToTheView(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/settings.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('I am coming from the settings', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function renderThrowsExceptionForReservedVariableNameData(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/variables_reserved_data.typoscript',
            ]
        );
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288095720);
        $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
    }

    /**
     * @test
     */
    public function renderThrowsExceptionForReservedVariableNameCurrent(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/variables_reserved_current.typoscript',
            ]
        );
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288095720);
        $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
    }

    /**
     * @test
     */
    public function cObjectIsAppliedOnVariables(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/variables.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('I am coming from the variables', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function contentObjectRendererDataIsAvailableInView(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/data.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('FluidTemplateContentObjectTest', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function renderAssignsContentObjectRendererCurrentValueToView(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/current.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('My current value', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function stdWrapIsAppliedOnOverallFluidTemplate(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/fluid_template_stdwrap.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('1+1=2', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function renderFluidTemplateAssetsIntoPageRendererRendersAndAttachesAssets(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/assets.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        $responseBody = (string)$response->getBody();
        self::assertStringContainsString('Foo Header' . "\n" . '</head>', $responseBody);
        self::assertStringContainsString('Foo Footer' . "\n" . '</body>', $responseBody);
    }
}
