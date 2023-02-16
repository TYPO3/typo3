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

use TYPO3\CMS\Core\TypoScript\UserTsConfigFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests UserTsConfigFactory and indirectly IncludeTree/TsConfigTreeBuilder
 */
class UserTsConfigFactoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_typoscript_usertsconfigfactory',
    ];

    /**
     * @test
     */
    public function userTsConfigLoadsDefaultFromGlobals(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] = 'loadedFromGlobals = loadedFromGlobals';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(1);
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
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('loadedFromUserGroupOverride', $userTsConfig->getUserTsConfigArray()['loadedFromUserGroup']);
    }

    /**
     * @test
     */
    public function userTsConfigLoadsFromWildcardAtImportWithTsconfigSuffix(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(7);
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('loadedFromTsconfigIncludesWithTsconfigSuffix', $userTsConfig->getUserTsConfigArray()['loadedFromTsconfigIncludesWithTsconfigSuffix']);
    }

    /**
     * @test
     */
    public function userTsConfigLoadsFromWildcardAtImportWithTypoScriptSuffix(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(8);
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('loadedFromTsconfigIncludesWithTyposcriptSuffix', $userTsConfig->getUserTsConfigArray()['loadedFromTsconfigIncludesWithTyposcriptSuffix']);
    }
}
