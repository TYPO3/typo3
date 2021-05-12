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

namespace TYPO3\CMS\Backend\Command\ProgressListener;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\View\ProgressListenerInterface;

/**
 * Shows the update for the reference index progress on the command line.
 * @internal not part of TYPO3 Public API as it is an implementation of a concrete feature.
 */
class ReferenceIndexProgressListener implements ProgressListenerInterface
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var ProgressBar|null
     */
    protected $progressBar;

    /**
     * @var bool
     */
    protected $isEnabled = false;

    public function initialize(SymfonyStyle $io)
    {
        $this->io = $io;
        $this->isEnabled = $io->isQuiet() === false;
    }

    public function start(int $maxSteps = 0, string $additionalMessage = null): void
    {
        if (!$this->isEnabled) {
            return;
        }
        $tableName = $additionalMessage;
        if ($maxSteps > 0) {
            $this->io->section('Update index of table ' . $tableName);
            $this->progressBar = $this->io->createProgressBar($maxSteps);
            $this->progressBar->start($maxSteps);
        } else {
            $this->io->section('Nothing to update for table ' . $tableName);
            $this->progressBar = null;
        }
    }

    public function advance(int $step = 1, string $additionalMessage = null): void
    {
        if (!$this->isEnabled) {
            return;
        }
        if ($additionalMessage) {
            $this->showMessageWhileInProgress(function () use ($additionalMessage) {
                $this->io->writeln($additionalMessage);
            });
        }
        if ($this->progressBar !== null) {
            $this->progressBar->advance();
        }
    }

    public function finish(string $additionalMessage = null): void
    {
        if (!$this->isEnabled) {
            return;
        }
        if ($this->progressBar !== null) {
            $this->progressBar->finish();
            $this->progressBar = null;
        }
        $this->io->writeln(PHP_EOL);
        if ($additionalMessage) {
            $this->io->writeln($additionalMessage);
        }
    }

    public function log(string $message, string $logLevel = LogLevel::INFO): void
    {
        if (!$this->isEnabled) {
            return;
        }
        $this->showMessageWhileInProgress(function () use ($message, $logLevel) {
            switch ($logLevel) {
                case LogLevel::ERROR:
                    $this->io->error($message);
                    break;
                case LogLevel::WARNING:
                    $this->io->warning($message);
                    break;
                default:
                    $this->io->writeln($message);
            }
        });
    }

    protected function showMessageWhileInProgress(callable $messageFunction): void
    {
        if ($this->progressBar !== null) {
            $this->progressBar->clear();
            $messageFunction();
            $this->progressBar->display();
        } else {
            $messageFunction();
        }
    }
}
