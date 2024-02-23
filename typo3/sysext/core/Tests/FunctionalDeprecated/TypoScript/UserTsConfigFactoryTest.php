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

namespace TYPO3\CMS\Core\Tests\FunctionalDeprecated\TypoScript;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\TypoScript\UserTsConfigFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UserTsConfigFactoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_typoscript_usertsconfigfactory',
    ];

    #[Test]
    public function userTsConfigLoadsDefaultFromGlobals(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] = 'loadedFromGlobals = loadedFromGlobals';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(1);
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('loadedFromGlobals', $userTsConfig->getUserTsConfigArray()['loadedFromGlobals']);
    }

    #[Test]
    public function userTsConfigLoadsSingleFileWithOldImportSyntaxFromGlobals(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] = '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:test_typoscript_usertsconfigfactory/Configuration/TsConfig/tsconfig-includes.tsconfig">';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/userTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(1);
        /** @var UserTsConfigFactory $subject */
        $subject = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $subject->create($backendUser);
        self::assertSame('loadedFromTsconfigIncludesWithTsconfigSuffix', $userTsConfig->getUserTsConfigArray()['loadedFromTsconfigIncludesWithTsconfigSuffix']);
    }
}
