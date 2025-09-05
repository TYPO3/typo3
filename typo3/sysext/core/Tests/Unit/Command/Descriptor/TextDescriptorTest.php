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

namespace TYPO3\CMS\Core\Tests\Unit\Command\Descriptor;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Command\Descriptor\TextDescriptor;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TextDescriptorTest extends UnitTestCase
{
    #[Test]
    public function ensureEmptyStringIsSetForDescriptionIfNoDescriptionForCommandIsGiven(): void
    {
        $commandRegistry = $this->createMock(CommandRegistry::class);
        $commandRegistry
            ->method('filter')
            ->willReturn([
                'command1' => [
                    'name' => 'command1',
                    'description' => 'description1',
                ],
                'command2' => [
                    'name' => 'command2',
                ],
            ]);

        $output = $this->createMock(OutputInterface::class);

        $matcher = $this->exactly(2);
        $output
            ->expects($matcher)
            ->method('write')
            ->willReturnCallback(function (string $description) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => self::assertEquals("command1   description1\n", $description),
                    2 => self::assertEquals("command2   \n", $description),
                    default => self::fail('Unexpected number of invocations')
                };
            });

        $subject = $this->getAccessibleMock(TextDescriptor::class, null, [$commandRegistry, false]);
        $subject->_set('output', $output);
        $subject->_call(
            'describeApplication',
            $this->createMock(Application::class),
            ['raw_text' => true]
        );
    }
}
