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

namespace TYPO3\CMS\Scheduler\Tests\Unit\Validation\Validator;

use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Validation\Validator\TaskValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TaskValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function isValidReturnsFalseWithClassNotExtendingAbstractTask(): void
    {
        $subject = new TaskValidator();
        self::assertFalse($subject->isValid($subject));
    }

    /**
     * @test
     */
    public function isValidReturnsFalseWithClassNotProperlyImplementingGetExecution(): void
    {
        $subject = new TaskValidator();
        $input = new class () extends AbstractTask {
            public function __construct()
            {
            }
            public function execute()
            {
                return false;
            }
        };
        self::assertFalse($subject->isValid($input));
    }

    /**
     * @test
     */
    public function isValidReturnsTrueWithClassProperlyImplementingAbstractTask(): void
    {
        $subject = new TaskValidator();
        $input = new class () extends AbstractTask {
            public function __construct()
            {
            }
            public function execute()
            {
                return false;
            }
            public function getExecution()
            {
                return new Execution();
            }
        };
        self::assertTrue($subject->isValid($input));
    }
}
