<?php
namespace TYPO3\CMS\Core\Tests\Functional\Command;

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

use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Command\SendEmailCommand;

/**
 * Testcase for the TYPO3\CMS\Core\Command\SendEmailCommand class.
 */
class SendEmailCommandTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function executeWillFlushTheQueue()
    {
        $realTransport = $this->getMockBuilder(\Swift_Transport::class)->getMock();

        $spool = $this->getMockBuilder(\Swift_Spool::class)->getMock();
        $spool
            ->expects($this->once())
            ->method('flushQueue')
            ->with($realTransport)
            ->will($this->returnValue(5))
        ;
        $spoolTransport = new \Swift_Transport_SpoolTransport(new \Swift_Events_SimpleEventDispatcher(), $spool);

        $mailer = $this->getMockBuilder(\TYPO3\CMS\Core\Mail\Mailer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTransport', 'getRealTransport'])
            ->getMock();

        $mailer
            ->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($spoolTransport));

        $mailer
            ->expects($this->any())
            ->method('getRealTransport')
            ->will($this->returnValue($realTransport));

        $command = $this->getMockBuilder(SendEmailCommand::class)
            ->setConstructorArgs(['swiftmailer:spool:send'])
            ->setMethods(['getMailer'])
            ->getMock();

        $command
            ->expects($this->any())
            ->method('getMailer')
            ->will($this->returnValue($mailer));

        $tester = new CommandTester($command);
        $tester->execute([], []);

        $this->assertStringEndsWith("5 emails sent\n", $tester->getDisplay());
    }
}
