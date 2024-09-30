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

use TYPO3\CMS\Core\Error\ErrorHandler;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ErrorHandlerTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    protected $configurationToUseInTestInstance = [
        'SYS' => [
            'errorHandler' => ErrorHandler::class,
        ],
    ];

    public function tearDown(): void
    {
        // Unset errorHandler instance configured for this test case
        restore_error_handler();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function handleErrorFetchesDeprecations(): void
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
     */
    public function handleErrorOnlyHandlesRegisteredErrorLevels(): void
    {
        // Make sure the core error handler does not return due to error_reporting being 0
        self::assertNotSame(0, error_reporting());

        // Make sure the core error handler does not return true due to a deprecation error
        $logManagerMock = $this->createMock(LogManager::class);
        $logManagerMock->expects(self::never())->method('getLogger')->with('TYPO3.CMS.deprecations');
        GeneralUtility::setSingletonInstance(LogManager::class, $logManagerMock);

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['log'])
            ->getMock();

        // Make sure the assigned logger does not log
        $logger->expects(self::never())->method('log');

        $coreErrorHandler = new ErrorHandler(
            // @todo: Remove 2048 (deprecated E_STRICT) in v14, as this value is no longer used by PHP itself
            //        and only kept here here because possible custom PHP extensions may still use it.
            //        See https://wiki.php.net/rfc/deprecations_php_8_4#remove_e_strict_error_level_and_deprecate_e_strict_constant
            E_ALL & ~(2048 /* deprecated E_STRICT */ | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR)
        );
        $coreErrorHandler->setLogger($logger);

        $customErrorHandler = new class () {
            protected $existingHandler;

            public function setExistingHandler($existingHandler): void
            {
                $this->existingHandler = $existingHandler;
            }

            /**
             * @param int $code
             * @param string $message
             * @param string $file
             * @param int $line
             * @param array $context
             * @return mixed
             */
            public function handleError(int $code, string $message, string $file = '', int $line = 0, array $context = [])
            {
                // process errors
                if ($this->existingHandler !== null) {
                    return ($this->existingHandler)($code, $message, $file, $line, $context);
                }

                return false;
            }
        };

        $existingHandler = set_error_handler([$customErrorHandler, 'handleError'], E_ALL);
        $customErrorHandler->setExistingHandler($existingHandler);

        // This assertion is the base assertion but as \TYPO3\CMS\Core\Error\ErrorHandler::handleError has a few return
        // points that return true, the expectation on dependency objects are in place. We want to be sure that the
        // first return point is used by checking that the method does not log anything, which happens before later
        // return points that return true.
        self::assertTrue($customErrorHandler->handleError(E_NOTICE, 'Notice error message', __FILE__, __LINE__));

        // Unset the closure error handler again
        restore_error_handler();
    }
}
