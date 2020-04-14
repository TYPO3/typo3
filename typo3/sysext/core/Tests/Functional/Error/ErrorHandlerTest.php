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

namespace TYPO3\CMS\Core\Tests\Functional\Error;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Error\ErrorHandler;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ErrorHandlerTest extends FunctionalTestCase
{
    /**
     * Disabled on sqlite and mssql: They don't support init command "SET NAMES 'UTF8'". That's
     * ok since this test is not about db platform support but error handling in core.
     *
     * @test
     * @group not-sqlite
     * @group not-mssql
     */
    public function handleErrorFetchesDeprecations()
    {
        trigger_error(
            'The first error triggers database connection to be initialized and should be caught.',
            E_USER_DEPRECATED
        );
        trigger_error(
            'The second error should be caught by ErrorHandler as well.',
            E_USER_DEPRECATED
        );
        self::assertTrue(true);
    }

    /**
     * This test checks the following:
     *
     * Normally the core error handler is registered with an error level other than E_ALL to not handle E_NOTICE errors
     * for instance.
     *
     * As PHP allows to stack error handlers with different error levels it is possible to register an error handler
     * with an E_ALL error level. As that custom handler does not know the error level of it's previously registered
     * handler, it has no choice but to forward all occurring errors to the previous handler by calling
     * \TYPO3\CMS\Core\Error\ErrorHandler::handleError via call_user_func. This leads to
     * \TYPO3\CMS\Core\Error\ErrorHandler::handleError handling errors it was not registered for. Thus, there needs to
     * be a check if \TYPO3\CMS\Core\Error\ErrorHandler should handle the incoming error.
     *
     * @test
     * @group not-sqlite
     * @group not-mssql
     */
    public function handleErrorOnlyHandlesRegisteredErrorLevels(): void
    {
        // Make sure the core error handler does not return due to error_reporting being 0
        self::assertNotSame(0, error_reporting());

        // Make sure the core error handler does not return true due to a deprecation error
        $logManagerMock = $this->createMock(LogManager::class);
        $logManagerMock->expects(self::never())->method('getLogger')->with('TYPO3.CMS.deprecations');
        GeneralUtility::setSingletonInstance(LogManager::class, $logManagerMock);

        /** @var Logger|MockObject $logger */
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->setMethods(['log'])
            ->getMock();

        // Make sure the assigned logger does not log
        $logger->expects(self::never())->method('log');

        /** @var ErrorHandler|AccessibleObjectInterface $coreErrorHandler */
        $coreErrorHandler = new ErrorHandler(
            E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR)
        );
        $coreErrorHandler->setLogger($logger);

        $customErrorHandler = new class() {
            protected $existingHandler;

            public function setExistingHandler($existingHandler)
            {
                $this->existingHandler = $existingHandler;
            }

            /**
             * @param $code
             * @param $message
             * @param string $file
             * @param int $line
             * @param array $context
             * @return bool|mixed
             */
            public function handleError($code, $message, $file = '', $line = 0, $context = [])
            {
                // process errors
                if ($this->existingHandler !== null) {
                    return call_user_func($this->existingHandler, $code, $message, $file, $line, $context);
                }

                return false;
            }
        };

        $existingHandler = set_error_handler([$customErrorHandler, 'handleError'], E_ALL);
        $customErrorHandler->setExistingHandler($existingHandler);

        self::assertTrue($customErrorHandler->handleError(E_NOTICE, 'Notice error message', __FILE__, __LINE__));
        // This assertion is the base assertion but as \TYPO3\CMS\Core\Error\ErrorHandler::handleError has a few return
        // points that return true, the expectation on dependency objects are in place. We want to be sure that the
        // first return point is used by checking that the method does not log anything, which happens before later
        // return points that return true.
    }
}
