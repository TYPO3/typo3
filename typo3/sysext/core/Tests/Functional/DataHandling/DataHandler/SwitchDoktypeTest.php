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
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
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
    public function switchingDoktypeAllowedWhenOnlyAllowedTablesFalse(): void
    {
        $pageDoktypeRegistry = $this->get(PageDoktypeRegistry::class);
        $pageDoktypeRegistry->add(1, ['allowedTables' => 'pages', 'onlyAllowedTables' => false]);
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
    public function switchingDoktypeChecksForAllowedRecordsOnPageWhenOnlyAllowedTablesTrue(): void
    {
        $pageDoktypeRegistry = $this->get(PageDoktypeRegistry::class);
        $pageDoktypeRegistry->add(1, ['allowedTables' => 'pages', 'onlyAllowedTables' => true]);
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
