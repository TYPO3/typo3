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

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Error\ErrorHandler;
use TYPO3\CMS\Core\Error\ErrorHandlerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the ErrorHandler class.
 */
class ErrorHandlerTest extends UnitTestCase
{
    protected ErrorHandlerInterface $subject;

    protected LoggerInterface $unusedLogger;
    protected LoggerInterface $trackingLogger;

    // These are borrowed from DefaultConfiguration.php.
    protected const DEFAULT_ERROR_HANDLER_LEVELS = E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR);
    protected const DEFAULT_EXCEPTIONAL_ERROR_LEVELS = E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_USER_DEPRECATED | E_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_WARNING);

    protected bool $resetSingletonInstances = true;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trackingLogger = new class () implements LoggerInterface {
            use LoggerTrait;
            public array $records = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };
    }

    /**
     * @test
     * @dataProvider errorTests
     */
    public function errorHandlerLogsCorrectly(
        int $levelsToHandle,
        int $levelsToThrow,
        int $levelToTrigger,
        string $message,
        string $file,
        int $line,
        ?bool $expectedReturn,
        ?string $deprecationsLogMessage,
        ?string $deprecationsLogLevel,
        ?string $errorsLogMessage,
        ?string $errorsLogLevel,
        ?string $exceptionMessage
    ): void {
        $logManager = new class () extends LogManager implements LogManagerInterface {
            protected array $loggers = [];
            public function getLogger(string $name = ''): LoggerInterface
            {
                return $this->loggers[$name];
            }
            public function setLogger(string $name, LoggerInterface $logger): self
            {
                $this->loggers[$name] = $logger;
                return $this;
            }
        };

        $subject = new ErrorHandler($levelsToHandle);
        $subject->setExceptionalErrors($levelsToThrow);
        $logManager->setLogger('TYPO3.CMS.deprecations', clone $this->trackingLogger);
        // disabled until the new logger is in place
        // $logManager->setLogger('TYPO3.CMS.php_errors', clone $this->trackingLogger);
        GeneralUtility::setSingletonInstance(LogManager::class, $logManager);

        try {
            $return = $subject->handleError($levelToTrigger, $message, $file, $line);
        } catch (\Exception $e) {
            if ($exceptionMessage) {
                self::assertEquals($exceptionMessage, $e->getMessage());
                return;
            }
            // An exception happened when it shouldn't; let PHPUnit deal with it.
            throw $e;
        }
        self::assertEquals($expectedReturn, $return);
        if ($deprecationsLogMessage) {
            self::assertEquals($deprecationsLogMessage, $logManager->getLogger('TYPO3.CMS.deprecations')->records[0]['message']);
            self::assertEquals($deprecationsLogLevel, $logManager->getLogger('TYPO3.CMS.deprecations')->records[0]['level']);
        }
        /**
         * disabled until the new channel is in place
         * if ($errorsLogMessage) {
         *    self::assertEquals($errorsLogMessage, $logManager->getLogger('TYPO3.CMS.php_errors')->records[0]['message']);
         *    self::assertEquals($errorsLogLevel, $logManager->getLogger('TYPO3.CMS.php_errors')->records[0]['level']);
         * }
         */
    }

    public static function errorTests(): iterable
    {
        // @todo Clean up the code base so the defaults can change to report notices.
        yield 'defaults ignore a notice' => [
            'levelsToHandle' => self::DEFAULT_ERROR_HANDLER_LEVELS,
            'levelsToThrow' => self::DEFAULT_EXCEPTIONAL_ERROR_LEVELS,
            'levelToTrigger' =>  E_NOTICE,
            'message' => 'A slightly bad thing happened',
            'file' => 'foo.php',
            'line' => 42,
            'expectedReturn' => ErrorHandlerInterface::ERROR_HANDLED,
            'deprecationsLogMessage' => null,
            'deprecationsLogLevel' => null,
            'errorsLogMessage' => null,
            'errorsLogLevel' => null,
            'exceptionMessage' => null,
        ];
        yield 'defaults log a warning' => [
            'levelsToHandle' => self::DEFAULT_ERROR_HANDLER_LEVELS,
            'levelsToThrow' => self::DEFAULT_EXCEPTIONAL_ERROR_LEVELS,
            'levelToTrigger' =>  E_WARNING,
            'message' => 'A bad thing happened',
            'file' => 'foo.php',
            'line' => 42,
            'expectedReturn' => ErrorHandlerInterface::ERROR_HANDLED,
            'deprecationsLogMessage' => null,
            'deprecationsLogLevel' => LogLevel::NOTICE,
            'errorsLogMessage' => 'Core: Error handler (BE): PHP Warning: A bad thing happened in foo.php line 42',
            'errorsLogLevel' => LogLevel::WARNING,
            'exceptionMessage' => null,
        ];
        // @todo Currently Errors are supressed by default. This seems unwise, but changing it is a separate task.
        /*
        yield 'defaults log an error' => [
            'levelsToHandle' => self::DEFAULT_ERROR_HANDLER_LEVELS,
            'levelsToThrow' => self::DEFAULT_EXCEPTIONAL_ERROR_LEVELS,
            'levelToTrigger' =>  E_ERROR,
            'message' => 'A very bad thing happened',
            'file' => 'foo.php',
            'line' => 42,
            'expectedReturn' => ErrorHandlerInterface::ERROR_HANDLED,
            'deprecationsLogMessage' => null,
            'deprecationsLogLevel' => null,
            'errorsLogMessage' => 'Core: Error handler (BE): PHP Error: A very bad thing happened in foo.php line 42',
            'errorsLogLevel' => LogLevel::ERROR,
            'exceptionMessage' => null,
        ];
        */
        yield 'user deprecations are logged' => [
            'levelsToHandle' => self::DEFAULT_ERROR_HANDLER_LEVELS,
            'levelsToThrow' => self::DEFAULT_EXCEPTIONAL_ERROR_LEVELS,
            'levelToTrigger' =>  E_USER_DEPRECATED,
            'message' => 'Stop doing that',
            'file' => 'foo.php',
            'line' => 42,
            'expectedReturn' => ErrorHandlerInterface::ERROR_HANDLED,
            'deprecationsLogMessage' => 'Core: Error handler (BE): TYPO3 Deprecation Notice: Stop doing that in foo.php line 42',
            'deprecationsLogLevel' => LogLevel::NOTICE,
            'errorsLogMessage' => null,
            'errorsLogLevel' => null,
            'exceptionMessage' => null,
        ];
        // @todo These ought to get logged to the deprecations channel.
        yield 'system deprecations are logged' => [
            'levelsToHandle' => self::DEFAULT_ERROR_HANDLER_LEVELS,
            'levelsToThrow' => self::DEFAULT_EXCEPTIONAL_ERROR_LEVELS,
            'levelToTrigger' =>  E_DEPRECATED,
            'message' => 'Stop doing that',
            'file' => 'foo.php',
            'line' => 42,
            'expectedReturn' => ErrorHandlerInterface::ERROR_HANDLED,
            'deprecationsLogMessage' => 'Core: Error handler (BE): PHP Runtime Deprecation Notice: Stop doing that in foo.php line 42',
            'deprecationsLogLevel' => LogLevel::NOTICE,
            'errorsLogMessage' => '',
            'errorsLogLevel' => null,
            'exceptionMessage' => null,
        ];
        yield 'user errors are logged but continue to PHP' => [
            'levelsToHandle' => self::DEFAULT_ERROR_HANDLER_LEVELS,
            'levelsToThrow' => self::DEFAULT_EXCEPTIONAL_ERROR_LEVELS,
            'levelToTrigger' =>  E_USER_ERROR,
            'message' => 'A horrible thing happened',
            'file' => 'foo.php',
            'line' => 42,
            'expectedReturn' => ErrorHandlerInterface::PROPAGATE_ERROR,
            'deprecationsLogMessage' => null,
            'deprecationsLogLevel' => null,
            'errorsLogMessage' => 'Core: Error handler (BE): PHP User Error: A horrible thing happened in foo.php line 42',
            'errorsLogLevel' => LogLevel::ERROR,
            'exceptionMessage' => null,
        ];
        yield 'can force errors to exceptions' => [
            'levelsToHandle' => self::DEFAULT_ERROR_HANDLER_LEVELS | E_WARNING,
            'levelsToThrow' => self::DEFAULT_EXCEPTIONAL_ERROR_LEVELS | E_WARNING,
            'levelToTrigger' =>  E_WARNING,
            'message' => 'A throwable thing happened',
            'file' => 'foo.php',
            'line' => 42,
            'expectedReturn' => null,
            'deprecationsLogMessage' => null,
            'deprecationsLogLevel' => null,
            'errorsLogMessage' => null,
            'errorsLogLevel' => LogLevel::ERROR,
            'exceptionMessage' => 'PHP Warning: A throwable thing happened in foo.php line 42',
        ];
    }
}
