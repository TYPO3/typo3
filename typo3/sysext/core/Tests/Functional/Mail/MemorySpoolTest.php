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

namespace TYPO3\CMS\Core\Tests\Functional\Mail;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MemorySpool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MemorySpoolTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * The install tool runs on the failsafe container. The spool resolves the mailer when it is
     * destructed, so a container that does not know MailerInterface ends the request with a fatal
     * error and the queued mail is never delivered.
     */
    #[Test]
    public function queuedMailIsDeliveredOnTheFailsafeContainer(): void
    {
        $mboxFile = Environment::getVarPath() . '/memory-spool-test.mbox';
        $currentContainer = GeneralUtility::getContainer();
        $currentSingletons = GeneralUtility::getSingletonInstances();
        $currentMailSettings = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];

        try {
            $failsafeContainer = Bootstrap::init(ClassLoadingInformation::getClassLoader(), true);
            GeneralUtility::purgeInstances();
            GeneralUtility::setContainer($failsafeContainer);
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'mbox';
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] = $mboxFile;
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_type'] = 'memory';

            $email = new Email();
            $email->from('from@example.com')->to('to@example.com')->subject('Spooled')->text('Spooled');
            $mailer = $failsafeContainer->get(Mailer::class);
            $mailer->send($email);
            self::assertFileDoesNotExist($mboxFile);

            // At the end of a request PHP destructs the spool while the failsafe container is still active.
            // Releasing the container in a test needs the cycle collector, which may close the generator of the
            // queued message before the spool is destructed, so the destructor is triggered explicitly here.
            $spool = $mailer->getTransport();
            self::assertInstanceOf(MemorySpool::class, $spool);
            $spool->__destruct();
        } finally {
            $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $currentMailSettings;
            GeneralUtility::purgeInstances();
            GeneralUtility::setContainer($currentContainer);
            GeneralUtility::resetSingletonInstances($currentSingletons);
        }

        self::assertFileExists($mboxFile);
        self::assertStringContainsString('Subject: Spooled', (string)file_get_contents($mboxFile));
    }

    /**
     * The failsafe fallback must not replace what the full container resolves MailerInterface to.
     */
    #[Test]
    public function mailerInterfaceResolvesToTheMailerServiceOnTheFullContainer(): void
    {
        self::assertSame($this->get(Mailer::class), $this->get(MailerInterface::class));
    }
}
