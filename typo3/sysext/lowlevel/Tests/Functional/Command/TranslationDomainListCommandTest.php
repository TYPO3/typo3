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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Lowlevel\Command\TranslationDomainListCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TranslationDomainListCommandTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'lowlevel',
    ];

    #[Test]
    public function jsonOutputListsDomainsWithLabelCount(): void
    {
        $tester = new CommandTester($this->get(TranslationDomainListCommand::class));
        $tester->execute(['--extension' => 'lowlevel', '--json' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $data = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        self::assertNotEmpty($data);
        foreach ($data as $entry) {
            self::assertSame(['domain', 'resource', 'labelCount', 'deprecated', 'overrides'], array_keys($entry));
            self::assertIsInt($entry['labelCount']);
            self::assertGreaterThan(0, $entry['labelCount']);
        }
    }

    #[Test]
    public function jsonOutputFailsForUnknownExtension(): void
    {
        $tester = new CommandTester($this->get(TranslationDomainListCommand::class));
        $tester->execute(['--extension' => 'does_not_exist', '--json' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    #[Test]
    public function tableOutputStaysDefault(): void
    {
        $tester = new CommandTester($this->get(TranslationDomainListCommand::class));
        $tester->execute(['--extension' => 'lowlevel']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('# Labels', $tester->getDisplay());
    }
}
