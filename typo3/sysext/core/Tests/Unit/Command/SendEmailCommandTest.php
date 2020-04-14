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

namespace TYPO3\CMS\Core\Tests\Unit\Command;

use Prophecy\Argument;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Command\SendEmailCommand;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SendEmailCommandTest extends UnitTestCase
{
    /**
     * @test
     */
    public function executeWillFlushTheQueue()
    {
        $delayedTransportProphecy = $this->prophesize(DelayedTransportInterface::class);
        $delayedTransportProphecy->flushQueue(Argument::any())->willReturn(5);
        $realTransportProphecy = $this->prophesize(TransportInterface::class);

        $mailer = $this->getMockBuilder(Mailer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTransport', 'getRealTransport'])
            ->getMock();

        $mailer
            ->expects(self::any())
            ->method('getTransport')
            ->willReturn($delayedTransportProphecy->reveal());

        $mailer
            ->expects(self::any())
            ->method('getRealTransport')
            ->willReturn($realTransportProphecy->reveal());

        /** @var SendEmailCommand|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this->getMockBuilder(SendEmailCommand::class)
            ->setConstructorArgs(['mailer:spool:send'])
            ->setMethods(['getMailer'])
            ->getMock();

        $command
            ->expects(self::any())
            ->method('getMailer')
            ->willReturn($mailer);

        $tester = new CommandTester($command);
        $tester->execute([], []);

        self::assertTrue(strpos($tester->getDisplay(), '5 emails sent') > 0);
    }
}
