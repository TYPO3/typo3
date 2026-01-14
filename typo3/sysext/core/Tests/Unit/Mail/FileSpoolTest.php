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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\FileSpool;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileSpoolTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private string $spoolPath;
    private LoggerInterface&MockObject $loggerMock;
    private FileSpool $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spoolPath = Environment::getVarPath() . '/spool/';
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->subject = new FileSpool($this->spoolPath, null, $this->loggerMock);
        $this->subject->setMessageLimit(10);
        $this->subject->setTimeLimit(1);
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

    #[DataProvider('messageCountProvider')]
    #[Test]
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

    #[Test]
    public function flushQueueSkipsDisallowedSerializedMessages(): void
    {
        $disallowedMessage = $this->spoolPath . 'disallowed.message';
        $disallowedMessageSending = $this->spoolPath . 'disallowed.message.sending';

        file_put_contents($disallowedMessage, serialize(new \stdClass()));

        $this->loggerMock->expects(self::once())->method('error')->with(
            'Serialized message from {fileName} was rejected, because it contains a disallowed class object.'
        );

        self::assertFileExists($disallowedMessage);
        self::assertSame(0, $this->subject->flushQueue(new NullTransport()));
        self::assertFileDoesNotExist($disallowedMessage);
        self::assertFileDoesNotExist($disallowedMessageSending);
    }

    #[Test]
    public function flushQueueSkipsUnsupportedSerializedMessages(): void
    {
        $invalidMessage = $this->spoolPath . 'invalid.message';
        $invalidMessageSending = $this->spoolPath . 'invalid.message.sending';

        file_put_contents($invalidMessage, serialize(new RawMessage('Hello World')));

        $this->loggerMock->expects(self::once())->method('error')->with(
            'Serialized message from {fileName} was rejected, because {className} is not an instance of SentMessage.',
            [
                'fileName' => $invalidMessage,
                'className' => RawMessage::class,
            ],
        );

        self::assertFileExists($invalidMessage);
        self::assertSame(0, $this->subject->flushQueue(new NullTransport()));
        self::assertFileDoesNotExist($invalidMessage);
        self::assertFileDoesNotExist($invalidMessageSending);
    }

    #[Test]
    public function flushQueueRetainsMessageOnTransportFailure(): void
    {
        $message = $this->spoolPath . 'test.message';
        $messageSending = $this->spoolPath . 'test.message.sending';

        file_put_contents($message, serialize(new SentMessage(
            new RawMessage('test message'),
            new Envelope(new Address('sender@example.com'), [new Address('recipient@example.com')])
        )));

        $transportMock = $this->createMock(TransportInterface::class);
        $transportMock->method('send')->willThrowException(new \RuntimeException('Transport failure', 1768491292));
        $exception = null;

        self::assertFileExists($message);

        try {
            $this->subject->flushQueue($transportMock);
        } catch (\RuntimeException $exception) {
        }

        self::assertNotNull($exception);
        self::assertFileDoesNotExist($message);
        self::assertFileExists($messageSending);

        unlink($messageSending);
    }

    #[Test]
    public function flushQueueProperlyDeserializesMessagesWithAttachmentBodyAsString(): void
    {
        $message = $this->spoolPath . 'test.message';
        $messageSending = $this->spoolPath . 'test.message.sending';

        $email = new Email();
        $email->from('sender@example.com');
        $email->to('recipient@example.com');
        $email->text('test message');
        $email->attach('foo');

        $envelope = new Envelope(new Address('sender@example.com'), [new Address('recipient@example.com')]);

        file_put_contents($message, serialize(new SentMessage($email, $envelope)));

        self::assertSame(1, $this->subject->flushQueue(new NullTransport()));
        self::assertFileDoesNotExist($message);
        self::assertFileDoesNotExist($messageSending);
    }

    #[Test]
    public function flushQueueProperlyDeserializesMessagesWithAttachmentBodyAsFile(): void
    {
        $message = $this->spoolPath . 'test.message';
        $messageSending = $this->spoolPath . 'test.message.sending';

        $email = new Email();
        $email->from('sender@example.com');
        $email->to('recipient@example.com');
        $email->text('test message');
        $email->attachFromPath(__DIR__ . '/Fixtures/attachment.txt');

        $envelope = new Envelope(new Address('sender@example.com'), [new Address('recipient@example.com')]);

        file_put_contents($message, serialize(new SentMessage($email, $envelope)));

        self::assertSame(1, $this->subject->flushQueue(new NullTransport()));
        self::assertFileDoesNotExist($message);
        self::assertFileDoesNotExist($messageSending);
    }
}
