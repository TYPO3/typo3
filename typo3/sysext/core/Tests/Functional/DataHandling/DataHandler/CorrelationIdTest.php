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
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests that all record history entries of one logical DataHandler
 * operation share the correlation id of the outermost instance, even
 * when parts of the operation are processed by nested instances.
 */
final class CorrelationIdTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/CorrelationId/PagesWithContent.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function historyEntriesOfNestedOperationsShareCorrelationIdScopeOfOuterMostInstance(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $cmd['pages'][2]['copy'] = 1;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        $outerScope = $dataHandler->getCorrelationId()->getScope();
        $correlationIds = $this->getConnectionPool()->getConnectionForTable('sys_history')
            ->select(['correlation_id'], 'sys_history')
            ->fetchFirstColumn();
        // Copied page and both content elements are inserted through nested instances
        self::assertGreaterThanOrEqual(3, count($correlationIds));
        foreach ($correlationIds as $correlationId) {
            self::assertSame($outerScope, CorrelationId::fromString($correlationId)->getScope());
        }
    }
}
