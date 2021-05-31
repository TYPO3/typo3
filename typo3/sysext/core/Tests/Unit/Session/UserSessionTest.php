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

namespace TYPO3\CMS\Core\Tests\Unit\Session;

use TYPO3\CMS\Core\Security\JwtTrait;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UserSessionTest extends UnitTestCase
{
    use JwtTrait;

    /**
     * @test
     */
    public function createFromRecordTest(): void
    {
        $record = [
            'ses_id' => '12345abcdf',
            'ses_data' => serialize(['some' => 'data', 'backuserid' => 1]),
            'ses_userid' => 0,
            'ses_iplock' => '[DISABLED]',
            'ses_tstamp' => 1607041477,
            'ses_permanent' => 1,
        ];

        $session = UserSession::createFromRecord($record['ses_id'], $record, true);

        self::assertEquals($record['ses_id'], $session->getIdentifier());
        self::assertNull($session->getUserId());
        self::assertTrue($session->isAnonymous());
        self::assertEquals($record['ses_tstamp'], $session->getLastUpdated());
        self::assertEquals($record['ses_iplock'], $session->getIpLock());
        self::assertTrue($session->isNew());
        self::assertTrue($session->isPermanent());
        self::assertTrue($session->needsUpdate());
        self::assertEquals($record, $session->toArray());

        $session->set('new', 'value');

        self::assertTrue($session->hasData());
        self::assertEquals(1, $session->get('backuserid'));
        self::assertEquals(['some' => 'data', 'backuserid' => 1, 'new' => 'value'], $session->getData());

        $session->overrideData(['override' => 'data']);

        self::assertTrue($session->dataWasUpdated());
        self::assertEquals(['override' => 'data'], $session->getData());
        self::assertSame($record['ses_id'], UserSession::resolveIdentifierFromJwt($session->getJwt()));
    }

    /**
     * @test
     */
    public function createNonFixated(): void
    {
        $session = UserSession::createNonFixated('fdcba54321');

        self::assertEquals('fdcba54321', $session->getIdentifier());
        self::assertEmpty($session->getIpLock());
        self::assertEquals($GLOBALS['EXEC_TIME'], $session->getLastUpdated());
        self::assertNull($session->getUserId());
        self::assertFalse($session->hasData());
        self::assertFalse($session->isPermanent());
        self::assertFalse($session->needsUpdate());
        self::assertFalse($session->dataWasUpdated());
        self::assertTrue($session->isNew());
        self::assertTrue($session->isAnonymous());
        self::assertEquals(
            [
                'ses_id' => 'fdcba54321',
                'ses_data' => 'a:0:{}',
                'ses_userid' => 0,
                'ses_iplock' => '',
                'ses_tstamp' => $GLOBALS['EXEC_TIME'],
            ],
            $session->toArray()
        );
    }
}
