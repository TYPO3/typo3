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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SlugHelperUniqueTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/PagesForSlugsUnique.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
        );
    }

    #[Test]
    public function buildSlugForUniqueInSiteRespectsMaxRetryOverflow(): void
    {
        $subject = new SlugHelper(
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

    #[Test]
    public function buildSlugForUniqueInPidRespectsMaxRetryOverflow(): void
    {
        $subject = new SlugHelper(
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

    #[Test]
    public function buildSlugForUniqueInTableRespectsMaxRetryOverflow(): void
    {
        $subject = new SlugHelper(
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
