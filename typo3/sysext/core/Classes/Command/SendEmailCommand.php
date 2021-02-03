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

namespace TYPO3\CMS\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\FileSpool;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for sending spooled messages.
 *
 * Inspired and partially taken from symfony's swiftmailer package, adapted for Symfony/Mailer.
 *
 * @link https://github.com/symfony/swiftmailer-bundle/blob/master/Command/SendEmailCommand.php
 */
class SendEmailCommand extends Command
{
    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this
            ->addOption('message-limit', null, InputOption::VALUE_REQUIRED, 'The maximum number of messages to send.')
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'The time limit for sending messages (in seconds).')
            ->addOption('recover-timeout', null, InputOption::VALUE_REQUIRED, 'The timeout for recovering messages that have taken too long to send (in seconds).')
            ->setAliases(['swiftmailer:spool:send']);
    }

    /**
     * Executes the mailer command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $mailer = $this->getMailer();

        $transport = $mailer->getTransport();
        if ($transport instanceof DelayedTransportInterface) {
            if ($transport instanceof FileSpool) {
                $transport->setMessageLimit((int)$input->getOption('message-limit'));
                $transport->setTimeLimit((int)$input->getOption('time-limit'));
                $recoverTimeout = (int)$input->getOption('recover-timeout');
                if ($recoverTimeout) {
                    $transport->recover($recoverTimeout);
                } else {
                    $transport->recover();
                }
            }
            $sent = $transport->flushQueue($mailer->getRealTransport());
            $io->comment($sent . ' emails sent');
            return 0;
        }
        $io->error('The Mailer Transport is not set to "spool".');

        return 1;
    }

    /**
     * Returns the TYPO3 mailer.
     *
     * @return Mailer
     */
    protected function getMailer(): Mailer
    {
        return GeneralUtility::makeInstance(Mailer::class);
    }
}
