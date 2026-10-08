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

namespace TYPO3\CMS\Reactions\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResetLastCallOnCopyTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['reactions'];

    #[Test]
    public function copyOfAReactionHasNotBeenCalledYet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_reaction');
        $connection->update('sys_reaction', ['last_called' => 1699963200, 'last_status' => 400, 'last_failure' => 'No fields given.'], ['uid' => 1]);
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['sys_reaction' => [1 => ['copy' => 0]]]);
        $dataHandler->process_cmdmap();

        $copyUid = (int)($dataHandler->copyMappingArray_merged['sys_reaction'][1] ?? 0);
        self::assertGreaterThan(0, $copyUid);
        $copy = $connection->select(['last_called', 'last_status', 'last_failure'], 'sys_reaction', ['uid' => $copyUid])->fetchAssociative();
        self::assertSame(0, (int)$copy['last_called']);
        self::assertSame(0, (int)$copy['last_status']);
        self::assertSame('', (string)$copy['last_failure']);
        $original = $connection->select(['last_called', 'last_status', 'last_failure'], 'sys_reaction', ['uid' => 1])->fetchAssociative();
        self::assertSame(1699963200, (int)$original['last_called']);
        self::assertSame(400, (int)$original['last_status']);
        self::assertSame('No fields given.', $original['last_failure']);
    }
}
