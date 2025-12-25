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

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SwitchDoktypeTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataHandler/DataSet/SwitchDoktype/base.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function switchingDoktypeAllowedWhenStateAfterIsValidDeprecated(): void
    {
        $pageDoktypeRegistry = $this->get(PageDoktypeRegistry::class);
        $pageDoktypeRegistry->add(1, ['allowedTables' => 'pages, sys_category']);
        $actionService = new ActionService();
        $actionService->modifyRecord(
            'pages',
            1,
            [
                'doktype' => 1,
            ]
        );
        self::assertCSVDataSet(__DIR__ . '/../DataHandler/DataSet/SwitchDoktype/doktypeSwitched.csv');
    }

    #[Test]
    #[IgnoreDeprecations]
    public function switchingDoktypeAllowedWhenStateAfterIsValidDeprecatedAddAllowedRecords(): void
    {
        $pageDoktypeRegistry = $this->get(PageDoktypeRegistry::class);
        $pageDoktypeRegistry->add(1, ['allowedTables' => 'pages']);
        $pageDoktypeRegistry->addAllowedRecordTypes(['sys_category'], 1);
        $actionService = new ActionService();
        $actionService->modifyRecord(
            'pages',
            1,
            [
                'doktype' => 1,
            ]
        );
        self::assertCSVDataSet(__DIR__ . '/../DataHandler/DataSet/SwitchDoktype/doktypeSwitched.csv');
    }

    #[Test]
    public function switchingDoktypeAllowedWhenStateAfterIsValid(): void
    {
        $GLOBALS['TCA']['pages']['types']['1']['allowedRecordTypes'] = ['pages', 'sys_category'];
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->rebuild($GLOBALS['TCA']);
        $actionService = new ActionService();
        $actionService->modifyRecord(
            'pages',
            1,
            [
                'doktype' => 1,
            ]
        );
        self::assertCSVDataSet(__DIR__ . '/../DataHandler/DataSet/SwitchDoktype/doktypeSwitched.csv');
    }

    #[Test]
    public function switchingDoktypeNotAllowedIfAllowedRecordTypesAreViolated(): void
    {
        $GLOBALS['TCA']['pages']['types']['1']['allowedRecordTypes'] = ['pages'];
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->rebuild($GLOBALS['TCA']);
        $actionService = new ActionService();
        $actionService->modifyRecord(
            'pages',
            1,
            [
                'doktype' => 1,
            ]
        );
        self::assertCSVDataSet(__DIR__ . '/../DataHandler/DataSet/SwitchDoktype/base.csv');
    }
}
