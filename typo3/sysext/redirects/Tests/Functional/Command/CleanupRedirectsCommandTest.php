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

namespace TYPO3\CMS\Redirects\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Redirects\Command\CleanupRedirectsCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CleanupRedirectsCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(dirname(__DIR__) . '/Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function redirectsAreRemovedRegardlessOfTheirVisibility(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');

        $tester = new CommandTester($this->get(CleanupRedirectsCommand::class));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/sys_redirect_after_cleanup.csv');
    }

    #[Test]
    public function dryRunKeepsRedirectsAndListsThoseWhichWouldBeRemoved(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');

        $tester = new CommandTester($this->get(CleanupRedirectsCommand::class));
        $tester->execute(['--dry-run' => true]);

        $tester->assertCommandIsSuccessful();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');

        $output = $tester->getDisplay();
        self::assertStringContainsString('/outdated', $output);
        self::assertStringContainsString('/disabled', $output);
        self::assertStringContainsString('/soft-deleted', $output);
        self::assertStringContainsString('/expired', $output);
        self::assertStringNotContainsString('/protected', $output);
        self::assertStringNotContainsString('/recent', $output);
        self::assertStringContainsString('4 redirects would be deleted.', $output);
    }

    #[Test]
    public function dryRunUsesSingularForASingleRedirect(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');

        $tester = new CommandTester($this->get(CleanupRedirectsCommand::class));
        $tester->execute(['--path' => '/outdated', '--dry-run' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('1 redirect would be deleted.', $tester->getDisplay());
    }

    #[Test]
    public function dryRunInformsAboutAnEmptyResult(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');

        $tester = new CommandTester($this->get(CleanupRedirectsCommand::class));
        $tester->execute(['--domain' => ['unknown.example.com'], '--dry-run' => true]);

        $tester->assertCommandIsSuccessful();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');
        self::assertStringContainsString('nothing would be deleted', $tester->getDisplay());
    }
}
