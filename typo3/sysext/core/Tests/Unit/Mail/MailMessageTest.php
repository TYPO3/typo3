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
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\MailboxHeader;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MailMessageTest extends UnitTestCase
{
    #[Test]
    public function isSentReturnsFalseIfMailWasNotSent(): void
    {
        $subject = new MailMessage();
        self::assertFalse($subject->isSent());
    }

    #[Test]
    public function setSubjectWorksAsExpected(): void
    {
        $subject = new MailMessage();
        $subject->setSubject('Test');
        self::assertSame('Test', $subject->getSubject());
        $subject->setSubject('Test2');
        self::assertSame('Test2', $subject->getSubject());
    }

    #[Test]
    public function setDateWorksAsExpected(): void
    {
        $time = time();
        $subject = new MailMessage();
        $subject->setDate($time);
        self::assertSame($time, (int)$subject->getDate()->format('U'));
        $time++;
        $subject->setDate($time);
        self::assertSame($time, (int)$subject->getDate()->format('U'));
    }

    #[Test]
    public function setReturnPathWorksAsExpected(): void
    {
        $subject = new MailMessage();
        $subject->setReturnPath('noreply@typo3.com');
        self::assertInstanceOf(Address::class, $subject->getReturnPath());
        self::assertSame('noreply@typo3.com', $subject->getReturnPath()->getAddress());
        $subject->setReturnPath('no-reply@typo3.com');
        self::assertNotNull($subject->getReturnPath());
        self::assertSame('no-reply@typo3.com', $subject->getReturnPath()->getAddress());
    }

    public static function setSenderAddressDataProvider(): array
    {
        return [
            'address without name' => [
                'admin@typo3.com', null, [
                    ['admin@typo3.com'],
                ],
            ],
            'address with name' => [
                'admin@typo3.com', 'Admin', [
                    ['admin@typo3.com', 'Admin', '<admin@typo3.com>'],
                ],
            ],
        ];
    }

    #[DataProvider('setSenderAddressDataProvider')]
    #[Test]
    public function setSenderWorksAsExpected(string $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        $subject->setSender($address, $name);
        self::assertInstanceOf(Address::class, $subject->getSender());
        self::assertSame($address, $subject->getSender()->getAddress());
        $this->assertCorrectAddresses([$subject->getSender()], $expectedAddresses);
    }

    public static function globalSetAddressDataProvider(): array
    {
        return [
            'address without name' => [
                'admin@typo3.com', null, [
                    ['admin@typo3.com'],
                ],
            ],
            'address with name' => [
                'admin@typo3.com', 'Admin', [
                    ['admin@typo3.com', 'Admin', '<admin@typo3.com>'],
                ],
            ],
            'address with name enclosed in quotes' => [
                'admin@typo3.com', '"Admin"', [
                    ['admin@typo3.com', 'Admin', '<admin@typo3.com>'],
                ],
            ],
            'multiple addresses without name' => [
                [
                    'admin@typo3.com',
                    'system@typo3.com',
                ], null, [
                    ['admin@typo3.com'],
                    ['system@typo3.com'],
                ],
            ],
            'address as array' => [
                ['admin@typo3.com' => 'Admin'], null, [
                    ['admin@typo3.com', 'Admin', '<admin@typo3.com>'],
                ],
            ],
            'multiple addresses as array' => [
                [
                    'admin@typo3.com' => 'Admin',
                    'system@typo3.com' => 'System',
                ], null, [
                    ['admin@typo3.com', 'Admin', '<admin@typo3.com>'],
                    ['system@typo3.com', 'System', '<system@typo3.com>'],
                ],
            ],
            'multiple addresses as array mixed' => [
                [
                    'admin@typo3.com' => 'Admin',
                    'it@typo3.com',
                    'system@typo3.com' => 'System',
                ], null, [
                    ['admin@typo3.com', 'Admin', '<admin@typo3.com>'],
                    ['it@typo3.com'],
                    ['system@typo3.com', 'System', '<system@typo3.com>'],
                ],
            ],
        ];
    }

    #[DataProvider('globalSetAddressDataProvider')]
    #[Test]
    public function setFromWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        // We first add one address, because set should override / remove existing addresses
        $subject->addFrom('foo@bar.com', 'Foo');
        $subject->setFrom($address, $name);
        $this->assertCorrectAddresses($subject->getFrom(), $expectedAddresses);
    }

    #[DataProvider('globalSetAddressDataProvider')]
    #[Test]
    public function setReplyToWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        // We first add one address, because set should override / remove existing addresses
        $subject->addReplyTo('foo@bar.com', 'Foo');
        $subject->setReplyTo($address, $name);
        $this->assertCorrectAddresses($subject->getReplyTo(), $expectedAddresses);
    }

    #[DataProvider('globalSetAddressDataProvider')]
    #[Test]
    public function setToToWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        // We first add one address, because set should override / remove existing addresses
        $subject->addTo('foo@bar.com', 'Foo');
        $subject->setTo($address, $name);
        $this->assertCorrectAddresses($subject->getTo(), $expectedAddresses);
    }

    #[DataProvider('globalSetAddressDataProvider')]
    #[Test]
    public function setCcToWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        // We first add one address, because set should override / remove existing addresses
        $subject->addCc('foo@bar.com', 'Foo');
        $subject->setCc($address, $name);
        $this->assertCorrectAddresses($subject->getCc(), $expectedAddresses);
    }

    #[DataProvider('globalSetAddressDataProvider')]
    #[Test]
    public function setBccToWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        // We first add one address, because set should override / remove existing addresses
        $subject->addBcc('foo@bar.com', 'Foo');
        $subject->setBcc($address, $name);
        $this->assertCorrectAddresses($subject->getBcc(), $expectedAddresses);
    }

    public static function globalAddAddressDataProvider(): array
    {
        return [
            'address without name' => [
                'admin@typo3.com', null, [
                    ['admin@typo3.com'],
                ],
            ],
            'address with name' => [
                'admin@typo3.com', 'Admin', [
                    ['admin@typo3.com', 'Admin', '<admin@typo3.com>'],
                ],
            ],
            'address as array' => [
                ['admin@typo3.com' => 'Admin'], null, [
                    ['admin@typo3.com', 'Admin', '<admin@typo3.com>'],
                ],
            ],
        ];
    }

    #[DataProvider('globalAddAddressDataProvider')]
    #[Test]
    public function addFromToWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        $subject->addFrom($address, $name);
        $this->assertCorrectAddresses($subject->getFrom(), $expectedAddresses);
    }

    #[DataProvider('globalAddAddressDataProvider')]
    #[Test]
    public function addReplyToToWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        $subject->addReplyTo($address, $name);
        $this->assertCorrectAddresses($subject->getReplyTo(), $expectedAddresses);
    }

    #[DataProvider('globalAddAddressDataProvider')]
    #[Test]
    public function addToToWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        $subject->addTo($address, $name);
        $this->assertCorrectAddresses($subject->getTo(), $expectedAddresses);
    }

    #[DataProvider('globalAddAddressDataProvider')]
    #[Test]
    public function addCcToWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        $subject->addCc($address, $name);
        $this->assertCorrectAddresses($subject->getCc(), $expectedAddresses);
    }

    #[DataProvider('globalAddAddressDataProvider')]
    #[Test]
    public function addBccToWorksAsExpected(string|array $address, ?string $name, array $expectedAddresses): void
    {
        $subject = new MailMessage();
        $subject->addBcc($address, $name);
        $this->assertCorrectAddresses($subject->getBcc(), $expectedAddresses);
    }

    #[Test]
    public function setReadReceiptToToWorksAsExpected(): void
    {
        $subject = new MailMessage();
        $subject->setReadReceiptTo('foo@example.com');
        /** @var MailboxHeader $header */
        $header = $subject->getHeaders()->get('Disposition-Notification-To');
        self::assertSame('foo@example.com', $header->getAddress()->getAddress());
    }

    public static function exceptionIsThrownForInvalidArgumentCombinationsDataProvider(): array
    {
        return [
            'setFrom' => ['setFrom'],
            'setReplyTo' => ['setReplyTo'],
            'setTo' => ['setTo'],
            'setCc' => ['setCc'],
            'setBcc' => ['setBcc'],
        ];
    }

    #[DataProvider('exceptionIsThrownForInvalidArgumentCombinationsDataProvider')]
    #[Test]
    public function exceptionIsThrownForInvalidArgumentCombinations(string $method): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1570543657);
        $subject = new MailMessage();
        $subject->{$method}(['foo@example.com'], 'A name');
    }

    /**
     * Assert that the correct address data are resolved after setting to the object.
     * This is a helper method to prevent duplicated code in this test.
     */
    private function assertCorrectAddresses(array $dataToCheck, array $expectedAddresses): void
    {
        self::assertCount(count($expectedAddresses), $dataToCheck);
        foreach ($expectedAddresses as $key => $expectedAddress) {
            self::assertIsArray($expectedAddress);
            self::assertSame($expectedAddress[0], $dataToCheck[$key]->getAddress());
            foreach ($expectedAddress as $expectedAddressPart) {
                self::assertStringContainsString($expectedAddressPart, $dataToCheck[$key]->toString());
            }
        }
    }
}
