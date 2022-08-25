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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Slug;

use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SlugHelperUniqueTest extends AbstractDataHandlerActionTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/DataSet/PagesForSlugsUnique.csv');
        $this->setUpFrontendSite(1);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    /**
     * @test
     */
    public function buildSlugForUniqueInSiteRespectsMaxRetryOverflow(): void
    {
        $subject = GeneralUtility::makeInstance(
            SlugHelper::class,
            'pages',
            'slug',
            [
                'generatorOptions' => [
                    'fields' => ['title'],
                    'prefixParentPageSlug' => true,
                ],
            ]
        );

        $state = RecordStateFactory::forName('pages')->fromArray(['uid' => 'NEW102', 'pid' => 1]);
        $overflowSlug = $subject->buildSlugForUniqueInSite('/unique-slug', $state);
        $parts = explode('-', $overflowSlug);
        if (count($parts) !== 3) {
            self::fail('No suffix to the slug was created');
        }
        $variablePartOfSlug = end($parts);
        self::assertSame(32, strlen($variablePartOfSlug));
    }

    /**
     * @test
     */
    public function buildSlugForUniqueInPidRespectsMaxRetryOverflow(): void
    {
        $subject = GeneralUtility::makeInstance(
            SlugHelper::class,
            'pages',
            'slug',
            [
                'generatorOptions' => [
                    'fields' => ['title'],
                    'prefixParentPageSlug' => true,
                ],
            ]
        );
        $state = RecordStateFactory::forName('pages')->fromArray(['uid' => 'NEW102', 'pid' => 1]);
        $overflowSlug = $subject->buildSlugForUniqueInPid('/unique-slug', $state);
        $parts = explode('-', $overflowSlug);
        if (count($parts) !== 3) {
            self::fail('No suffix to the slug was created');
        }
        $variablePartOfSlug = end($parts);
        self::assertSame(32, strlen($variablePartOfSlug));
    }

    /**
     * @test
     */
    public function buildSlugForUniqueInTableRespectsMaxRetryOverflow(): void
    {
        $subject = GeneralUtility::makeInstance(
            SlugHelper::class,
            'pages',
            'slug',
            [
                'generatorOptions' => [
                    'fields' => ['title'],
                    'prefixParentPageSlug' => true,
                ],
            ]
        );

        $state = RecordStateFactory::forName('pages')->fromArray(['uid' => 'NEW102', 'pid' => 1]);
        $overflowSlug = $subject->buildSlugForUniqueInTable('/unique-slug', $state);
        $parts = explode('-', $overflowSlug);
        if (count($parts) !== 3) {
            self::fail('No suffix to the slug was created');
        }
        $variablePartOfSlug = end($parts);
        self::assertSame(32, strlen($variablePartOfSlug));
    }
}
