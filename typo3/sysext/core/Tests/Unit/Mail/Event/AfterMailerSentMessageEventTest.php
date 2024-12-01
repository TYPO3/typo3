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

namespace TYPO3\CMS\Core\Tests\Unit\Mail\Event;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\TransportFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterMailerSentMessageEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $transportFactory = $this->createMock(TransportFactory::class);
        $transportFactory->method('get')->with(self::anything())->willReturn($this->createMock(SendmailTransport::class));
        GeneralUtility::addInstance(TransportFactory::class, $transportFactory);

        $mailer = (new Mailer());
        $event = new AfterMailerSentMessageEvent($mailer);

        self::assertEquals($mailer, $event->getMailer());
    }
}
