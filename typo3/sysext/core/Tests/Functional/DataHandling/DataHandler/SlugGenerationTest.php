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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to automatic slug generation of pages by DataHandler
 */
final class SlugGenerationTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueBase.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    public static function slugIsGeneratedFromPageTitleDataProvider(): array
    {
        return [
            'plain title' => ['Page Three', '/page-three'],
            'slash surrounded by spaces' => ['foo / bar', '/foo-bar'],
            'slash without spaces' => ['foo/bar', '/foo-bar'],
            'multiple slashes' => ['foo // bar', '/foo-bar'],
            'leading slash' => ['/ foo bar', '/foo-bar'],
            'trailing slash' => ['foo bar /', '/foo-bar'],
        ];
    }

    #[DataProvider('slugIsGeneratedFromPageTitleDataProvider')]
    #[Test]
    public function slugIsGeneratedFromPageTitle(string $title, string $expectedSlug): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'pages' => [
                    'NEW-1' => [
                        'pid' => 1,
                        'title' => $title,
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);
        $newPageId = $dataHandler->substNEWwithIDs['NEW-1'];

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $slug = $queryBuilder
            ->select('slug')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($newPageId, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        self::assertSame($expectedSlug, $slug);
    }
}
