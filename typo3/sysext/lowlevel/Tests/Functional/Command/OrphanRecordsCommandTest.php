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
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Lowlevel\Command\OrphanRecordsCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class OrphanRecordsCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'lowlevel',
    ];

    #[Test]
    public function invalidRecordsAreDeleted(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OrphanRecordsCommandImport.csv');
        $tester = new CommandTester($this->get(OrphanRecordsCommand::class));
        $tester->execute([]);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/OrphanRecordsCommandResult.csv');
    }
}
