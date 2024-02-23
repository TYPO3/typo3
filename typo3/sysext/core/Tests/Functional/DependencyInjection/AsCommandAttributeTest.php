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

namespace TYPO3\CMS\Core\Tests\Functional\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestDi\Command\AliasTestCommand;
use TYPO3Tests\TestDi\Command\HiddenTestCommand;
use TYPO3Tests\TestDi\Command\VisibleTestCommand;

final class AsCommandAttributeTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_di',
    ];

    #[Test]
    public function asCommandRegisteredToCommandRegistry(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);

        self::assertTrue($commandRegistry->has('testdi:ascommand:visible'));
        self::assertInstanceOf(VisibleTestCommand::class, $commandRegistry->get('testdi:ascommand:visible'));

        self::assertTrue($commandRegistry->has('testdi:ascommand:hidden'));
        self::assertInstanceOf(HiddenTestCommand::class, $commandRegistry->get('testdi:ascommand:hidden'));
    }

    #[Test]
    public function asCommandHiddenAttributeIsRespected(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);
        $visibleList = $commandRegistry->filter();

        self::assertArrayHasKey('testdi:ascommand:visible', $visibleList);
        self::assertArrayNotHasKey('testdi:ascommand:hidden', $visibleList);
    }

    #[Test]
    public function asCommandAliasAttributeIsRespected(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);
        $visibleList = $commandRegistry->filter();

        $aliasCommand = $commandRegistry->get('testdi:ascommand:alias-main');
        $aliasSub1Command = $commandRegistry->get('testdi:ascommand:alias-sub1');
        $aliasSub2Command = $commandRegistry->get('testdi:ascommand:alias-sub2');

        self::assertInstanceOf(AliasTestCommand::class, $aliasCommand);
        self::assertInstanceOf(AliasTestCommand::class, $aliasSub1Command);
        self::assertInstanceOf(AliasTestCommand::class, $aliasSub2Command);

        self::assertArrayHasKey('testdi:ascommand:alias-main', $visibleList);
        self::assertArrayNotHasKey('testdi:ascommand:alias-sub1', $visibleList);
        self::assertArrayNotHasKey('testdi:ascommand:alias-sub2', $visibleList);
    }

    #[Test]
    public function asCommandSetsDescription(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);

        $visibleCommand = $commandRegistry->get('testdi:ascommand:visible');
        $hiddenCommand = $commandRegistry->get('testdi:ascommand:hidden');

        self::assertEquals('This is a visible command.', $visibleCommand->getDescription());
        self::assertEquals('This is a hidden command.', $hiddenCommand->getDescription());
    }
}
