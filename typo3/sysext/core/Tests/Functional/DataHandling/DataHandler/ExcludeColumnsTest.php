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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests exclude handling by
 * - HIDE_FOR_NON_ADMINS displayCondition
 * - TCA's exclude option
 */
final class ExcludeColumnsTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_exclude.csv');
    }

    #[Test]
    #[DataProvider('excludeHideForNonAdminDataProvider')]
    public function excludeHideForNonAdmins(array $input, string $expected): void
    {
        $backendUser = $this->setUpBackendUser($input['user']);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        unset($GLOBALS['TCA']['pages']['columns']['nav_title']['exclude']);
        $GLOBALS['TCA']['pages']['columns']['nav_title']['displayCond'] = $input['displayCondition'];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $datamap['pages']['NEW1234'] = [
            'nav_title' => 'A new page',
            'pid' => 1,
            'sys_language_uid' => 0,
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();
        $uid = $dataHandler->substNEWwithIDs['NEW1234'];
        $newPageRecord = BackendUtility::getRecord('pages', $uid);
        self::assertEquals($expected, $newPageRecord['nav_title'] ?? '');
    }

    public static function excludeHideForNonAdminDataProvider(): \Generator
    {
        yield 'is admin and displayCondition hideForNonAdmins' => [
            [
                'user' => 1,
                'displayCondition' => 'HIDE_FOR_NON_ADMINS',
            ],
            'A new page',
        ];
        yield 'is admin and no displayCondition hideForNonAdmins' => [
            [
                'user' => 1,
                'displayCondition' => '',
            ],
            'A new page',
        ];
        yield 'no admin and displayCondition hideForNonAdmins' => [
            [
                'user' => 2,
                'displayCondition' => 'HIDE_FOR_NON_ADMINS',
            ],
            '',
        ];
        yield 'no admin and no displayCondition hideForNonAdmins' => [
            [
                'user' => 2,
                'displayCondition' => '',
            ],
            'A new page',
        ];
    }

    #[Test]
    #[DataProvider('excludeByAccessControlDataProvider')]
    public function excludeByAccessControl(array $input, string $expected): void
    {
        $backendUser = $this->setUpBackendUser($input['user']);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        unset($GLOBALS['TCA']['pages']['columns']['nav_title']['displayCond']);
        $GLOBALS['TCA']['pages']['columns']['nav_title']['exclude'] = $input['exclude'];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        // Create page and verify default from TCA is applied
        $datamap['pages']['NEW1234'] = [
            'nav_title' => 'A new page',
            'pid' => 1,
            'sys_language_uid' => 0,
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();
        $uid = $dataHandler->substNEWwithIDs['NEW1234'];
        $newPageRecord = BackendUtility::getRecord('pages', $uid);
        self::assertEquals($expected, $newPageRecord['nav_title'] ?? '');
    }

    public static function excludeByAccessControlDataProvider(): \Generator
    {
        yield 'is admin and field with access control' => [
            [
                'user' => 1,
                'exclude' => true,
            ],
            'A new page',
        ];
        yield 'is admin and field without access control' => [
            [
                'user' => 1,
                'exclude' => false,
            ],
            'A new page',
        ];
        yield 'no admin and field without access control' => [
            [
                'user' => 2,
                'exclude' => false,
            ],
            'A new page',
        ];
        yield 'no admin and field with access control and permission' => [
            [
                'user' => 3,
                'exclude' => true,
            ],
            'A new page',
        ];
        yield 'no admin and field with access control and without permission' => [
            [
                'user' => 2,
                'exclude' => true,
            ],
            '',
        ];
    }
}
