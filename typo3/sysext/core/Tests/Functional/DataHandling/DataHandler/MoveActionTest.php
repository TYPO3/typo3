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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MoveActionTest extends FunctionalTestCase
{
    private BackendUserAuthentication $backendUser;
    private DataHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/DataSet/MoveRecord.csv');

        $this->backendUser = $this->setUpBackendUser(9);
        $this->backendUser->setWebmounts([1]);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        $this->subject = $this->get(DataHandler::class);
        $this->subject->start([], []);
    }

    #[Test]
    public function moveRecordOfAccessibleRecordIsPermitted(): void
    {
        $recordUid = 1;
        $targetPid = 3;
        $this->subject->moveRecord('tt_content', $recordUid, $targetPid);
        self::assertEmpty($this->subject->errorLog);
    }

    #[Test]
    public function moveRecordOfNonAccessibleRecordIsDenied(): void
    {
        $recordUid = 2;
        $sourcePid = 5;
        $targetPid = 2;
        $this->subject->moveRecord('tt_content', $recordUid, $targetPid);
        self::assertStringContainsString(
            sprintf(
                'Attempt to move record tt_content:%d to pid "%d" without having permissions to update the source page (uid=%d)',
                $recordUid,
                $targetPid,
                $sourcePid,
            ),
            $this->subject->errorLog[0]
        );
    }
}
