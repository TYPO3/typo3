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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Command\SendEmailCommand;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SendEmailCommandTest extends UnitTestCase
{
    #[Test]
    public function executeWillFlushTheQueue(): void
    {
        $delayedTransportStub = self::createStub(DelayedTransportInterface::class);
        $delayedTransportStub->method('flushQueue')->willReturn(5);
        $realTransportStub = self::createStub(TransportInterface::class);

        $mailer = self::createStub(MailerInterface::class);
        $mailer->method('getTransport')->willReturn($delayedTransportStub);
        $mailer->method('getRealTransport')->willReturn($realTransportStub);

        $command = new SendEmailCommand($mailer);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertTrue(strpos($tester->getDisplay(), '5 emails sent') > 0);
    }
}
