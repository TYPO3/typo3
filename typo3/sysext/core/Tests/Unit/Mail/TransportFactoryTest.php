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

use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\FileSpool;
use TYPO3\CMS\Core\Mail\MemorySpool;
use TYPO3\CMS\Core\Mail\TransportFactory;
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeFileSpoolFixture;
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeInvalidSpoolFixture;
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeMemorySpoolFixture;
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeValidSpoolFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TransportFactoryTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function getReturnsSpoolTransportUsingFileSpool(): void
    {
        $mailSettings = [
            'transport' => 'sendmail',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => 'file',
            'transport_spool_filepath' => '.',
        ];

        // Register fixture class
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][FileSpool::class]['className'] = FakeFileSpoolFixture::class;

        $transport = (new TransportFactory())->get($mailSettings);
        self::assertInstanceOf(DelayedTransportInterface::class, $transport);
        self::assertInstanceOf(FakeFileSpoolFixture::class, $transport);

        $path = $transport->getPath();
        self::assertStringContainsString($mailSettings['transport_spool_filepath'], $path);
    }

    /**
     * @test
     */
    public function getReturnsSpoolTransportUsingMemorySpool(): void
    {
        $mailSettings = [
            'transport' => 'mail',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => 'memory',
            'transport_spool_filepath' => Environment::getVarPath() . '/messages/',
        ];

        // Register fixture class
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][MemorySpool::class]['className'] = FakeMemorySpoolFixture::class;

        $transport = (new TransportFactory())->get($mailSettings);
        self::assertInstanceOf(DelayedTransportInterface::class, $transport);
        self::assertInstanceOf(MemorySpool::class, $transport);
    }

    /**
     * @test
     */
    public function getReturnsSpoolTransportUsingCustomSpool(): void
    {
        $mailSettings = [
            'transport' => 'sendmail',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => FakeValidSpoolFixture::class,
            'transport_spool_filepath' => Environment::getVarPath() . '/messages/',
        ];

        $transport = (new TransportFactory())->get($mailSettings);
        self::assertInstanceOf(DelayedTransportInterface::class, $transport);
        self::assertInstanceOf(FakeValidSpoolFixture::class, $transport);

        self::assertSame($mailSettings, $transport->getSettings());
    }

    /**
     * @test
     */
    public function getThrowsRuntimeExceptionForInvalidCustomSpool(): void
    {
        $this->expectExceptionCode(1466799482);

        $mailSettings = [
            'transport' => 'mail',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => FakeInvalidSpoolFixture::class,
            'transport_spool_filepath' => Environment::getVarPath() . '/messages/',
        ];

        (new TransportFactory())->get($mailSettings);
    }

    /**
     * @test
     */
    public function getReturnsMailerTransportInterface(): void
    {
        $mailSettings = [
            'transport' => 'smtp',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => '',
            'transport_spool_filepath' => Environment::getVarPath() . '/messages/',
        ];

        $transport = (new TransportFactory())->get($mailSettings);
        self::assertInstanceOf(TransportInterface::class, $transport);
    }
}
