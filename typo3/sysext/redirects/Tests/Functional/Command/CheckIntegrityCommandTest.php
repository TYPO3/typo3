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
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Redirects\Command\CheckIntegrityCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CheckIntegrityCommandTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'unused' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['redirects'];

    #[Test]
    public function targetColumnShowsRedirectTargetForPageConflict(): void
    {
        $this->writeSiteConfiguration('simple-page', $this->buildSiteConfiguration(1, 'https://example.com'));
        $this->importCSVDataSet(__DIR__ . '/../Service/Fixtures/SimplePages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Service/Fixtures/sys_redirect.csv');

        $tester = new CommandTester($this->get(CheckIntegrityCommand::class));
        $tester->execute(['site' => 'simple-page']);

        self::assertMatchesRegularExpression(
            '~\|\s*7\s*\|\s*example\.com\s*\|\s*/\s*\|\s*/home\s*\|~',
            $tester->getDisplay(),
        );
    }
}
