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

namespace TYPO3\CMS\Core\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Command\SiteListCommand;
use TYPO3\CMS\Core\Command\SiteValidateCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteValidateCommandTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Configuration/Fixtures/InvalidSites' => 'typo3conf/sites',
    ];

    protected function setUp(): void
    {
        putenv('TYPO3_TESTING_INVALID_SITES_EMPTY_HOST=');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('TYPO3_TESTING_INVALID_SITES_EMPTY_HOST');
        parent::tearDown();
    }

    #[Test]
    public function validatingAllSitesFailsAndReportsInvalidSitesOnly(): void
    {
        $tester = new CommandTester($this->get(SiteValidateCommand::class));
        $tester->execute([]);
        $output = $tester->getDisplay();
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Site "invalid-language-base"', $output);
        self::assertStringContainsString('languages.1.base', $output);
        self::assertStringContainsString('Site "broken-yaml"', $output);
        self::assertStringContainsString('8 site configuration errors were found.', $output);
        self::assertStringNotContainsString('Site "valid"', $output);
    }

    #[Test]
    public function validatingAnInvalidSiteFailsAndReportsThisSiteOnly(): void
    {
        $tester = new CommandTester($this->get(SiteValidateCommand::class));
        $tester->execute(['identifier' => 'invalid-language-base']);
        $output = $tester->getDisplay();
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Site "invalid-language-base"', $output);
        self::assertStringContainsString('The parsedUri "https://" appears to be malformed', $output);
        self::assertStringNotContainsString('Site "invalid-base"', $output);
    }

    #[Test]
    public function validatingAValidSiteSucceeds(): void
    {
        $tester = new CommandTester($this->get(SiteValidateCommand::class));
        $tester->execute(['identifier' => 'valid']);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Site "valid" is valid.', $tester->getDisplay());
    }

    #[Test]
    public function validatingAnUnknownSiteFails(): void
    {
        $tester = new CommandTester($this->get(SiteValidateCommand::class));
        $tester->execute(['identifier' => 'unknown']);
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Site "unknown" does not exist.', $tester->getDisplay());
    }

    #[Test]
    public function listingSitesSucceedsAndWarnsAboutInvalidSites(): void
    {
        $tester = new CommandTester($this->get(SiteListCommand::class));
        $tester->execute([]);
        $output = $tester->getDisplay();
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('https://valid.example.com/', $output);
        self::assertStringContainsString('invalid-language-base', $output);
        self::assertStringContainsString('site:validate', $output);
    }
}
