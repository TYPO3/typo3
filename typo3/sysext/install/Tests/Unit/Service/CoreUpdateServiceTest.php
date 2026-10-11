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

namespace TYPO3\CMS\Install\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithEnvironmentVariable;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Install\Service\CoreUpdateService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
#[WithEnvironmentVariable('TYPO3_DISABLE_CORE_UPDATER')]
#[WithEnvironmentVariable('REDIRECT_TYPO3_DISABLE_CORE_UPDATER')]
final class CoreUpdateServiceTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    #[Test]
    public function getMessagesReturnsPreviouslySetMessage(): void
    {
        $instance = $this->getAccessibleMock(CoreUpdateService::class, null, [], '', false);
        $aMessage = new FlashMessageQueue('install');
        $instance->_set('messages', $aMessage);
        self::assertSame($aMessage, $instance->getMessages());
    }

    public static function coreUpdateEnvironmentDataProvider(): \Generator
    {
        yield 'non-Composer mode without flags' => [false, null, true];
        yield 'non-Composer mode with direct flag' => [false, 'TYPO3_DISABLE_CORE_UPDATER', false];
        yield 'non-Composer mode with redirected flag' => [false, 'REDIRECT_TYPO3_DISABLE_CORE_UPDATER', false];
        yield 'Composer mode without flags' => [true, null, false];
    }

    #[Test]
    #[DataProvider('coreUpdateEnvironmentDataProvider')]
    public function isCoreUpdateEnabledRespectsComposerModeAndEnvironmentFlags(bool $composerMode, ?string $disableVariable, bool $expected): void
    {
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            $composerMode,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        if ($disableVariable !== null) {
            putenv($disableVariable . '=1');
        }
        $instance = $this->getAccessibleMock(CoreUpdateService::class, null, [], '', false);
        self::assertSame($expected, $instance->isCoreUpdateEnabled());
    }
}
