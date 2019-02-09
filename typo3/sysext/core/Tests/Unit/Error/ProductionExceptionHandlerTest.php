<?php

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

use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Error\ProductionExceptionHandler;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * testcase for the \TYPO3\CMS\Core\Error\ProductionExceptionHandler class.
 */
class ProductionExceptionHandlerTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @var \TYPO3\CMS\Core\Error\ProductionExceptionHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(ProductionExceptionHandler::class)
            ->setMethods(['discloseExceptionInformation', 'sendStatusHeaders', 'writeLogEntries'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject->expects(self::any())->method('discloseExceptionInformation')->willReturn(true);
    }

    /**
     * @test
     */
    public function echoExceptionWebEscapesExceptionMessage()
    {
        $typo3InformationProphecy = $this->prophesize(Typo3Information::class);
        $typo3InformationProphecy->getCopyrightYear()->willReturn('1999-20XX');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationProphecy->reveal());
        $message = '<b>b</b><script>alert(1);</script>';
        $exception = new \Exception($message, 1476049364);
        ob_start();
        $this->subject->echoExceptionWeb($exception);
        $output = ob_get_contents();
        ob_end_clean();
        self::assertStringContainsString(htmlspecialchars($message), $output);
        self::assertStringNotContainsString($message, $output);
    }

    /**
     * @test
     */
    public function echoExceptionWebEscapesExceptionTitle()
    {
        $typo3InformationProphecy = $this->prophesize(Typo3Information::class);
        $typo3InformationProphecy->getCopyrightYear()->willReturn('1999-20XX');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationProphecy->reveal());
        $title = '<b>b</b><script>alert(1);</script>';
        /** @var $exception \Exception|\PHPUnit\Framework\MockObject\MockObject */
        $exception = $this->getMockBuilder('Exception')
            ->setMethods(['getTitle'])
            ->setConstructorArgs(['some message'])
            ->getMock();
        $exception->expects(self::any())->method('getTitle')->willReturn($title);
        ob_start();
        $this->subject->echoExceptionWeb($exception);
        $output = ob_get_contents();
        ob_end_clean();
        self::assertStringContainsString(htmlspecialchars($title), $output);
        self::assertStringNotContainsString($title, $output);
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
                'http://localhost/typo3/index.php?M=foo&moduleToken=--AnonymizedToken--'
            ],
            'url with valid token in the middle' => [
                'http://localhost/typo3/index.php?M=foo&moduleToken=5f1f7d447f22886e8ea206693b0d530ccd6b2b36&param=asdf',
                'http://localhost/typo3/index.php?M=foo&moduleToken=--AnonymizedToken--&param=asdf'
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
    public function logEntriesContainAnonymousTokens(string $originalUrl, string $expectedUrl)
    {
        $typo3InformationProphecy = $this->prophesize(Typo3Information::class);
        $typo3InformationProphecy->getCopyrightYear()->willReturn('1999-20XX');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationProphecy->reveal());
        $subject = new ProductionExceptionHandler();
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->critical(Argument::containingString($expectedUrl), Argument::cetera())->shouldBeCalled();
        $subject->setLogger($logger->reveal());

        GeneralUtility::setIndpEnv('TYPO3_REQUEST_URL', $originalUrl);
        $GLOBALS['BE_USER'] = null;

        $exception = new \Exception('message', 1476049365);
        ob_start();
        $subject->echoExceptionWeb($exception);
        // output is caught, so it does not pollute the test run
        ob_end_clean();
    }
}
