<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Mail;

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

use TYPO3\CMS\Core\Mail\MemorySpool;
use TYPO3\CMS\Core\Mail\TransportFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the TYPO3\CMS\Core\Mail\TransportFactory class.
 */
class TransportFactoryTest extends UnitTestCase
{
    /**
     * Subject is not notice free, disable E_NOTICES
     */
    protected static $suppressNotices = true;

    /**
     * * @var TransportFactory
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = GeneralUtility::makeInstance(TransportFactory::class);
    }

    /**
     * @test
     */
    public function getReturnsSwiftSpoolTransportUsingSwiftFileSpool()
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
            'transport_spool_type' => 'file',
            'transport_spool_filepath' => '.',
        ];

        // Register fixture class
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\Swift_FileSpool::class]['className'] = Fixtures\FakeFileSpoolFixture::class;

        /** @var \Swift_SpoolTransport $transport */
        $transport = $this->subject->get($mailSettings);
        $this->assertInstanceOf(\Swift_SpoolTransport::class, $transport);

        /** @var Fixtures\FakeFileSpoolFixture $spool */
        $spool = $transport->getSpool();
        $this->assertInstanceOf(\Swift_FileSpool::class, $spool);

        $path = $spool->getPath();
        $this->assertContains($mailSettings['transport_spool_filepath'], $path);
    }

    /**
     * @test
     */
    public function getReturnsSwiftSpoolTransportUsingSwiftMemorySpool()
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
            'transport_spool_filepath' => 'typo3temp/var/messages/',
        ];

        // Register fixture class
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][MemorySpool::class]['className'] = Fixtures\FakeMemorySpoolFixture::class;

        /** @var \Swift_SpoolTransport $transport */
        $transport = $this->subject->get($mailSettings);
        $this->assertInstanceOf(\Swift_SpoolTransport::class, $transport);

        /** @var \Swift_MemorySpool $spool */
        $spool = $transport->getSpool();
        $this->assertInstanceOf(\Swift_MemorySpool::class, $spool);
    }

    /**
     * @test
     */
    public function getReturnsSwiftSpoolTransportUsingCustomSpool()
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
            'transport_spool_type' => 'TYPO3\\CMS\\Core\\Tests\\Unit\\Mail\\Fixtures\\FakeValidSpoolFixture',
            'transport_spool_filepath' => 'typo3temp/var/messages/',
        ];

        /** @var \Swift_SpoolTransport $transport */
        $transport = $this->subject->get($mailSettings);
        $this->assertInstanceOf(\Swift_SpoolTransport::class, $transport);

        /** @var Fixtures\FakeValidSpoolFixture $spool */
        $spool = $transport->getSpool();
        $this->assertInstanceOf(Fixtures\FakeValidSpoolFixture::class, $spool);

        $this->assertSame($mailSettings, $spool->getSettings());
    }

    /**
     * @test
     */
    public function getThrowsRuntimeExceptionForInvalidCustomSpool()
    {
        $this->expectException(\RuntimeException::class);
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
            'transport_spool_type' => 'TYPO3\\CMS\\Core\\Tests\\Unit\\Mail\\Fixtures\\FakeInvalidSpoolFixture',
            'transport_spool_filepath' => 'typo3temp/var/messages/',
        ];

        $this->subject->get($mailSettings);
    }

    /**
     * @test
     */
    public function getReturnsSwiftMailTransport()
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
            'transport_spool_type' => '',
            'transport_spool_filepath' => 'typo3temp/var/messages/',
        ];

        $transport = $this->subject->get($mailSettings);
        $this->assertInstanceOf(\Swift_MailTransport::class, $transport);
    }
}
