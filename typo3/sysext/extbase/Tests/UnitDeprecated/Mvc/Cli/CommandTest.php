<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Mvc\Cli;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\Command;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\Mvc\Cli\Fixture\Command\MockCCommandController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CommandTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    public function testIsCliOnly()
    {
        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'empty'
        );

        static::assertFalse($commandController->isCliOnly());

        $commandController = GeneralUtility::makeInstance(ObjectManager::class)->get(
            Command::class,
            MockCCommandController::class,
            'cliOnly'
        );

        static::assertTrue($commandController->isCliOnly());
    }
}
