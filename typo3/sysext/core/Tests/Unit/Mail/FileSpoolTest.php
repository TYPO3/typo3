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

namespace TYPO3\CMS\Core\Tests\Unit\Mail;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\FileSpool;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FileSpoolTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected bool $resetSingletonInstances = true;

    protected ?FileSpool $subject;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new FileSpool(Environment::getVarPath() . '/spool/');
        $this->subject->setMessageLimit(10);
        $this->subject->setTimeLimit(1);
    }

    /**
     * @test
     * @dataProvider messageCountProvider
     */
    public function spoolsMessagesCorrectly(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->subject->send(
                new RawMessage('test message ' . $i),
                new Envelope(new Address('sender@example.com'), [new Address('recipient@example.com')])
            );
        }

        self::assertEquals($count, $this->subject->flushQueue(new NullTransport()));
    }

    /**
     * Data provider for message spooling test
     *
     * @return array Data sets
     */
    public static function messageCountProvider(): array
    {
        return [
            'spools 0 messages' => [0],
            'spools 1 message' => [1],
            'spools 2 messages' => [2],
        ];
    }
}
