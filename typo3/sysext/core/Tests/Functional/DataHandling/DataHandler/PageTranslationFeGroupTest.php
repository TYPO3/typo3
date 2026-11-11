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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTranslationFeGroupTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
    ];

    #[Test]
    public function feGroupOfDefaultPageIsSynchronizedToTranslation(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => [1 => ['fe_group' => '-2']]], []);
        $dataHandler->process_datamap();

        self::assertSame('-2', (string)$this->getConnectionPool()->getConnectionForTable('pages')
            ->select(['fe_group'], 'pages', ['uid' => 2])
            ->fetchOne());
    }

    #[Test]
    public function feGroupOfTranslationCannotDeviateFromDefaultPage(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => [2 => ['fe_group' => '-2']]], []);
        $dataHandler->process_datamap();

        self::assertSame('', (string)$this->getConnectionPool()->getConnectionForTable('pages')
            ->select(['fe_group'], 'pages', ['uid' => 2])
            ->fetchOne());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/PageTranslationFeGroup/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ],
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }
}
