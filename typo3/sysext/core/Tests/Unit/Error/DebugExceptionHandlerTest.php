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
use TYPO3\CMS\Core\Error\DebugExceptionHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * testcase for the DebugExceptionHandler class.
 */
class DebugExceptionHandlerTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Error\DebugExceptionHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(DebugExceptionHandler::class)
            ->onlyMethods(['sendStatusHeaders', 'writeLogEntries'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @test
     */
    public function echoExceptionWebEscapesExceptionMessage(): void
    {
        $message = '<b>b</b><script>alert(1);</script>';
        $exception = new \Exception($message, 1476049363);
        ob_start();
        $this->subject->echoExceptionWeb($exception);
        $output = ob_get_contents();
        ob_end_clean();
        self::assertStringContainsString(htmlspecialchars($message), $output);
        self::assertStringNotContainsString($message, $output);
    }

    /**
     * Data provider with allowed contexts.
     *
     * @return string[][]
     */
    public function exampleUrlsForTokenAnonymization(): array
    {
        return [
            'url with valid token' => [
                'http://localhost/typo3/index.php?M=foo&moduleToken=5f1f7d447f22886e8ea206693b0d530ccd6b2b36',
                'http://localhost/typo3/index.php?M=foo&moduleToken=--AnonymizedToken--',
            ],
            'url with valid token in the middle' => [
                'http://localhost/typo3/index.php?M=foo&moduleToken=5f1f7d447f22886e8ea206693b0d530ccd6b2b36&param=asdf',
                'http://localhost/typo3/index.php?M=foo&moduleToken=--AnonymizedToken--&param=asdf',
            ],
            'url with invalid token' => [
                'http://localhost/typo3/index.php?M=foo&moduleToken=5f1f7d447f22886e8/e',
                'http://localhost/typo3/index.php?M=foo&moduleToken=5f1f7d447f22886e8/e',
            ],
            'url with empty token' => [
                'http://localhost/typo3/index.php?M=foo&moduleToken=',
                'http://localhost/typo3/index.php?M=foo&moduleToken=',
            ],
            'url with no token' => [
                'http://localhost/typo3/index.php?M=foo',
                'http://localhost/typo3/index.php?M=foo',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider exampleUrlsForTokenAnonymization
     * @param string $originalUrl
     * @param string $expectedUrl
     */
    public function logEntriesContainAnonymousTokens(string $originalUrl, string $expectedUrl): void
    {
        $subject = new DebugExceptionHandler();

        $logger = new class() implements LoggerInterface {
            use LoggerTrait;
            public array $records = [];
            public function log($level, $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };

        $subject->setLogger($logger);

        GeneralUtility::setIndpEnv('TYPO3_REQUEST_URL', $originalUrl);

        $exception = new \Exception('message', 1476049367);
        ob_start();
        $subject->echoExceptionWeb($exception);
        // output is caught, so it does not pollute the test run
        ob_end_clean();

        self::assertEquals('critical', $logger->records[0]['level']);
        self::assertEquals($expectedUrl, $logger->records[0]['context']['request_url']);
    }
}
