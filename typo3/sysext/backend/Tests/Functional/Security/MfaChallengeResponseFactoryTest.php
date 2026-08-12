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

namespace TYPO3\CMS\Backend\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Security\MfaChallengeResponseFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\Challenge;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MfaChallengeResponseFactoryTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_mfa.csv');
    }

    #[Test]
    public function userWithAnActiveProviderIsChallenged(): void
    {
        $user = $this->createUserWithSession(4);
        $subject = $this->get(MfaChallengeResponseFactory::class);

        self::assertTrue($subject->isMfaPending($user));
        $challenge = $subject->createChallengeIfRequired($user, $this->createAjaxRequest());
        self::assertInstanceOf(Challenge::class, $challenge);
        self::assertSame(401, $challenge->response->getStatusCode());
    }

    #[Test]
    public function userWhoPassedMfaInTheSessionIsNotChallenged(): void
    {
        $user = $this->createUserWithSession(4);
        $user->setSessionData('mfa', true);
        $subject = $this->get(MfaChallengeResponseFactory::class);

        self::assertFalse($subject->isMfaPending($user));
        self::assertNull($subject->createChallengeIfRequired($user, $this->createAjaxRequest()));
    }

    #[Test]
    public function userSwitchedToByAnAdminIsNotChallenged(): void
    {
        $user = $this->createUserWithSession(4);
        $user->setSessionData('backuserid', 1);
        $subject = $this->get(MfaChallengeResponseFactory::class);

        self::assertFalse($subject->isMfaPending($user));
        self::assertNull($subject->createChallengeIfRequired($user, $this->createAjaxRequest()));
    }

    private function createUserWithSession(int $userId): BackendUserAuthentication
    {
        $userRecord = $this->getBackendUserRecordFromDatabase($userId);
        $user = new BackendUserAuthentication();
        $user->initializeFromPipeline($user->createUserSession($userRecord), $userRecord);
        return $user;
    }

    private function createAjaxRequest(): ServerRequest
    {
        $route = new Route('/ajax/test', ['ajax' => true, '_identifier' => 'ajax_test']);
        return new ServerRequest('https://typo3-testing.local/typo3/ajax/test')->withAttribute('route', $route);
    }
}
