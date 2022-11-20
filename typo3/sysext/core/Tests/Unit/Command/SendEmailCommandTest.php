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

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Command\SendEmailCommand;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SendEmailCommandTest extends UnitTestCase
{
    /**
     * @test
     */
    public function executeWillFlushTheQueue(): void
    {
        $delayedTransportMock = $this->createMock(DelayedTransportInterface::class);
        $delayedTransportMock->method('flushQueue')->with(self::anything())->willReturn(5);
        $realTransportMock = $this->createMock(TransportInterface::class);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('getTransport')->willReturn($delayedTransportMock);
        $mailer->method('getRealTransport')->willReturn($realTransportMock);

        $command = $this->getMockBuilder(SendEmailCommand::class)
            ->setConstructorArgs(['mailer:spool:send'])
            ->onlyMethods(['getMailer'])
            ->getMock();
        $command->method('getMailer')->willReturn($mailer);

        $tester = new CommandTester($command);
        $tester->execute([], []);

        self::assertTrue(strpos($tester->getDisplay(), '5 emails sent') > 0);
    }
}
