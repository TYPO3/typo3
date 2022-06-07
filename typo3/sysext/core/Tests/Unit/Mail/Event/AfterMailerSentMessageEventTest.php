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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\TransportFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AfterMailerSentMessageEventTest extends UnitTestCase
{
    use ProphecyTrait;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        $transportFactory = $this->prophesize(TransportFactory::class);
        $transportFactory->get(Argument::any())->willReturn($this->prophesize(SendmailTransport::class));
        GeneralUtility::setSingletonInstance(TransportFactory::class, $transportFactory->reveal());
    }

    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $mailer = (new Mailer());
        $event = new AfterMailerSentMessageEvent($mailer);

        self::assertEquals($mailer, $event->getMailer());
    }
}
