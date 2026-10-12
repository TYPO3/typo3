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

namespace TYPO3\CMS\Core\Tests\Unit\Error;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Error\AbstractExceptionHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractExceptionHandlerTest extends UnitTestCase
{
    #[Test]
    public function writeLogEntriesDoesNotLogInvalidRequestExceptions(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('critical');

        $exceptionHandler = new class extends AbstractExceptionHandler {
            public function __construct() {}

            public function logException(\Throwable $exception): void
            {
                $this->writeLogEntries($exception, self::CONTEXT_WEB);
            }

            public function echoExceptionWeb(\Throwable $exception): void {}

            public function echoExceptionCLI(\Throwable $exception): void {}
        };
        $exceptionHandler->setLogger($logger);

        $exceptionHandler->logException(new \RuntimeException('Invalid form page index', 1791791661));
    }
}
