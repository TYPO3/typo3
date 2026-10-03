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

namespace TYPO3\CMS\Styleguide\Tests\Functional\TcaDataGenerator;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Styleguide\TcaDataGenerator\GeneratorFrontend;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class GeneratorFrontendTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
        'felogin',
        'form',
        'indexed_search',
        'seo',
        'styleguide',
    ];

    #[Test]
    public function createGeneratesFrontendDemoData(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->get(GeneratorFrontend::class)->create('', 1, true);

        $connectionPool = $this->get(ConnectionPool::class);

        $rootPageUids = $this->get(RecordFinder::class)->findUidsOfFrontendPages(['tx_styleguide_frontend_root']);
        self::assertCount(1, $rootPageUids);
        $site = $this->get(SiteFinder::class)->getSiteByRootPageId((int)$rootPageUids[0]);
        self::assertSame(['typo3/styleguide'], $site->getSets());

        // Every content element type has a page, plus the folders and the restrictions page
        self::assertGreaterThan(
            25,
            $connectionPool->getConnectionForTable('pages')->count('*', 'pages', ['tx_styleguide_containsdemo' => 'tx_styleguide_frontend'])
        );

        // Each image, textmedia and uploads element got the three demo files attached
        $textmedia = $connectionPool->getConnectionForTable('tt_content')
            ->select(['uid'], 'tt_content', ['CType' => 'textmedia', 'tx_styleguide_containsdemo' => 'tx_styleguide_frontend'])
            ->fetchAllAssociative();
        self::assertNotEmpty($textmedia);
        foreach ($textmedia as $row) {
            self::assertSame(
                3,
                $connectionPool->getConnectionForTable('sys_file_reference')->count('*', 'sys_file_reference', [
                    'tablenames' => 'tt_content',
                    'fieldname' => 'assets',
                    'uid_foreign' => (int)$row['uid'],
                ])
            );
        }

        // Menu elements point to frontend pages, shortcut elements to a text element
        $menuPages = $connectionPool->getConnectionForTable('tt_content')
            ->select(['pages'], 'tt_content', ['CType' => 'menu_pages'])
            ->fetchOne();
        self::assertCount(5, explode(',', (string)$menuPages));
        $shortcutRecords = $connectionPool->getConnectionForTable('tt_content')
            ->select(['records'], 'tt_content', ['CType' => 'shortcut'])
            ->fetchOne();
        self::assertNotEmpty($shortcutRecords);

        // The frontend user is assigned to the demo group and the login element uses the user folder
        $feUser = $connectionPool->getConnectionForTable('fe_users')
            ->select(['usergroup'], 'fe_users', ['username' => 'styleguide-frontend-demo'])
            ->fetchAssociative();
        self::assertIsArray($feUser);
        self::assertNotSame('', (string)$feUser['usergroup']);
        $loginFlexForm = $connectionPool->getConnectionForTable('tt_content')
            ->select(['pi_flexform'], 'tt_content', ['CType' => 'felogin_login'])
            ->fetchOne();
        self::assertStringContainsString('settings.pages', (string)$loginFlexForm);
    }
}
