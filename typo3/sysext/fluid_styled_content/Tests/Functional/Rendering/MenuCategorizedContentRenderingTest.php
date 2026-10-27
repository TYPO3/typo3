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

namespace TYPO3\CMS\FluidStyledContent\Tests\Functional\Rendering;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MenuCategorizedContentRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid_styled_content/Tests/Functional/Fixtures/Extensions/test_tt_content_tablenames',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MenuCategorizedContent/MenuCategorizedContent.csv');
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => ['EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript'],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:fluid_styled_content/Tests/Functional/Rendering/Fixtures/MenuCategorizedContent/setup.typoscript',
                ],
            ]
        );
    }

    #[Test]
    public function menuCategorizedContentRendersContentOfSelectedCategoryWithTtContentHavingMmColumnNames(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
        $body = (string)$response->getBody();
        self::assertStringContainsString('Element in selected category', $body);
        self::assertStringNotContainsString('Element in other category', $body);
        self::assertStringNotContainsString('Element related via other field', $body);
        self::assertStringNotContainsString('Element related via other table', $body);
    }
}
