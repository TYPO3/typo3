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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\UserTsConfigFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests UserTsConfigFactory and indirectly IncludeTree/TsConfigTreeBuilder
 */
class UserTsConfigFactoryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function userTsConfigLoadsDefaultFromGlobals(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] = 'loadedFromGlobals = loadedFromGlobals';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(1);
        /** @var UserTsConfigFactory $subject */
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('loadedFromGlobals', $userTsConfig->getUserTsConfigArray()['loadedFromGlobals']);
    }

    /**
     * @test
     */
    public function userTsConfigLoadsDefaultFromBackendUserTsConfigField(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(2);
        /** @var UserTsConfigFactory $subject */
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('loadedFromUser', $userTsConfig->getUserTsConfigArray()['loadedFromUser']);
    }

    /**
     * @test
     */
    public function userTsConfigLoadsDefaultFromBackendUserGroupTsConfigField(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(3);
        /** @var UserTsConfigFactory $subject */
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('loadedFromUserGroup', $userTsConfig->getUserTsConfigArray()['loadedFromUserGroup']);
    }

    /**
     * @test
     */
    public function userTsConfigLoadsDefaultFromBackendUserGroupTsConfigFieldAndGroupOverride(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(4);
        /** @var UserTsConfigFactory $subject */
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('loadedFromUserGroupOverride', $userTsConfig->getUserTsConfigArray()['loadedFromUserGroup']);
    }

    /**
     * @test
     */
    public function userTsConfigMatchesRequestHttpsCondition(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $userRow = $this->getBackendUserRecordFromDatabase(5);
        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $request = (new ServerRequest('https://www.example.com/', null, 'php://input', [], ['HTTPS' => 'ON']));
        $session = $backendUser->createUserSession($userRow);
        $request = $request->withCookieParams(['be_typo_user' => $session->getJwt()]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $backendUser = $this->authenticateBackendUser($backendUser, $request);
        /** @var UserTsConfigFactory $subject */
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('on', $userTsConfig->getUserTsConfigArray()['isHttps']);
    }
}
