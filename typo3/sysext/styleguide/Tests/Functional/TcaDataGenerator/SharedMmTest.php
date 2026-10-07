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
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SharedMmTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['styleguide'];

    #[Test]
    public function sharedRelationsKeepFieldDiscriminatorsWhenEditedFromEitherSide(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SharedMm.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $actionService = new ActionService();

        $actionService->modifyRecord('tx_styleguide_elements_select', 1, [
            'shared_mm_primary' => '1',
            'shared_mm_secondary' => '1',
        ]);
        self::assertSame([], $actionService->getDataHandler()->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/SharedMmBothFields.csv');

        $actionService->modifyRecord('tx_styleguide_elements_select', 1, ['shared_mm_secondary' => '']);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tx_styleguide_staticdata_shared_mm');
        self::assertSame(1, $connection->count('*', 'tx_styleguide_staticdata_shared_mm', ['fieldname' => 'shared_mm_primary']));
        self::assertSame(0, $connection->count('*', 'tx_styleguide_staticdata_shared_mm', ['fieldname' => 'shared_mm_secondary']));

        $actionService->modifyRecord('tx_styleguide_elements_select', 2, ['shared_mm_secondary' => '1']);
        $actionService->modifyRecord('tx_styleguide_staticdata', 1, ['shared_mm_primary_records' => '']);
        $actionService->modifyRecord('tx_styleguide_staticdata', 1, ['shared_mm_secondary_records' => '2,1']);
        self::assertSame([], $actionService->getDataHandler()->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/SharedMmReverse.csv');
    }
}
